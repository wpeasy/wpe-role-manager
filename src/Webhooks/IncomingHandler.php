<?php
/**
 * Incoming Webhook Handler
 *
 * Handles incoming webhook requests from external automation systems.
 *
 * @package WP_Easy\RoleManager\Webhooks
 */

namespace WP_Easy\RoleManager\Webhooks;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Easy\RoleManager\Helpers\RoleManager;
use WP_Easy\RoleManager\Helpers\CapabilityManager;

defined('ABSPATH') || exit;

/**
 * Handler class for incoming webhooks.
 */
final class IncomingHandler {

    /**
     * Default rate limit: requests per minute per IP.
     */
    private const DEFAULT_RATE_LIMIT = 100;

    /**
     * Rate limit window in seconds.
     */
    private const RATE_LIMIT_WINDOW = 60;

    /**
     * Transient prefix for rate limiting.
     */
    private const RATE_LIMIT_PREFIX = 'wpe_rm_webhook_rate_';

    /**
     * Maximum payload size in bytes (64KB).
     */
    private const MAX_PAYLOAD_SIZE = 65536;

    /**
     * Allowed incoming actions.
     */
    private const ALLOWED_ACTIONS = [
        'create_role',
        'delete_role',
        'enable_role',
        'disable_role',
        'add_capability',
        'remove_capability',
        'update_user_roles',
    ];

    /**
     * Handle incoming webhook request.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error Response or error.
     */
    public static function handle_request(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $start_time = microtime(true);

        // Check payload size
        $body = $request->get_body();
        if (strlen($body) > self::MAX_PAYLOAD_SIZE) {
            return new WP_Error(
                'payload_too_large',
                __('Payload exceeds maximum size of 64KB.', WPE_RM_TEXTDOMAIN),
                ['status' => 413]
            );
        }

        // Rate limiting
        $rate_check = self::check_rate_limit();
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }

        // Get action and params
        $action = $request->get_param('action');
        $params = $request->get_param('params') ?? [];

        // Validate action
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            $result = [
                'success' => false,
                'error' => sprintf(
                    __('Invalid action: %s. Allowed: %s', WPE_RM_TEXTDOMAIN),
                    $action,
                    implode(', ', self::ALLOWED_ACTIONS)
                ),
            ];

            $duration = (int) ((microtime(true) - $start_time) * 1000);
            Logger::log_incoming($action ?? 'unknown', $params, $result, $duration);

