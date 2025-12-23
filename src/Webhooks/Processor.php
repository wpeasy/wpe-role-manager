<?php
/**
 * Webhook Processor
 *
 * Handles WP Cron queue processing, HTTP delivery, and retry logic.
 *
 * @package WP_Easy\RoleManager\Webhooks
 */

namespace WP_Easy\RoleManager\Webhooks;

defined('ABSPATH') || exit;

/**
 * Processor class for webhook delivery.
 */
final class Processor {

    /**
     * Cron hook name.
     */
    public const CRON_HOOK = 'wpe_rm_process_webhook_queue';

    /**
     * Cron schedule name.
     */
    public const CRON_SCHEDULE = 'wpe_rm_every_minute';

    /**
     * Retry delays in seconds (exponential backoff).
     */
    private const RETRY_DELAYS = [
        1 => 0,      // Attempt 1: immediate
        2 => 60,     // Attempt 2: 1 minute
        3 => 300,    // Attempt 3: 5 minutes
        4 => 900,    // Attempt 4: 15 minutes
        5 => 3600,   // Attempt 5: 1 hour
    ];

    /**
     * Request timeout in seconds.
     */
    private const REQUEST_TIMEOUT = 15;

    /**
     * Initialize processor - register cron schedule and handler.
     *
     * @return void
     */
    public static function init(): void {
        // Register custom cron schedule
        add_filter('cron_schedules', [self::class, 'add_cron_schedule']);

        // Register cron handler
        add_action(self::CRON_HOOK, [self::class, 'process_queue']);

        // Schedule cron if not already scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    /**
     * Add custom cron schedule (every minute).
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public static function add_cron_schedule(array $schedules): array {
        $schedules[self::CRON_SCHEDULE] = [
            'interval' => 60,
            'display' => __('Every Minute', WPE_RM_TEXTDOMAIN),
        ];

        return $schedules;
    }

    /**
     * Process the webhook queue (cron callback).
     *
     * @return void
     */
    public static function process_queue(): void {
        $entries = Dispatcher::get_pending_entries(10);

        if (empty($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            self::process_entry($entry);
        }
    }

    /**
     * Process a single queue entry.
     *
     * @param array $entry Queue entry.
     * @return void
     */
    private static function process_entry(array $entry): void {
        $webhook = Manager::get_webhook($entry['webhook_id']);

        if (!$webhook) {
            // Webhook was deleted, remove from queue
            Dispatcher::remove_from_queue($entry['id']);
            return;
        }

        if (!$webhook['enabled']) {
            // Webhook is disabled, remove from queue
            Dispatcher::remove_from_queue($entry['id']);
            return;
        }

        $attempt = $entry['attempts'] + 1;
        $response = self::send_webhook($webhook, $entry['payload'], $attempt);

        if ($response['success']) {
            // Success - remove from queue
            Dispatcher::remove_from_queue($entry['id']);

            // Log success
            Logger::log_outgoing($webhook, $entry['event'], $entry['payload'], [
                'code' => $response['code'],
                'body' => $response['body'],
                'duration_ms' => $response['duration_ms'],
                'success' => true,
                'will_retry' => false,
                'attempt' => $attempt,
                'error' => '',
            ]);
        } else {
            // Failure - check if should retry
            $max_retries = $webhook['retries'] ?? 1;
            $will_retry = $attempt < $max_retries;

            if ($will_retry) {
                // Update entry for retry
                $next_delay = self::RETRY_DELAYS[$attempt + 1] ?? 3600;
                Dispatcher::update_entry_status(
                    $entry['id'],
                    'pending',
                    $attempt,
                    time() + $next_delay
                );
            } else {
                // Max retries reached - mark as failed and remove
                Dispatcher::remove_from_queue($entry['id']);
            }

            // Log failure
            Logger::log_outgoing($webhook, $entry['event'], $entry['payload'], [
                'code' => $response['code'],
                'body' => $response['body'],
                'duration_ms' => $response['duration_ms'],
                'success' => false,
                'will_retry' => $will_retry,
                'attempt' => $attempt,
                'error' => $response['error'],
            ]);
        }
    }

    /**
     * Send a webhook HTTP request.
     *
     * @param array $webhook Webhook configuration.
     * @param array $payload Request payload.
     * @param int   $attempt Current attempt number.
     * @return array Response data.
     */
    public static function send_webhook(array $webhook, array $payload, int $attempt = 1): array {
        $start_time = microtime(true);
        $json_payload = wp_json_encode($payload);
        $timestamp = time();

        // Build headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-WPE-RM-Event' => $payload['event'] ?? '',
            'X-WPE-RM-Timestamp' => (string) $timestamp,
            'X-WPE-RM-Signature' => self::sign_payload($json_payload, $timestamp, $webhook['secret']),
            'X-WPE-RM-Attempt' => (string) $attempt,
        ];

        // Add custom headers
        foreach ($webhook['headers'] ?? [] as $custom_header) {
            if (!empty($custom_header['key'])) {
                $headers[$custom_header['key']] = $custom_header['value'];
            }
        }

        // Build request args
        $args = [
            'method' => $webhook['method'] ?? 'POST',
            'headers' => $headers,
            'timeout' => self::REQUEST_TIMEOUT,
            'sslverify' => !defined('WP_DEBUG') || !WP_DEBUG,
        ];

        // Add body for POST requests
        if ($args['method'] === 'POST') {
            $args['body'] = $json_payload;
        }

        // Send request
        $response = wp_remote_request($webhook['url'], $args);

        $duration_ms = (int) ((microtime(true) - $start_time) * 1000);

        // Handle response
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'code' => 0,
                'body' => '',
                'error' => $response->get_error_message(),
                'duration_ms' => $duration_ms,
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Consider 2xx and 3xx as success
        $success = $code >= 200 && $code < 400;

        return [
            'success' => $success,
            'code' => $code,
            'body' => $body,
            'error' => $success ? '' : "HTTP $code",
            'duration_ms' => $duration_ms,
        ];
    }

    /**
     * Generate HMAC signature for payload.
     *
     * @param string $payload   JSON payload.
     * @param int    $timestamp Unix timestamp.
     * @param string $secret    Webhook secret.
     * @return string Signature in format "sha256=xxx".
     */
    public static function sign_payload(string $payload, int $timestamp, string $secret): string {
        $data = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $data, $secret);
        return 'sha256=' . $signature;
    }

