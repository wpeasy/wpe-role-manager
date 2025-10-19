<?php
/**
 * Activity Logger
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

/**
 * Log activity for roles and capabilities management.
 *
 * @since 0.0.1-alpha
 */
final class Logger {
    /**
     * Maximum number of logs to keep.
     *
     * @var int
     */
    private const MAX_LOGS = 500;

    /**
     * Log option name.
     *
     * @var string
     */
    private const OPTION_NAME = 'wpe_rm_activity_logs';

    /**
     * Log an activity.
     *
     * @param string $action Action type (e.g., 'Role Created', 'Capability Added').
     * @param string $details Detailed description of the action.
     * @return void
     */
    public static function log(string $action, string $details): void {
        $logs = self::get_logs();

        // Get current user info
        $current_user = wp_get_current_user();
        $username = $current_user->user_login ?? 'system';

        // Create log entry
        $log_entry = [
            'id' => uniqid('log_', true),
            'action' => sanitize_text_field($action),
            'details' => sanitize_text_field($details),
            'user' => $username,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'timestamp_gmt' => current_time('mysql', true),
        ];

        // Add to beginning of array (newest first)
        array_unshift($logs, $log_entry);

        // Keep only the most recent logs
        if (count($logs) > self::MAX_LOGS) {
            $logs = array_slice($logs, 0, self::MAX_LOGS);
        }

        // Save logs
        update_option(self::OPTION_NAME, $logs);
    }

    /**
     * Get all logs.
     *
     * @return array
     */
    public static function get_logs(): array {
        return get_option(self::OPTION_NAME, []);
    }

    /**
     * Clear all logs.
     *
     * @return bool
     */
    public static function clear_logs(): bool {
        return delete_option(self::OPTION_NAME);
    }

    /**
     * Get filtered logs.
     *
     * @param string|null $action_filter Filter by action type.
     * @param string|null $details_filter Filter by details (search).
     * @return array
     */
    public static function get_filtered_logs(?string $action_filter = null, ?string $details_filter = null): array {
        $logs = self::get_logs();

        // Filter by action
        if (!empty($action_filter)) {
            $logs = array_filter($logs, function($log) use ($action_filter) {
                return $log['action'] === $action_filter;
            });
        }

        // Filter by details (case-insensitive search)
        if (!empty($details_filter)) {
            $search = strtolower($details_filter);
            $logs = array_filter($logs, function($log) use ($search) {
                return strpos(strtolower($log['details']), $search) !== false ||
                       strpos(strtolower($log['action']), $search) !== false ||
                       strpos(strtolower($log['user']), $search) !== false;
            });
        }

        return array_values($logs); // Re-index array
    }

    /**
     * Get unique action types from logs.
     *
     * @return array
     */
    public static function get_action_types(): array {
        $logs = self::get_logs();
        $actions = array_unique(array_column($logs, 'action'));
        sort($actions);
        return $actions;
    }
}
