<?php
/**
 * Webhook Manager
 *
 * Handles CRUD operations for outgoing webhook configurations.
 *
 * @package WP_Easy\RoleManager\Webhooks
 */

namespace WP_Easy\RoleManager\Webhooks;

defined('ABSPATH') || exit;

/**
 * Manager class for webhook configuration.
 */
final class Manager {

    /**
     * Option key for storing webhooks.
     */
    private const OPTION_KEY = 'wpe_rm_webhooks_outgoing';

    /**
     * Available webhook events.
     */
    private const EVENTS = [
        'role:created' => 'Role Created',
        'role:updated' => 'Role Updated',
        'role:deleted' => 'Role Deleted',
        'role:enabled' => 'Role Enabled',
        'role:disabled' => 'Role Disabled',
        'capability:added' => 'Capability Added',
        'capability:removed' => 'Capability Removed',
        'capability:toggled' => 'Capability Toggled',
        'user:roles_updated' => 'User Roles Updated',
        'import:completed' => 'Import Completed',
        'settings:updated' => 'Settings Updated',
    ];

    /**
     * Get all webhooks.
     *
     * @return array Array of webhook configurations.
     */
    public static function get_webhooks(): array {
        return get_option(self::OPTION_KEY, []);
    }

    /**
     * Get a single webhook by ID.
     *
     * @param string $id Webhook ID.
     * @return array|null Webhook config or null if not found.
     */
    public static function get_webhook(string $id): ?array {
        $webhooks = self::get_webhooks();
        return $webhooks[$id] ?? null;
    }

    /**
     * Create a new webhook.
     *
     * @param array $data Webhook data.
     * @return array Created webhook with ID.
     */
    public static function create_webhook(array $data): array {
        $webhooks = self::get_webhooks();

        $id = 'whk_' . bin2hex(random_bytes(8));

        $webhook = [
            'id' => $id,
            'name' => sanitize_text_field($data['name'] ?? 'Untitled Webhook'),
            'url' => esc_url_raw($data['url'] ?? ''),
            'secret' => $data['secret'] ?? self::generate_secret(),
            'events' => self::sanitize_events($data['events'] ?? []),
            'method' => in_array(strtoupper($data['method'] ?? 'POST'), ['POST', 'GET'], true) ? strtoupper($data['method']) : 'POST',
            'headers' => self::sanitize_headers($data['headers'] ?? []),
            'retries' => self::sanitize_retries($data['retries'] ?? 3),
            'enabled' => (bool) ($data['enabled'] ?? true),
            'created' => current_time('mysql'),
            'last_triggered' => null,
        ];

        $webhooks[$id] = $webhook;
        update_option(self::OPTION_KEY, $webhooks);

        return $webhook;
    }

