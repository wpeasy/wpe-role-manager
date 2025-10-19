<?php
/**
 * Role Manager Helper
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

use WP_Role;

/**
 * Helper class for managing WordPress roles.
 *
 * @since 0.0.1-alpha
 */
final class RoleManager {
    /**
     * Core WordPress roles that cannot be modified.
     *
     * @var array
     */
    private const CORE_ROLES = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

    /**
     * Get all roles with metadata.
     *
     * @return array
     */
    public static function get_all_roles(): array {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $plugin_created_roles = get_option('wpe_rm_created_roles', []);
        $roles = [];

        foreach ($wp_roles->roles as $slug => $role) {
            $is_core = in_array($slug, self::CORE_ROLES, true);
            $is_plugin_created = in_array($slug, $plugin_created_roles, true);

            $roles[] = [
                'slug' => $slug,
                'name' => $role['name'],
                'capabilities' => $role['capabilities'],
                'isCore' => $is_core,
                'isExternal' => !$is_core && !$is_plugin_created,
                'disabled' => in_array($slug, $disabled_roles, true),
                'userCount' => self::count_users_in_role($slug),
            ];
        }

        return $roles;
    }

    /**
     * Get a single role by slug.
     *
     * @param string $slug Role slug.
     * @return array|null
     */
    public static function get_role(string $slug): ?array {
        $role = get_role($slug);

        if (!$role) {
            return null;
        }

        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $plugin_created_roles = get_option('wpe_rm_created_roles', []);
        $is_core = in_array($slug, self::CORE_ROLES, true);
        $is_plugin_created = in_array($slug, $plugin_created_roles, true);

        return [
            'slug' => $slug,
            'name' => $role->name,
            'capabilities' => $role->capabilities,
            'isCore' => $is_core,
            'isExternal' => !$is_core && !$is_plugin_created,
            'disabled' => in_array($slug, $disabled_roles, true),
            'userCount' => self::count_users_in_role($slug),
        ];
    }

    /**
     * Create a new role.
     *
     * @param string $slug Role slug.
     * @param string $name Role display name.
     * @param string $copy_from Optional role to copy capabilities from.
     * @return array|false Role data or false on failure.
     */
    public static function create_role(string $slug, string $name, string $copy_from = ''): array|false {
        // Validate slug
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            return false;
        }

        // Check if role already exists
        if (get_role($slug)) {
            return false;
        }

        // Get capabilities from source role
        $capabilities = [];
        if (!empty($copy_from)) {
            $source_role = get_role($copy_from);
            if ($source_role) {
                $capabilities = $source_role->capabilities;
            }
        }

        // Create the role
        $role = add_role($slug, $name, $capabilities);

        if (!$role) {
            return false;
        }

