<?php
/**
 * Capability Manager Helper
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

/**
 * Helper class for managing WordPress capabilities.
 *
 * @since 0.0.1-alpha
 */
final class CapabilityManager {
    /**
     * Core WordPress capabilities that cannot be removed.
     *
     * @var array
     */
    private const CORE_CAPABILITIES = [
        'read',
        'edit_posts',
        'delete_posts',
        'publish_posts',
        'upload_files',
        'edit_published_posts',
        'delete_published_posts',
        'edit_pages',
        'delete_pages',
        'publish_pages',
        'edit_published_pages',
        'delete_published_pages',
        'read_private_posts',
        'read_private_pages',
        'delete_private_posts',
        'delete_private_pages',
        'edit_private_posts',
        'edit_private_pages',
        'edit_others_posts',
        'edit_others_pages',
        'delete_others_posts',
        'delete_others_pages',
        'manage_categories',
        'manage_links',
        'manage_options',
        'moderate_comments',
        'activate_plugins',
        'edit_plugins',
        'edit_users',
        'edit_files',
        'edit_themes',
        'install_plugins',
        'install_themes',
        'update_core',
        'update_plugins',
        'update_themes',
        'delete_plugins',
        'delete_themes',
        'delete_users',
        'create_users',
        'unfiltered_html',
        'unfiltered_upload',
        'edit_dashboard',
        'customize',
        'delete_site',
        'export',
        'import',
        'list_users',
        'promote_users',
        'remove_users',
        'switch_themes',
        'edit_comment',
        'edit_theme_options',
    ];

    /**
     * Add capability to a role.
     *
     * @param string $role_slug Role slug.
     * @param string $capability Capability name.
     * @param bool   $grant      Whether to grant (true) or deny (false).
     * @return bool
     */
    public static function add_capability(string $role_slug, string $capability, bool $grant = true): bool {
        $role = get_role($role_slug);

        if (!$role) {
            return false;
        }

        // Validate capability name
        if (!preg_match('/^[a-z0-9_]+$/', $capability)) {
            return false;
        }

        $role->add_cap($capability, $grant);

        // Track this role+capability pair as managed by the plugin
        $plugin_managed_caps = get_option('wpe_rm_managed_role_caps', []);
        if (!isset($plugin_managed_caps[$role_slug])) {
            $plugin_managed_caps[$role_slug] = [];
        }
        if (!in_array($capability, $plugin_managed_caps[$role_slug], true)) {
            $plugin_managed_caps[$role_slug][] = $capability;
            update_option('wpe_rm_managed_role_caps', $plugin_managed_caps);
        }

        // Track the capability itself as created by the plugin (for isExternal check)
        if (!self::is_core_capability($capability)) {
            $plugin_created_caps = get_option('wpe_rm_created_caps', []);
            if (!in_array($capability, $plugin_created_caps, true)) {
                $plugin_created_caps[] = $capability;
                update_option('wpe_rm_created_caps', $plugin_created_caps);
            }
        }

        return true;
    }

    /**
     * Remove capability from a role.
     *
     * @param string $role_slug  Role slug.
     * @param string $capability Capability name.
     * @return bool
     */
    public static function remove_capability(string $role_slug, string $capability): bool {
        $role = get_role($role_slug);

        if (!$role) {
            return false;
        }

        // Check if the capability exists on this role
        if (!isset($role->capabilities[$capability])) {
            return false;
        }

        // Check if this role+capability pair was managed by the plugin
        $plugin_managed_caps = get_option('wpe_rm_managed_role_caps', []);
        $is_managed = isset($plugin_managed_caps[$role_slug]) &&
                      in_array($capability, $plugin_managed_caps[$role_slug], true);

        // Can only remove capabilities that were added by this plugin
        if (!$is_managed) {
            return false;
        }

        $role->remove_cap($capability);

        // Remove from managed list
        if (isset($plugin_managed_caps[$role_slug])) {
            $plugin_managed_caps[$role_slug] = array_diff(
                $plugin_managed_caps[$role_slug],
                [$capability]
            );
            if (empty($plugin_managed_caps[$role_slug])) {
                unset($plugin_managed_caps[$role_slug]);
            }
            update_option('wpe_rm_managed_role_caps', $plugin_managed_caps);
        }

        // Check if this capability is still used by any other role
        $still_in_use = false;
        foreach ($plugin_managed_caps as $other_role_slug => $caps) {
            if (in_array($capability, $caps, true)) {
                $still_in_use = true;
                break;
            }
        }

        // If the capability is no longer managed by any role, remove it from created caps
        if (!$still_in_use) {
            $plugin_created_caps = get_option('wpe_rm_created_caps', []);
            $plugin_created_caps = array_diff($plugin_created_caps, [$capability]);
            update_option('wpe_rm_created_caps', array_values($plugin_created_caps));
        }

        return true;
    }

    /**
     * Disable capability for a role (doesn't remove, just marks as disabled).
     *
     * @param string $role_slug  Role slug.
     * @param string $capability Capability name.
     * @return bool
     */
    public static function disable_capability(string $role_slug, string $capability): bool {
        // Cannot modify core capabilities
        if (self::is_core_capability($capability)) {
            return false;
        }

        $disabled_caps = get_option('wpe_rm_disabled_caps', []);

        if (!isset($disabled_caps[$role_slug])) {
            $disabled_caps[$role_slug] = [];
        }

        if (!in_array($capability, $disabled_caps[$role_slug], true)) {
            $disabled_caps[$role_slug][] = $capability;
        }

        return update_option('wpe_rm_disabled_caps', $disabled_caps);
    }

