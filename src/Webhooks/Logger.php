<?php
/**
 * Webhook Logger
 *
 * Handles activity logging for webhook operations.
 *
 * @package WP_Easy\RoleManager\Webhooks
 */

namespace WP_Easy\RoleManager\Webhooks;

defined('ABSPATH') || exit;

/**
 * Logger class for webhook activity.
 */
final class Logger {

    /**
     * Option key for storing webhook logs.
     */
    private const OPTION_KEY = 'wpe_rm_webhooks_log';

    /**
     * Default log retention limit.
     */
    private const DEFAULT_RETENTION = 500;

    /**
     * Log a webhook activity.
     *
     * @param array $entry Log entry data.
     * @return void
     */
    public static function log(array $entry): void {
        $logs = self::get_logs();

        $log_entry = [
            'id' => 'whl_' . bin2hex(random_bytes(8)),
            'direction' => sanitize_key($entry['direction'] ?? 'outgoing'),
            'webhook_id' => sanitize_text_field($entry['webhook_id'] ?? ''),
            'webhook_name' => sanitize_text_field($entry['webhook_name'] ?? ''),
            'event' => sanitize_text_field($entry['event'] ?? ''),
            'url' => esc_url_raw($entry['url'] ?? ''),
            'method' => sanitize_text_field($entry['method'] ?? 'POST'),
            'request_payload' => $entry['request_payload'] ?? null,
            'response_code' => (int) ($entry['response_code'] ?? 0),
            'response_body' => self::truncate_response($entry['response_body'] ?? ''),
            'duration_ms' => (int) ($entry['duration_ms'] ?? 0),
            'status' => sanitize_key($entry['status'] ?? 'pending'),
            'error' => sanitize_text_field($entry['error'] ?? ''),
            'attempt' => (int) ($entry['attempt'] ?? 1),
            'max_attempts' => (int) ($entry['max_attempts'] ?? 1),
            'timestamp' => current_time('mysql'),
            'timestamp_gmt' => current_time('mysql', true),
            'user_id' => (int) ($entry['user_id'] ?? get_current_user_id()),
        ];

        // Prepend new entry (newest first)
        array_unshift($logs, $log_entry);

        // Enforce retention limit
        $retention = self::get_retention_limit();
        if (count($logs) > $retention) {
            $logs = array_slice($logs, 0, $retention);
        }

        update_option(self::OPTION_KEY, $logs);
    }

    /**
     * Log an outgoing webhook attempt.
     *
     * @param array  $webhook  Webhook configuration.
     * @param string $event    Event that triggered the webhook.
     * @param array  $payload  Request payload.
     * @param array  $response Response data from send attempt.
     * @return void
     */
    public static function log_outgoing(array $webhook, string $event, array $payload, array $response): void {
        self::log([
            'direction' => 'outgoing',
            'webhook_id' => $webhook['id'] ?? '',
            'webhook_name' => $webhook['name'] ?? '',
            'event' => $event,
            'url' => $webhook['url'] ?? '',
            'method' => $webhook['method'] ?? 'POST',
            'request_payload' => $payload,
            'response_code' => $response['code'] ?? 0,
            'response_body' => $response['body'] ?? '',
            'duration_ms' => $response['duration_ms'] ?? 0,
            'status' => $response['success'] ? 'success' : ($response['will_retry'] ? 'retrying' : 'failed'),
            'error' => $response['error'] ?? '',
            'attempt' => $response['attempt'] ?? 1,
            'max_attempts' => $webhook['retries'] ?? 1,
        ]);
    }