        // Track this role as created by the plugin
        $plugin_created_roles = get_option('wpe_rm_created_roles', []);
        if (!in_array($slug, $plugin_created_roles, true)) {
            $plugin_created_roles[] = $slug;
            update_option('wpe_rm_created_roles', $plugin_created_roles);
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'capabilities' => $capabilities,
            'isCore' => false,
            'isExternal' => false,
            'disabled' => false,
            'userCount' => 0,
        ];
    }

    /**
     * Update role (currently only supports enable/disable).
     *
     * @param string $slug Role slug.
     * @param array  $data Update data.
     * @return bool
     */
    public static function update_role(string $slug, array $data): bool {
        // Check if role exists
        if (!get_role($slug)) {
            return false;
        }

        // Cannot modify core roles
        if (self::is_core_role($slug)) {
            return false;
        }

        // Cannot enable/disable external roles (not created by this plugin)
        if (self::is_external_role($slug)) {
            return false;
        }

        // Handle disable/enable
        if (isset($data['disabled'])) {
            return self::set_role_disabled($slug, (bool) $data['disabled']);
        }

        return true;
    }

    /**
     * Delete a role.
     *
     * @param string $slug Role slug.
     * @param bool   $force Force delete even if users exist.
     * @return bool
     */
    public static function delete_role(string $slug, bool $force = false): bool {
        // Cannot delete core roles
        if (self::is_core_role($slug)) {
            return false;
        }

        // Check if role exists
        if (!get_role($slug)) {
            return false;
        }

        // Check if users have this role
        $user_count = self::count_users_in_role($slug);
        if ($user_count > 0 && !$force) {
            return false;
        }

        // Remove from disabled list if present
        self::remove_from_disabled($slug);

        // Remove from plugin created roles list if present
        self::remove_from_plugin_created($slug);

        // Remove the role
        remove_role($slug);

        return true;
    }

    /**
     * Check if a role is a core WordPress role.
     *
     * @param string $slug Role slug.
     * @return bool
     */
    public static function is_core_role(string $slug): bool {
        return in_array($slug, self::CORE_ROLES, true);
    }

    /**
     * Check if a role is an external role (not core, not created by this plugin).
     *
     * @param string $slug Role slug.
     * @return bool
     */
    public static function is_external_role(string $slug): bool {
        if (self::is_core_role($slug)) {
            return false;
        }

        $plugin_created_roles = get_option('wpe_rm_created_roles', []);
        return !in_array($slug, $plugin_created_roles, true);
    }

    /**
     * Set role disabled status.
     *
     * @param string $slug     Role slug.
     * @param bool   $disabled Whether to disable the role.
     * @return bool
     */
    public static function set_role_disabled(string $slug, bool $disabled): bool {
        $disabled_roles = get_option('wpe_rm_disabled_roles', []);

        if ($disabled) {
            if (!in_array($slug, $disabled_roles, true)) {
                $disabled_roles[] = $slug;
            }
        } else {
            $disabled_roles = array_diff($disabled_roles, [$slug]);
        }

        return update_option('wpe_rm_disabled_roles', array_values($disabled_roles));
    }

    /**
     * Remove role from disabled list.
     *
     * @param string $slug Role slug.
     * @return bool
     */
    private static function remove_from_disabled(string $slug): bool {
        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $disabled_roles = array_diff($disabled_roles, [$slug]);
        return update_option('wpe_rm_disabled_roles', array_values($disabled_roles));
    }

    /**
     * Remove role from plugin created list.
     *
     * @param string $slug Role slug.
     * @return bool
     */
    private static function remove_from_plugin_created(string $slug): bool {
        $plugin_created_roles = get_option('wpe_rm_created_roles', []);
        $plugin_created_roles = array_diff($plugin_created_roles, [$slug]);
        return update_option('wpe_rm_created_roles', array_values($plugin_created_roles));
    }

    /**
     * Count users in a specific role.
     *
     * @param string $slug Role slug.
     * @return int
     */
    public static function count_users_in_role(string $slug): int {
        $users = get_users([
            'role' => $slug,
            'fields' => 'ID',
            'number' => -1,
        ]);

        return count($users);
    }

    /**
     * Get users assigned to a role (for migration).
     *
     * @param string $slug Role slug.
     * @return array Array of user IDs.
     */
    public static function get_users_in_role(string $slug): array {
        return get_users([
            'role' => $slug,
            'fields' => 'ID',
            'number' => -1,
        ]);
    }

    /**
     * Export custom roles to array.
     *
     * @param array|null $selected_roles Optional array of role slugs to export. If null, exports all custom roles.
     * @return array
     */
    public static function export_custom_roles(?array $selected_roles = null): array {
        $all_roles = self::get_all_roles();

        // Filter to custom roles only (exclude core)
        $custom_roles = array_filter($all_roles, fn($role) => !$role['isCore']);

        // If specific roles are selected, filter to only those
        if ($selected_roles !== null && !empty($selected_roles)) {
            $custom_roles = array_filter($custom_roles, function($role) use ($selected_roles) {
                return in_array($role['slug'], $selected_roles, true);
            });
        }

        return [
            'version' => WPE_RM_VERSION,
            'exported' => current_time('mysql'),
            'roles' => array_values($custom_roles),
        ];
    }

    /**
     * Import roles from array.
     *
     * @param array $data Import data.
     * @return array Result with success/error counts.
     */
    public static function import_roles(array $data): array {
        $result = [
            'success' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        if (!isset($data['roles']) || !is_array($data['roles'])) {
            $result['messages'][] = 'Invalid import data format';
            $result['errors']++;
            return $result;
        }

        // Get the list of plugin-created roles
        $plugin_created_roles = get_option('wpe_rm_created_roles', []);

        foreach ($data['roles'] as $role_data) {
            if (!isset($role_data['slug']) || !isset($role_data['name'])) {
                $result['messages'][] = 'Missing required fields for role';
                $result['errors']++;
                continue;
            }

            // Skip if role already exists
            if (get_role($role_data['slug'])) {
                $result['messages'][] = "Role '{$role_data['slug']}' already exists, skipped";
                continue;
            }

            $capabilities = $role_data['capabilities'] ?? [];
            $role = add_role($role_data['slug'], $role_data['name'], $capabilities);

            if ($role) {
                // Track this role as created by the plugin
                if (!in_array($role_data['slug'], $plugin_created_roles, true)) {
                    $plugin_created_roles[] = $role_data['slug'];
                }

                $result['success']++;
                $result['messages'][] = "Created role '{$role_data['name']}'";
            } else {
                $result['errors']++;
                $result['messages'][] = "Failed to create role '{$role_data['name']}'";
            }
        }

        // Update the plugin-created roles option
        update_option('wpe_rm_created_roles', $plugin_created_roles);

        return $result;
    }
}