    /**
     * Update an existing webhook.
     *
     * @param string $id   Webhook ID.
     * @param array  $data Updated data.
     * @return array|null Updated webhook or null if not found.
     */
    public static function update_webhook(string $id, array $data): ?array {
        $webhooks = self::get_webhooks();

        if (!isset($webhooks[$id])) {
            return null;
        }

        $webhook = $webhooks[$id];

        // Update allowed fields
        if (isset($data['name'])) {
            $webhook['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['url'])) {
            $webhook['url'] = esc_url_raw($data['url']);
        }
        if (isset($data['secret'])) {
            $webhook['secret'] = sanitize_text_field($data['secret']);
        }
        if (isset($data['events'])) {
            $webhook['events'] = self::sanitize_events($data['events']);
        }
        if (isset($data['method'])) {
            $webhook['method'] = in_array(strtoupper($data['method']), ['POST', 'GET'], true) ? strtoupper($data['method']) : 'POST';
        }
        if (isset($data['headers'])) {
            $webhook['headers'] = self::sanitize_headers($data['headers']);
        }
        if (isset($data['retries'])) {
            $webhook['retries'] = self::sanitize_retries($data['retries']);
        }
        if (isset($data['enabled'])) {
            $webhook['enabled'] = (bool) $data['enabled'];
        }

        $webhooks[$id] = $webhook;
        update_option(self::OPTION_KEY, $webhooks);

        return $webhook;
    }

    /**
     * Delete a webhook.
     *
     * @param string $id Webhook ID.
     * @return bool True if deleted, false if not found.
     */
    public static function delete_webhook(string $id): bool {
        $webhooks = self::get_webhooks();

        if (!isset($webhooks[$id])) {
            return false;
        }

        unset($webhooks[$id]);
        update_option(self::OPTION_KEY, $webhooks);

        return true;
    }

    /**
     * Generate a secure secret for webhook signing.
     *
     * @return string 32-character hex secret.
     */
    public static function generate_secret(): string {
        return bin2hex(random_bytes(16));
    }

    /**
     * Get available events for webhooks.
     *
     * @return array Associative array of event_key => label.
     */
    public static function get_available_events(): array {
        return self::EVENTS;
    }

    /**
     * Update last triggered timestamp for a webhook.
     *
     * @param string $id Webhook ID.
     * @return void
     */
    public static function update_last_triggered(string $id): void {
        $webhooks = self::get_webhooks();

        if (isset($webhooks[$id])) {
            $webhooks[$id]['last_triggered'] = current_time('mysql');
            update_option(self::OPTION_KEY, $webhooks);
        }
    }

    /**
     * Get webhooks subscribed to a specific event.
     *
     * @param string $event Event name.
     * @return array Array of enabled webhooks subscribed to the event.
     */
    public static function get_webhooks_for_event(string $event): array {
        $webhooks = self::get_webhooks();
        $matching = [];

        foreach ($webhooks as $webhook) {
            if ($webhook['enabled'] && in_array($event, $webhook['events'], true)) {
                $matching[] = $webhook;
            }
        }

        return $matching;
    }

    /**
     * Sanitize events array.
     *
     * @param array $events Raw events.
     * @return array Sanitized events.
     */
    private static function sanitize_events(array $events): array {
        $valid_events = array_keys(self::EVENTS);
        return array_values(array_intersect($events, $valid_events));
    }

    /**
     * Sanitize headers array.
     *
     * @param array $headers Raw headers.
     * @return array Sanitized headers.
     */
    private static function sanitize_headers(array $headers): array {
        $sanitized = [];

        foreach ($headers as $header) {
            if (isset($header['key'], $header['value']) && !empty($header['key'])) {
                $sanitized[] = [
                    'key' => sanitize_text_field($header['key']),
                    'value' => sanitize_text_field($header['value']),
                ];
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize retry count.
     *
     * @param mixed $retries Raw retries value.
     * @return int Sanitized retries (1-5).
     */
    private static function sanitize_retries($retries): int {
        $retries = (int) $retries;
        return max(1, min(5, $retries));
    }

    /**
     * Validate a webhook URL.
     *
     * @param string $url URL to validate.
     * @return bool|string True if valid, error message if invalid.
     */
    public static function validate_url(string $url): bool|string {
        if (empty($url)) {
            return __('URL is required.', WPE_RM_TEXTDOMAIN);
        }

        $parsed = wp_parse_url($url);

        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return __('Invalid URL format.', WPE_RM_TEXTDOMAIN);
        }

        // Only allow https (or http for local dev)
        if (!in_array($parsed['scheme'], ['https', 'http'], true)) {
            return __('URL must use http or https.', WPE_RM_TEXTDOMAIN);
        }

        // Block localhost and private IPs in production
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            $host = strtolower($parsed['host']);

            if ($host === 'localhost' || $host === '127.0.0.1') {
                return __('Localhost URLs are not allowed.', WPE_RM_TEXTDOMAIN);
            }

            // Check for private IP ranges
            $ip = gethostbyname($host);
            if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return __('Private IP addresses are not allowed.', WPE_RM_TEXTDOMAIN);
            }
        }

        return true;
    }
}
