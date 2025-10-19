<?php
/**
 * REST API Routes
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\REST;

defined('ABSPATH') || exit;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Easy\RoleManager\Helpers\RoleManager;
use WP_Easy\RoleManager\Helpers\CapabilityManager;
use WP_Easy\RoleManager\Helpers\UserManager;
use WP_Easy\RoleManager\Helpers\Logger;

/**
 * Register and handle REST API routes.
 *
 * @since 0.0.1-alpha
 */
final class Routes {
    /**
     * REST API namespace.
     *
     * @var string
     */
    private const NAMESPACE = 'wpe-rm/v1';

    /**
     * Register all REST API routes.
     *
     * @return void
     */
    public static function register(): void {
        // Role endpoints
        register_rest_route(
            self::NAMESPACE,
            '/roles',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_roles'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [self::class, 'create_role'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/roles/(?P<role>[a-zA-Z0-9_-]+)',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [self::class, 'update_role'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [self::class, 'delete_role'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
            ]
        );

        // Capability endpoints
        register_rest_route(
            self::NAMESPACE,
            '/capabilities',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_capabilities'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/capabilities/matrix',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_capability_matrix'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/roles/(?P<role>[a-zA-Z0-9_-]+)/caps',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [self::class, 'add_capability'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/roles/(?P<role>[a-zA-Z0-9_-]+)/caps/(?P<cap>[a-zA-Z0-9_-]+)',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [self::class, 'toggle_capability'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [self::class, 'remove_capability'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
            ]
        );

        // User endpoints
        register_rest_route(
            self::NAMESPACE,
            '/users',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_users'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/users/(?P<id>\d+)/roles',
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [self::class, 'update_user_roles'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/users/(?P<id>\d+)/effective-caps',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_user_effective_caps'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/users/(?P<id>\d+)/can/(?P<capability>[a-zA-Z0-9_-]+)',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'test_user_capability'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        // Import/Export endpoints
        register_rest_route(
            self::NAMESPACE,
            '/export',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'export_roles'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/import',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [self::class, 'import_roles'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        // Logs endpoints
        register_rest_route(
            self::NAMESPACE,
            '/logs',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_logs'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/logs',
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [self::class, 'clear_logs'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/logs/actions',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_log_actions'],
                'permission_callback' => [self::class, 'check_permissions'],
            ]
        );

        // Settings endpoints
        register_rest_route(
            self::NAMESPACE,
            '/settings',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_settings'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [self::class, 'update_settings'],
                    'permission_callback' => [self::class, 'check_permissions'],
                ],
            ]
        );
    }

    /**
     * Check if user has permission to access REST endpoints.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public static function check_permissions(WP_REST_Request $request) {
        // Check nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid nonce.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        // Require administrator capability for all role/capability operations
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be an administrator to access this resource.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        // Check same-origin with proper URL parsing
        $referer = $request->get_header('referer');
        if (empty($referer)) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid request origin.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        $home_url = home_url();
        $referer_parsed = wp_parse_url($referer);
        $home_parsed = wp_parse_url($home_url);

        if (!isset($referer_parsed['host']) ||
            !isset($home_parsed['host']) ||
            $referer_parsed['host'] !== $home_parsed['host']) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid request origin.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Get all roles.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function get_roles(WP_REST_Request $request): WP_REST_Response {
        $roles = RoleManager::get_all_roles();

        return new WP_REST_Response([
            'roles' => $roles,
            'total' => count($roles),
        ], 200);
    }

    /**
     * Create a new role.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function create_role(WP_REST_Request $request) {
        $params = $request->get_json_params();

        // Validate required fields
        if (empty($params['slug']) || empty($params['name'])) {
            return new WP_Error(
                'missing_fields',
                __('Role slug and name are required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        $slug = sanitize_key($params['slug']);
        $name = sanitize_text_field($params['name']);
        $copy_from = !empty($params['copyFrom']) ? sanitize_key($params['copyFrom']) : '';

        $role = RoleManager::create_role($slug, $name, $copy_from);

        if (!$role) {
            return new WP_Error(
                'role_creation_failed',
                __('Failed to create role. Role may already exist or slug is invalid.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Log the action
        $log_details = $copy_from
            ? sprintf('Created role "%s" (slug: %s) based on "%s"', $name, $slug, $copy_from)
            : sprintf('Created role "%s" (slug: %s)', $name, $slug);
        Logger::log('Role Created', $log_details);

        return new WP_REST_Response([
            'success' => true,
            'role' => $role,
            'message' => sprintf(__('Role "%s" created successfully.', WPE_RM_TEXTDOMAIN), $name),
        ], 201);
    }

    /**
     * Update a role.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function update_role(WP_REST_Request $request) {
        $role_slug = sanitize_key($request->get_param('role'));
        $params = $request->get_json_params();

        if (empty($role_slug)) {
            return new WP_Error(
                'missing_role',
                __('Role slug is required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        $success = RoleManager::update_role($role_slug, $params);

        if (!$success) {
            return new WP_Error(
                'role_update_failed',
                __('Failed to update role. Role may not exist or is a core role.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Log the action
        if (isset($params['disabled'])) {
            $action = $params['disabled'] ? 'Role Disabled' : 'Role Enabled';
            Logger::log($action, sprintf('Role "%s" %s', $role_slug, $params['disabled'] ? 'disabled' : 'enabled'));
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Role updated successfully.', WPE_RM_TEXTDOMAIN),
        ], 200);
    }

    /**
     * Delete a role.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function delete_role(WP_REST_Request $request) {
        $role_slug = sanitize_key($request->get_param('role'));
        $force = $request->get_param('force') === 'true' || $request->get_param('force') === true;

        if (empty($role_slug)) {
            return new WP_Error(
                'missing_role',
                __('Role slug is required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Check if users exist with this role
        $user_count = RoleManager::count_users_in_role($role_slug);
        if ($user_count > 0 && !$force) {
            return new WP_Error(
                'role_has_users',
                sprintf(
                    __('Cannot delete role. %d user(s) currently have this role. Use force=true to delete anyway.', WPE_RM_TEXTDOMAIN),
                    $user_count
                ),
                ['status' => 400, 'user_count' => $user_count]
            );
        }

        $success = RoleManager::delete_role($role_slug, $force);

        if (!$success) {
            return new WP_Error(
                'role_deletion_failed',
                __('Failed to delete role. Role may not exist or is a core role.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Log the action
        $log_details = $force && $user_count > 0
            ? sprintf('Deleted role "%s" (forced deletion with %d user(s))', $role_slug, $user_count)
            : sprintf('Deleted role "%s"', $role_slug);
        Logger::log('Role Deleted', $log_details);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Role deleted successfully.', WPE_RM_TEXTDOMAIN),
        ], 200);
    }

    /**
     * Get all capabilities.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function get_capabilities(WP_REST_Request $request): WP_REST_Response {
        $capabilities = CapabilityManager::get_all_capabilities();

        return new WP_REST_Response([
            'capabilities' => $capabilities,
            'total' => count($capabilities),
        ], 200);
    }

    /**
     * Get capability matrix (roles × capabilities).
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function get_capability_matrix(WP_REST_Request $request): WP_REST_Response {
        $matrix = CapabilityManager::get_capability_matrix();

        return new WP_REST_Response([
            'matrix' => $matrix,
            'total' => count($matrix),
        ], 200);
    }

    /**
     * Add capability to a role.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function add_capability(WP_REST_Request $request) {
        $role_slug = sanitize_key($request->get_param('role'));
        $params = $request->get_json_params();

        if (empty($role_slug) || empty($params['capability'])) {
            return new WP_Error(
                'missing_fields',
                __('Role slug and capability name are required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        $capability = sanitize_key($params['capability']);
        $grant = isset($params['grant']) ? (bool) $params['grant'] : true;

        // Check if dangerous capability protection is enabled
        $settings = get_option('wpe_rm_settings', ['allow_core_cap_assignment' => false]);
        $allow_dangerous_caps = $settings['allow_core_cap_assignment'] ?? false;

        // Blacklist dangerous capabilities that allow code execution or security bypasses
        // Only enforce if the setting is disabled (default)
        if (!$allow_dangerous_caps) {
            $dangerous_caps = [
                'unfiltered_html',
                'unfiltered_upload',
                'edit_plugins',
                'edit_themes',
                'edit_files',
                'install_plugins',
                'install_themes',
                'update_core',
                'update_plugins',
                'update_themes',
                'delete_plugins',
                'delete_themes',
                'manage_options',
                'activate_plugins',
                'delete_users',
                'create_users',
                'promote_users',
                'edit_users',
                'list_users',
                'remove_users',
                'switch_themes',
                'edit_dashboard',
                'customize',
                'delete_site',
                'import',
                'export',
            ];

            if (in_array($capability, $dangerous_caps, true)) {
                return new WP_Error(
                    'dangerous_capability',
                    __('This capability cannot be added for security reasons. Enable "Allow assigning dangerous capabilities to roles" in Settings to override this protection.', WPE_RM_TEXTDOMAIN),
                    ['status' => 403]
                );
            }
        }

        $success = CapabilityManager::add_capability($role_slug, $capability, $grant);

        if (!$success) {
            return new WP_Error(
                'capability_add_failed',
                __('Failed to add capability. Role may not exist or is a core role.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Log the action
        $action_text = $grant ? 'granted' : 'denied';
        Logger::log('Capability Added', sprintf('Added capability "%s" to role "%s" (%s)', $capability, $role_slug, $action_text));

        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf(__('Capability "%s" added to role.', WPE_RM_TEXTDOMAIN), $capability),
        ], 200);
    }

    /**
     * Toggle capability state (grant/deny/unset).
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function toggle_capability(WP_REST_Request $request) {
        $role_slug = sanitize_key($request->get_param('role'));
        $capability = sanitize_key($request->get_param('cap'));
        $params = $request->get_json_params();
        $action = $params['action'] ?? 'grant'; // grant, deny, or unset

        if (empty($role_slug) || empty($capability)) {
            return new WP_Error(
                'missing_fields',
                __('Role slug and capability name are required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Check if dangerous capability protection is enabled
        $settings = get_option('wpe_rm_settings', ['allow_core_cap_assignment' => false]);
        $allow_dangerous_caps = $settings['allow_core_cap_assignment'] ?? false;

        // Prevent adding dangerous capabilities for security (unless setting is enabled)
        if (!$allow_dangerous_caps && $action !== 'unset') {
            $dangerous_caps = [
                'unfiltered_html',
                'unfiltered_upload',
                'edit_plugins',
                'edit_themes',
                'edit_files',
                'install_plugins',
                'install_themes',
                'update_core',
                'update_plugins',
                'update_themes',
                'delete_plugins',
                'delete_themes',
                'manage_options',
                'activate_plugins',
                'delete_users',
                'create_users',
                'promote_users',
                'edit_users',
                'list_users',
                'remove_users',
                'switch_themes',
                'edit_dashboard',
                'customize',
                'delete_site',
                'import',
                'export',
            ];

            if (in_array($capability, $dangerous_caps, true)) {
                return new WP_Error(
                    'dangerous_capability',
                    __('This capability cannot be added for security reasons. Enable "Allow assigning dangerous capabilities to roles" in Settings to override this protection.', WPE_RM_TEXTDOMAIN),
                    ['status' => 403]
                );
            }
        }

        if ($action === 'unset') {
            // Remove the capability completely
            $success = CapabilityManager::remove_capability($role_slug, $capability);
            if (!$success) {
                return new WP_Error(
                    'capability_toggle_failed',
                    __('Failed to unset capability. It may not have been added by this plugin.', WPE_RM_TEXTDOMAIN),
                    ['status' => 400]
                );
            }
            $message = sprintf(__('Capability "%s" unset from role.', WPE_RM_TEXTDOMAIN), $capability);
            Logger::log('Capability Toggled', sprintf('Unset capability "%s" from role "%s"', $capability, $role_slug));
        } else {
            // Grant (true) or deny (false)
            $grant = $action === 'grant';
            $success = CapabilityManager::add_capability($role_slug, $capability, $grant);
            if (!$success) {
                return new WP_Error(
                    'capability_toggle_failed',
                    __('Failed to toggle capability.', WPE_RM_TEXTDOMAIN),
                    ['status' => 400]
                );
            }
            $message = sprintf(
                __('Capability "%s" %s for role.', WPE_RM_TEXTDOMAIN),
                $capability,
                $grant ? 'granted' : 'denied'
            );
            Logger::log('Capability Toggled', sprintf('Toggled capability "%s" on role "%s" to %s', $capability, $role_slug, $action));
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    /**
     * Remove capability from a role.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function remove_capability(WP_REST_Request $request) {
        $role_slug = sanitize_key($request->get_param('role'));
        $capability = sanitize_key($request->get_param('cap'));

        if (empty($role_slug) || empty($capability)) {
            return new WP_Error(
                'missing_fields',
                __('Role slug and capability name are required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        $success = CapabilityManager::remove_capability($role_slug, $capability);

        if (!$success) {
            return new WP_Error(
                'capability_remove_failed',
                __('Failed to remove capability. It may not have been added by this plugin.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Log the action
        Logger::log('Capability Removed', sprintf('Removed capability "%s" from role "%s"', $capability, $role_slug));

        return new WP_REST_Response([
            'success' => true,
            'message' => sprintf(__('Capability "%s" removed from role.', WPE_RM_TEXTDOMAIN), $capability),
        ], 200);
    }

    /**
     * Get users with roles.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function get_users(WP_REST_Request $request): WP_REST_Response {
        $search = $request->get_param('search');
        $args = [
            'number' => $request->get_param('per_page') ?: 50,
            'paged' => $request->get_param('page') ?: 1,
        ];

        if (!empty($search)) {
            $users = UserManager::search_users($search, $args['number']);
        } else {
            $users = UserManager::get_users($args);
        }

        return new WP_REST_Response([
            'users' => $users,
            'total' => count($users),
        ], 200);
    }

    /**
     * Update user roles.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function update_user_roles(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $current_user_id = get_current_user_id();
        $params = $request->get_json_params();

        if (empty($user_id)) {
            return new WP_Error(
                'missing_user_id',
                __('User ID is required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Prevent self-modification
        if ($user_id === $current_user_id) {
            return new WP_Error(
                'cannot_modify_self',
                __('You cannot modify your own roles.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        // Require promote_users capability
        if (!current_user_can('promote_users')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to modify user roles.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        // Prevent modification of administrators by non-super-admins
        $target_user = get_userdata($user_id);
        if (!$target_user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', WPE_RM_TEXTDOMAIN),
                ['status' => 404]
            );
        }

        if (user_can($target_user, 'manage_options') && !current_user_can('manage_network')) {
            return new WP_Error(
                'cannot_modify_admin',
                __('You cannot modify administrator accounts.', WPE_RM_TEXTDOMAIN),
                ['status' => 403]
            );
        }

        if (!isset($params['roles']) || !is_array($params['roles'])) {
            return new WP_Error(
                'missing_roles',
                __('Roles array is required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Sanitize roles
        $roles = array_map('sanitize_key', $params['roles']);

        $success = UserManager::update_user_roles($user_id, $roles);

        if (!$success) {
            return new WP_Error(
                'user_update_failed',
                __('Failed to update user roles. User may not exist or role is invalid.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Log the action
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : "ID:$user_id";
        $roles_list = empty($roles) ? 'none' : implode(', ', $roles);
        Logger::log('User Roles Updated', sprintf('Updated roles for user "%s": %s', $username, $roles_list));

        return new WP_REST_Response([
            'success' => true,
            'message' => __('User roles updated successfully.', WPE_RM_TEXTDOMAIN),
            'user' => UserManager::get_user($user_id),
        ], 200);
    }

    /**
     * Get user's effective capabilities.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function get_user_effective_caps(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');

        if (empty($user_id)) {
            return new WP_Error(
                'missing_user_id',
                __('User ID is required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        $capabilities = UserManager::get_effective_capabilities($user_id);

        if (empty($capabilities)) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', WPE_RM_TEXTDOMAIN),
                ['status' => 404]
            );
        }

        return new WP_REST_Response($capabilities, 200);
    }

    /**
     * Test if a user has a specific capability.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function test_user_capability(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $capability = sanitize_key($request->get_param('capability'));

        if (empty($user_id) || empty($capability)) {
            return new WP_Error(
                'missing_parameters',
                __('User ID and capability are required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', WPE_RM_TEXTDOMAIN),
                ['status' => 404]
            );
        }

        // Test if user has the capability
        $has_cap = $user->has_cap($capability);

        // Check if it's explicitly denied (false) or just not set (null)
        $all_caps = $user->get_role_caps();
        $is_denied = isset($all_caps[$capability]) && $all_caps[$capability] === false;
        $is_not_set = !isset($all_caps[$capability]);

        $result = 'not_set';
        if ($has_cap) {
            $result = 'granted';
        } elseif ($is_denied) {
            $result = 'denied';
        }

        return new WP_REST_Response([
            'success' => true,
            'result' => $result,
            'user_id' => $user_id,
            'capability' => $capability,
        ], 200);
    }

    /**
     * Export custom roles to JSON.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function export_roles(WP_REST_Request $request): WP_REST_Response {
        // Check if this is a full backup request
        $export_type = $request->get_param('type');

        if ($export_type === 'full') {
            // Full backup: all custom roles, capabilities, and assignments
            $export_data = [
                'backup_type' => 'full',
                'version' => WPE_RM_VERSION,
                'timestamp' => current_time('mysql'),
                'roles' => [],
                'capabilities' => [],
                'role_capabilities' => [],
            ];

            // Get all roles and filter to custom roles only (exclude core and external)
            $all_roles = RoleManager::get_all_roles();
            foreach ($all_roles as $role_data) {
                // Only include custom roles (plugin-created, not core, not external)
                if (!$role_data['isCore'] && !$role_data['isExternal']) {
                    $export_data['roles'][$role_data['slug']] = [
                        'name' => $role_data['name'],
                        'capabilities' => $role_data['capabilities'] ?? [],
                    ];
                }
            }

            // Get all plugin-created capabilities
            $created_caps = get_option('wpe_rm_created_caps', []);
            $export_data['capabilities'] = $created_caps;

            // Get all plugin-managed capability assignments
            $managed_caps = get_option('wpe_rm_managed_role_caps', []);
            $export_data['role_capabilities'] = $managed_caps;

            Logger::log('Full Backup Created', sprintf('Created full backup with %d custom roles', count($export_data['roles'])));
        } else {
            // Roles only export
            $roles_param = $request->get_param('roles');
            $selected_roles = null;

            if (!empty($roles_param)) {
                // Parse comma-separated role slugs
                $selected_roles = array_map('sanitize_key', explode(',', $roles_param));
            }

            // Export all custom roles or just selected ones
            $export_data = RoleManager::export_custom_roles($selected_roles);
        }

        return new WP_REST_Response([
            'export' => $export_data,
            'success' => true,
        ], 200);
    }

    /**
     * Import roles from JSON.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function import_roles(WP_REST_Request $request) {
        $params = $request->get_json_params();

        if (empty($params)) {
            return new WP_Error(
                'missing_data',
                __('Import data is required.', WPE_RM_TEXTDOMAIN),
                ['status' => 400]
            );
        }

        // Check if this is a full backup
        $is_full_backup = isset($params['backup_type']) && $params['backup_type'] === 'full' && isset($params['version']);

        if ($is_full_backup) {
            // Full backup restore
            $restored_roles = 0;
            $restored_caps = 0;
            $errors = 0;
            $messages = [];

            // Restore roles
            if (isset($params['roles']) && is_array($params['roles'])) {
                foreach ($params['roles'] as $role_slug => $role_data) {
                    // Skip if role already exists
                    if (get_role($role_slug)) {
                        $messages[] = sprintf('Role "%s" already exists, skipping.', $role_slug);
                        continue;
                    }

                    $result = add_role(
                        $role_slug,
                        $role_data['name'] ?? $role_slug,
                        $role_data['capabilities'] ?? []
                    );

                    if ($result) {
                        $restored_roles++;
                        // Track as custom role
                        $custom_roles = get_option('wpe_rm_custom_roles', []);
                        if (!in_array($role_slug, $custom_roles, true)) {
                            $custom_roles[] = $role_slug;
                            update_option('wpe_rm_custom_roles', $custom_roles);
                        }
                    } else {
                        $errors++;
                        $messages[] = sprintf('Failed to restore role "%s".', $role_slug);
                    }
                }
            }

            // Restore capabilities
            if (isset($params['capabilities']) && is_array($params['capabilities'])) {
                update_option('wpe_rm_created_caps', $params['capabilities']);
                $restored_caps = count($params['capabilities']);
            }

            // Restore capability assignments
            if (isset($params['role_capabilities']) && is_array($params['role_capabilities'])) {
                // First, save the metadata
                update_option('wpe_rm_managed_role_caps', $params['role_capabilities']);

                // Then actually apply the capabilities to the roles
                $cap_assignments = 0;
                foreach ($params['role_capabilities'] as $role_slug => $capabilities) {
                    $role = get_role($role_slug);

                    // Skip if role doesn't exist
                    if (!$role) {
                        $messages[] = sprintf('Cannot assign capabilities to non-existent role "%s". Create the role first.', $role_slug);
                        continue;
                    }

                    // Add each capability to the role
                    foreach ($capabilities as $capability) {
                        $role->add_cap($capability, true);
                        $cap_assignments++;
                    }
                }

                if ($cap_assignments > 0) {
                    $messages[] = sprintf('Applied %d capability assignment(s) to roles.', $cap_assignments);
                }
            }

            $status_code = $errors > 0 && $restored_roles === 0 ? 400 : 200;

            Logger::log(
                'Full Backup Restored',
                sprintf('Restored %d role(s) and %d capability(ies)', $restored_roles, $restored_caps)
            );

            return new WP_REST_Response([
                'success' => $errors === 0,
                'imported' => $restored_roles,
                'capabilities_restored' => $restored_caps,
                'errors' => $errors,
                'messages' => $messages,
                'backup_type' => 'full',
            ], $status_code);
        } else {
            // Regular role import
            $result = RoleManager::import_roles($params);

            $status_code = $result['errors'] > 0 && $result['success'] === 0 ? 400 : 200;

            // Log the action
            if ($result['success'] > 0) {
                Logger::log('Roles Imported', sprintf('Imported %d role(s) with %d error(s)', $result['success'], $result['errors']));
            }

            return new WP_REST_Response([
                'success' => $result['errors'] === 0,
                'imported' => $result['success'],
                'errors' => $result['errors'],
                'messages' => $result['messages'],
            ], $status_code);
        }
    }

    /**
     * Get activity logs.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function get_logs(WP_REST_Request $request): WP_REST_Response {
        $action = $request->get_param('action');
        $details = $request->get_param('details');

        $logs = Logger::get_filtered_logs($action, $details);

        return new WP_REST_Response([
            'logs' => $logs,
            'success' => true,
        ], 200);
    }

    /**
     * Clear all activity logs.
     *
     * @return WP_REST_Response
     */
    public static function clear_logs(): WP_REST_Response {
        $success = Logger::clear_logs();

        return new WP_REST_Response([
            'success' => $success,
        ], 200);
    }

    /**
     * Get unique action types from logs.
     *
     * @return WP_REST_Response
     */
    public static function get_log_actions(): WP_REST_Response {
        $actions = Logger::get_action_types();

        return new WP_REST_Response([
            'actions' => $actions,
            'success' => true,
        ], 200);
    }

    /**
     * Get plugin settings.
     *
     * @return WP_REST_Response
     */
    public static function get_settings(): WP_REST_Response {
        $settings = get_option('wpe_rm_settings', [
            'allow_core_cap_assignment' => false,
            'autosave_debounce' => 500,
        ]);

        return new WP_REST_Response([
            'settings' => $settings,
            'success' => true,
        ], 200);
    }

    /**
     * Update plugin settings.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public static function update_settings(WP_REST_Request $request) {
        $params = $request->get_json_params();

        $settings = [
            'allow_core_cap_assignment' => isset($params['allow_core_cap_assignment'])
                ? (bool) $params['allow_core_cap_assignment']
                : false,
            'autosave_debounce' => isset($params['autosave_debounce'])
                ? absint($params['autosave_debounce'])
                : 500,
        ];

        // Validate autosave_debounce range
        if ($settings['autosave_debounce'] < 100 || $settings['autosave_debounce'] > 5000) {
            $settings['autosave_debounce'] = 500;
        }

        update_option('wpe_rm_settings', $settings);

        // Log the settings change
        $changes = [];
        if (isset($params['allow_core_cap_assignment'])) {
            $changes[] = sprintf(
                'Core capability assignment: %s',
                $settings['allow_core_cap_assignment'] ? 'enabled' : 'disabled'
            );
        }
        if (isset($params['autosave_debounce'])) {
            $changes[] = sprintf('Autosave debounce: %dms', $settings['autosave_debounce']);
        }

        if (!empty($changes)) {
            Logger::log('Settings Updated', implode(', ', $changes));
        }

        return new WP_REST_Response([
            'settings' => $settings,
            'success' => true,
            'message' => __('Settings saved successfully.', WPE_RM_TEXTDOMAIN),
        ], 200);
    }
}