    /**
     * Verify a webhook signature.
     *
     * @param string $payload   JSON payload.
     * @param int    $timestamp Unix timestamp.
     * @param string $secret    Webhook secret.
     * @param string $signature Provided signature.
     * @return bool True if signature is valid.
     */
    public static function verify_signature(string $payload, int $timestamp, string $secret, string $signature): bool {
        $expected = self::sign_payload($payload, $timestamp, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Calculate delay for next retry attempt.
     *
     * @param int $attempt Current attempt number.
     * @return int Delay in seconds.
     */
    public static function calculate_next_retry(int $attempt): int {
        return self::RETRY_DELAYS[$attempt + 1] ?? 3600;
    }

    /**
     * Send a test webhook.
     *
     * @param array $webhook Webhook configuration.
     * @return array Response data.
     */
    public static function send_test(array $webhook): array {
        $test_payload = [
            'event' => 'test',
            'timestamp' => gmdate('c'),
            'site_url' => home_url(),
            'data' => [
                'message' => 'This is a test webhook from WP Easy Role Manager.',
                'webhook_id' => $webhook['id'],
                'webhook_name' => $webhook['name'],
            ],
        ];

        $response = self::send_webhook($webhook, $test_payload);

        // Log the test
        Logger::log_outgoing($webhook, 'test', $test_payload, [
            'code' => $response['code'],
            'body' => $response['body'],
            'duration_ms' => $response['duration_ms'],
            'success' => $response['success'],
            'will_retry' => false,
            'attempt' => 1,
            'error' => $response['error'],
        ]);

        return $response;
    }

    /**
     * Cleanup on plugin deactivation.
     *
     * @return void
     */
    public static function deactivate(): void {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }
}