    /**
     * Log an incoming webhook request.
     *
     * @param string $action    Action requested.
     * @param array  $params    Request parameters.
     * @param array  $result    Result of the action.
     * @param int    $duration  Duration in milliseconds.
     * @return void
     */
    public static function log_incoming(string $action, array $params, array $result, int $duration): void {
        self::log([
            'direction' => 'incoming',
            'webhook_id' => '',
            'webhook_name' => 'External Request',
            'event' => $action,
            'url' => rest_url('wpe-rm/v1/webhook/incoming'),
            'method' => 'POST',
            'request_payload' => ['action' => $action, 'params' => $params],
            'response_code' => $result['success'] ? 200 : 400,
            'response_body' => wp_json_encode($result),
            'duration_ms' => $duration,
            'status' => $result['success'] ? 'success' : 'failed',
            'error' => $result['error'] ?? '',
            'user_id' => 0, // External request
        ]);
    }

    /**
     * Get all webhook logs.
     *
     * @return array Array of log entries.
     */
    public static function get_logs(): array {
        return get_option(self::OPTION_KEY, []);
    }

    /**
     * Get filtered logs.
     *
     * @param string|null $direction Filter by direction (outgoing/incoming).
     * @param string|null $status    Filter by status (success/failed/retrying).
     * @param string|null $search    Search in event, url, webhook_name.
     * @return array Filtered log entries.
     */
    public static function get_filtered_logs(?string $direction = null, ?string $status = null, ?string $search = null): array {
        $logs = self::get_logs();
        $filtered = [];

        foreach ($logs as $log) {
            // Direction filter
            if ($direction && $log['direction'] !== $direction) {
                continue;
            }

            // Status filter
            if ($status && $log['status'] !== $status) {
                continue;
            }

            // Search filter
            if ($search) {
                $search_lower = strtolower($search);
                $match = false;

                if (stripos($log['event'] ?? '', $search_lower) !== false) {
                    $match = true;
                } elseif (stripos($log['url'] ?? '', $search_lower) !== false) {
                    $match = true;
                } elseif (stripos($log['webhook_name'] ?? '', $search_lower) !== false) {
                    $match = true;
                } elseif (stripos($log['error'] ?? '', $search_lower) !== false) {
                    $match = true;
                }

                if (!$match) {
                    continue;
                }
            }

            $filtered[] = $log;
        }

        return $filtered;
    }

    /**
     * Clear all webhook logs.
     *
     * @return bool True on success.
     */
    public static function clear_logs(): bool {
        return delete_option(self::OPTION_KEY);
    }

    /**
     * Get unique statuses from logs.
     *
     * @return array Array of status values.
     */
    public static function get_statuses(): array {
        $logs = self::get_logs();
        $statuses = [];

        foreach ($logs as $log) {
            if (!empty($log['status']) && !in_array($log['status'], $statuses, true)) {
                $statuses[] = $log['status'];
            }
        }

        sort($statuses);
        return $statuses;
    }

    /**
     * Get log retention limit.
     * Uses the same log_retention setting as the main activity log.
     *
     * @return int Retention limit.
     */
    private static function get_retention_limit(): int {
        $settings = get_option('wpe_rm_settings', []);
        return (int) ($settings['log_retention'] ?? self::DEFAULT_RETENTION);
    }

    /**
     * Truncate response body to prevent storage bloat.
     *
     * @param string $response Response body.
     * @param int    $max_length Maximum length (default 2000).
     * @return string Truncated response.
     */
    private static function truncate_response(string $response, int $max_length = 2000): string {
        if (strlen($response) <= $max_length) {
            return $response;
        }

        return substr($response, 0, $max_length) . '... (truncated)';
    }

    /**
     * Get log statistics.
     *
     * @return array Statistics array.
     */
    public static function get_stats(): array {
        $logs = self::get_logs();

        $stats = [
            'total' => count($logs),
            'outgoing' => 0,
            'incoming' => 0,
            'success' => 0,
            'failed' => 0,
            'retrying' => 0,
        ];

        foreach ($logs as $log) {
            if ($log['direction'] === 'outgoing') {
                $stats['outgoing']++;
            } else {
                $stats['incoming']++;
            }

            switch ($log['status']) {
                case 'success':
                    $stats['success']++;
                    break;
                case 'failed':
                    $stats['failed']++;
                    break;
                case 'retrying':
                    $stats['retrying']++;
                    break;
            }
        }

        return $stats;
    }
}