            return new WP_Error(
                'invalid_action',
                $result['error'],
                ['status' => 400]
            );
        }

        // Execute action
        $result = self::execute_action($action, $params);

        // Log the request
        $duration = (int) ((microtime(true) - $start_time) * 1000);
        Logger::log_incoming($action, $params, $result, $duration);

        if (!$result['success']) {
            return new WP_Error(
                'action_failed',
                $result['error'],
                ['status' => 400]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => $result['message'] ?? 'Action completed successfully.',
            'data' => $result['data'] ?? null,
        ], 200);
    }

    /**
     * Check permissions for incoming webhook.
     * Uses WP Application Passwords for authentication.
     *
     * @param WP_REST_Request $request REST request.
     * @return bool|WP_Error True if authorized, WP_Error otherwise.
     */
    public static function check_permissions(WP_REST_Request $request): bool|WP_Error {
        // Check if user is authenticated (via Application Password)
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return new WP_Error(
                'rest_forbidden',
                __('Authentication required. Use WordPress Application Passwords.', WPE_RM_TEXTDOMAIN),
                ['status' => 401]
            );
        }

        // Check if user has manage_options capability
        if (!user_can($user, 'manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Insufficient permissions. User must have manage_options capability.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Execute an incoming webhook action.
     *
     * @param string $action Action name.
     * @param array  $params Action parameters.
     * @return array Result with success, message, data, error keys.
     */
    public static function execute_action(string $action, array $params): array {
        switch ($action) {
            case 'create_role':
                return self::action_create_role($params);

            case 'delete_role':
                return self::action_delete_role($params);

            case 'enable_role':
                return self::action_enable_role($params);

            case 'disable_role':
                return self::action_disable_role($params);

            case 'add_capability':
                return self::action_add_capability($params);

            case 'remove_capability':
                return self::action_remove_capability($params);

            case 'update_user_roles':
                return self::action_update_user_roles($params);

            default:
                return [
                    'success' => false,
                    'error' => __('Unknown action.', WPE_RM_TEXTDOMAIN),
                ];
        }
    }

    /**
     * Create a new role.
     *
     * @param array $params Parameters: slug, name, copy_from (optional).
     * @return array Result.
     */
    private static function action_create_role(array $params): array {
        $slug = sanitize_key($params['slug'] ?? '');
        $name = sanitize_text_field($params['name'] ?? '');
        $copy_from = sanitize_key($params['copy_from'] ?? '');

        if (empty($slug) || empty($name)) {
            return [
                'success' => false,
                'error' => __('Role slug and name are required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        // Check if role exists
        if (wp_roles()->is_role($slug)) {
            return [
                'success' => false,
                'error' => sprintf(__('Role "%s" already exists.', WPE_RM_TEXTDOMAIN), $slug),
            ];
        }

        // Get capabilities from source role
        $capabilities = [];
        if (!empty($copy_from) && wp_roles()->is_role($copy_from)) {
            $source_role = get_role($copy_from);
            $capabilities = $source_role ? $source_role->capabilities : [];
        }

        // Create the role
        $role = add_role($slug, $name, $capabilities);

        if (!$role) {
            return [
                'success' => false,
                'error' => __('Failed to create role.', WPE_RM_TEXTDOMAIN),
            ];
        }

        // Track as created by plugin
        RoleManager::track_created_role($slug);

        return [
            'success' => true,
            'message' => sprintf(__('Role "%s" created successfully.', WPE_RM_TEXTDOMAIN), $name),
            'data' => [
                'slug' => $slug,
                'name' => $name,
                'capabilities' => array_keys(array_filter($role->capabilities)),
            ],
        ];
    }

    /**
     * Delete a role.
     *
     * @param array $params Parameters: slug.
     * @return array Result.
     */
    private static function action_delete_role(array $params): array {
        $slug = sanitize_key($params['slug'] ?? '');

        if (empty($slug)) {
            return [
                'success' => false,
                'error' => __('Role slug is required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        // Check if role exists
        if (!wp_roles()->is_role($slug)) {
            return [
                'success' => false,
                'error' => sprintf(__('Role "%s" does not exist.', WPE_RM_TEXTDOMAIN), $slug),
            ];
        }

        // Check if core role
        if (RoleManager::is_core_role($slug)) {
            return [
                'success' => false,
                'error' => __('Cannot delete core WordPress roles.', WPE_RM_TEXTDOMAIN),
            ];
        }

        // Delete the role
        remove_role($slug);

        // Untrack from plugin
        RoleManager::untrack_created_role($slug);

        return [
            'success' => true,
            'message' => sprintf(__('Role "%s" deleted successfully.', WPE_RM_TEXTDOMAIN), $slug),
        ];
    }

    /**
     * Enable a disabled role.
     *
     * @param array $params Parameters: slug.
     * @return array Result.
     */
    private static function action_enable_role(array $params): array {
        $slug = sanitize_key($params['slug'] ?? '');

        if (empty($slug)) {
            return [
                'success' => false,
                'error' => __('Role slug is required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        if (!wp_roles()->is_role($slug)) {
            return [
                'success' => false,
                'error' => sprintf(__('Role "%s" does not exist.', WPE_RM_TEXTDOMAIN), $slug),
            ];
        }

        RoleManager::enable_role($slug);

        return [
            'success' => true,
            'message' => sprintf(__('Role "%s" enabled successfully.', WPE_RM_TEXTDOMAIN), $slug),
        ];
    }

    /**
     * Disable a role.
     *
     * @param array $params Parameters: slug.
     * @return array Result.
     */
    private static function action_disable_role(array $params): array {
        $slug = sanitize_key($params['slug'] ?? '');

        if (empty($slug)) {
            return [
                'success' => false,
                'error' => __('Role slug is required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        if (!wp_roles()->is_role($slug)) {
            return [
                'success' => false,
                'error' => sprintf(__('Role "%s" does not exist.', WPE_RM_TEXTDOMAIN), $slug),
            ];
        }

        if (RoleManager::is_core_role($slug)) {
            return [
                'success' => false,
                'error' => __('Cannot disable core WordPress roles.', WPE_RM_TEXTDOMAIN),
            ];
        }

        RoleManager::disable_role($slug);

        return [
            'success' => true,
            'message' => sprintf(__('Role "%s" disabled successfully.', WPE_RM_TEXTDOMAIN), $slug),
        ];
    }

    /**
     * Add capability to a role.
     *
     * @param array $params Parameters: role, capability, grant (optional, default true).
     * @return array Result.
     */
    private static function action_add_capability(array $params): array {
        $role_slug = sanitize_key($params['role'] ?? '');
        $capability = sanitize_key($params['capability'] ?? '');
        $grant = (bool) ($params['grant'] ?? true);

        if (empty($role_slug) || empty($capability)) {
            return [
                'success' => false,
                'error' => __('Role and capability are required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        $role = get_role($role_slug);
        if (!$role) {
            return [
                'success' => false,
                'error' => sprintf(__('Role "%s" does not exist.', WPE_RM_TEXTDOMAIN), $role_slug),
            ];
        }

        // Check if dangerous capability
        if (CapabilityManager::is_dangerous_capability($capability)) {
            $settings = get_option('wpe_rm_settings', []);
            if (empty($settings['allow_dangerous_caps'])) {
                return [
                    'success' => false,
                    'error' => sprintf(
                        __('Capability "%s" is dangerous. Enable "Allow dangerous capabilities" in settings.', WPE_RM_TEXTDOMAIN),
                        $capability
                    ),
                ];
            }
        }

        $role->add_cap($capability, $grant);
        CapabilityManager::track_managed_cap($role_slug, $capability);

        return [
            'success' => true,
            'message' => sprintf(
                __('Capability "%s" added to role "%s".', WPE_RM_TEXTDOMAIN),
                $capability,
                $role_slug
            ),
        ];
    }

    /**
     * Remove capability from a role.
     *
     * @param array $params Parameters: role, capability.
     * @return array Result.
     */
    private static function action_remove_capability(array $params): array {
        $role_slug = sanitize_key($params['role'] ?? '');
        $capability = sanitize_key($params['capability'] ?? '');

        if (empty($role_slug) || empty($capability)) {
            return [
                'success' => false,
                'error' => __('Role and capability are required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        $role = get_role($role_slug);
        if (!$role) {
            return [
                'success' => false,
                'error' => sprintf(__('Role "%s" does not exist.', WPE_RM_TEXTDOMAIN), $role_slug),
            ];
        }

        $role->remove_cap($capability);
        CapabilityManager::untrack_managed_cap($role_slug, $capability);

        return [
            'success' => true,
            'message' => sprintf(
                __('Capability "%s" removed from role "%s".', WPE_RM_TEXTDOMAIN),
                $capability,
                $role_slug
            ),
        ];
    }

    /**
     * Update user roles.
     *
     * @param array $params Parameters: user_id, roles[].
     * @return array Result.
     */
    private static function action_update_user_roles(array $params): array {
        $user_id = (int) ($params['user_id'] ?? 0);
        $roles = $params['roles'] ?? [];

        if (empty($user_id)) {
            return [
                'success' => false,
                'error' => __('User ID is required.', WPE_RM_TEXTDOMAIN),
            ];
        }

        if (!is_array($roles)) {
            return [
                'success' => false,
                'error' => __('Roles must be an array.', WPE_RM_TEXTDOMAIN),
            ];
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [
                'success' => false,
                'error' => sprintf(__('User ID %d does not exist.', WPE_RM_TEXTDOMAIN), $user_id),
            ];
        }

        // Validate all roles exist
        foreach ($roles as $role_slug) {
            if (!wp_roles()->is_role($role_slug)) {
                return [
                    'success' => false,
                    'error' => sprintf(__('Role "%s" does not exist.', WPE_RM_TEXTDOMAIN), $role_slug),
                ];
            }
        }

        $old_roles = $user->roles;

        // Remove all existing roles
        foreach ($old_roles as $old_role) {
            $user->remove_role($old_role);
        }

        // Add new roles
        foreach ($roles as $new_role) {
            $user->add_role($new_role);
        }

        return [
            'success' => true,
            'message' => sprintf(
                __('User %d roles updated successfully.', WPE_RM_TEXTDOMAIN),
                $user_id
            ),
            'data' => [
                'user_id' => $user_id,
                'previous_roles' => $old_roles,
                'new_roles' => $roles,
            ],
        ];
    }

    /**
     * Get the configured rate limit.
     *
     * @return int Rate limit per minute.
     */
    private static function get_rate_limit(): int {
        $settings = get_option('wpe_rm_settings', []);
        return absint($settings['webhook_rate_limit'] ?? self::DEFAULT_RATE_LIMIT);
    }

    /**
     * Check rate limit for incoming requests.
     *
     * @return bool|WP_Error True if allowed, WP_Error if rate limited.
     */
    private static function check_rate_limit(): bool|WP_Error {
        $ip = self::get_client_ip();
        $key = self::RATE_LIMIT_PREFIX . md5($ip);
        $rate_limit = self::get_rate_limit();

        $current = get_transient($key);

        if ($current === false) {
            set_transient($key, 1, self::RATE_LIMIT_WINDOW);
            return true;
        }

        if ($current >= $rate_limit) {
            return new WP_Error(
                'rate_limited',
                sprintf(
                    __('Rate limit exceeded. Maximum %d requests per minute.', WPE_RM_TEXTDOMAIN),
                    $rate_limit
                ),
                ['status' => 429]
            );
        }

        set_transient($key, $current + 1, self::RATE_LIMIT_WINDOW);
        return true;
    }

    /**
     * Get client IP address.
     *
     * @return string Client IP.
     */
    private static function get_client_ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get allowed actions with descriptions.
     *
     * @return array Action descriptions.
     */
    public static function get_allowed_actions(): array {
        return [
            'create_role' => [
                'description' => __('Create a new role', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'slug' => ['type' => 'string', 'required' => true],
                    'name' => ['type' => 'string', 'required' => true],
                    'copy_from' => ['type' => 'string', 'required' => false],
                ],
            ],
            'delete_role' => [
                'description' => __('Delete a role', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'slug' => ['type' => 'string', 'required' => true],
                ],
            ],
            'enable_role' => [
                'description' => __('Enable a disabled role', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'slug' => ['type' => 'string', 'required' => true],
                ],
            ],
            'disable_role' => [
                'description' => __('Disable a role', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'slug' => ['type' => 'string', 'required' => true],
                ],
            ],
            'add_capability' => [
                'description' => __('Add capability to a role', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'role' => ['type' => 'string', 'required' => true],
                    'capability' => ['type' => 'string', 'required' => true],
                    'grant' => ['type' => 'boolean', 'required' => false, 'default' => true],
                ],
            ],
            'remove_capability' => [
                'description' => __('Remove capability from a role', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'role' => ['type' => 'string', 'required' => true],
                    'capability' => ['type' => 'string', 'required' => true],
                ],
            ],
            'update_user_roles' => [
                'description' => __('Update user\'s roles', WPE_RM_TEXTDOMAIN),
                'params' => [
                    'user_id' => ['type' => 'integer', 'required' => true],
                    'roles' => ['type' => 'array', 'required' => true],
                ],
            ],
        ];
    }
}