    /**
     * Enable a previously disabled capability.
     *
     * @param string $role_slug  Role slug.
     * @param string $capability Capability name.
     * @return bool
     */
    public static function enable_capability(string $role_slug, string $capability): bool {
        $disabled_caps = get_option('wpe_rm_disabled_caps', []);

        if (!isset($disabled_caps[$role_slug])) {
            return true;
        }

        $disabled_caps[$role_slug] = array_diff($disabled_caps[$role_slug], [$capability]);

        if (empty($disabled_caps[$role_slug])) {
            unset($disabled_caps[$role_slug]);
        }

        return update_option('wpe_rm_disabled_caps', $disabled_caps);
    }

    /**
     * Check if a capability is a core WordPress capability.
     *
     * @param string $capability Capability name.
     * @return bool
     */
    public static function is_core_capability(string $capability): bool {
        return in_array($capability, self::CORE_CAPABILITIES, true);
    }

    /**
     * Check if a capability is an external capability (not core, not created by this plugin).
     *
     * @param string $capability Capability name.
     * @return bool
     */
    public static function is_external_capability(string $capability): bool {
        if (self::is_core_capability($capability)) {
            return false;
        }

        // Check if capability is in the created caps list
        $plugin_created_caps = get_option('wpe_rm_created_caps', []);
        if (in_array($capability, $plugin_created_caps, true)) {
            return false;
        }

        // Also check if capability is managed by any role (for backwards compatibility)
        $plugin_managed_caps = get_option('wpe_rm_managed_role_caps', []);
        foreach ($plugin_managed_caps as $role_caps) {
            if (in_array($capability, $role_caps, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all capabilities across all roles.
     *
     * @return array
     */
    public static function get_all_capabilities(): array {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $all_caps = [];

        foreach ($wp_roles->roles as $role_slug => $role) {
            if (isset($role['capabilities'])) {
                foreach ($role['capabilities'] as $cap => $grant) {
                    if (!isset($all_caps[$cap])) {
                        $is_core = self::is_core_capability($cap);
                        $all_caps[$cap] = [
                            'name' => $cap,
                            'isCore' => $is_core,
                            'isExternal' => !$is_core && self::is_external_capability($cap),
                            'roles' => [],
                        ];
                    }

                    $all_caps[$cap]['roles'][$role_slug] = $grant;
                }
            }
        }

        return array_values($all_caps);
    }

    /**
     * Get capabilities for a specific role.
     *
     * @param string $role_slug Role slug.
     * @return array
     */
    public static function get_role_capabilities(string $role_slug): array {
        $role = get_role($role_slug);

        if (!$role) {
            return [];
        }

        $disabled_caps = get_option('wpe_rm_disabled_caps', []);
        $role_disabled_caps = $disabled_caps[$role_slug] ?? [];

        $capabilities = [];

        foreach ($role->capabilities as $cap => $grant) {
            $is_core = self::is_core_capability($cap);
            $capabilities[] = [
                'name' => $cap,
                'granted' => (bool) $grant,
                'isCore' => $is_core,
                'isExternal' => !$is_core && self::is_external_capability($cap),
                'disabled' => in_array($cap, $role_disabled_caps, true),
            ];
        }

        return $capabilities;
    }

    /**
     * Get capability matrix (roles × capabilities).
     *
     * @return array
     */
    public static function get_capability_matrix(): array {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $disabled_caps = get_option('wpe_rm_disabled_caps', []);
        $plugin_managed_caps = get_option('wpe_rm_managed_role_caps', []);
        $all_caps = [];
        $matrix = [];

        // Collect all unique capabilities
        foreach ($wp_roles->roles as $role_slug => $role) {
            if (isset($role['capabilities'])) {
                foreach ($role['capabilities'] as $cap => $grant) {
                    $all_caps[$cap] = true;
                }
            }
        }

        // Build matrix
        foreach ($all_caps as $cap => $ignore) {
            $is_core = self::is_core_capability($cap);
            $cap_data = [
                'capability' => $cap,
                'isCore' => $is_core,
                'isExternal' => !$is_core && self::is_external_capability($cap),
                'roles' => [],
            ];

            foreach ($wp_roles->roles as $role_slug => $role) {
                $cap_value = isset($role['capabilities'][$cap]) ? $role['capabilities'][$cap] : null;
                $is_disabled = isset($disabled_caps[$role_slug]) && in_array($cap, $disabled_caps[$role_slug], true);
                $is_managed = isset($plugin_managed_caps[$role_slug]) &&
                             in_array($cap, $plugin_managed_caps[$role_slug], true);

                $cap_data['roles'][$role_slug] = [
                    'granted' => $cap_value === true,
                    'denied' => $cap_value === false,
                    'unset' => $cap_value === null,
                    'disabled' => $is_disabled,
                    'managed' => $is_managed,
                ];
            }

            $matrix[] = $cap_data;
        }

        return $matrix;
    }
}
