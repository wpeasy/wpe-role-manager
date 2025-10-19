<?php
/**
 * User Manager Helper
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

use WP_User;

/**
 * Helper class for managing user roles.
 *
 * @since 0.0.1-alpha
 */
final class UserManager {
    /**
     * Get all users with their roles.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_users(array $args = []): array {
        $defaults = [
            'number' => 50,
            'paged' => 1,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);

        $users = get_users($args);
        $user_data = [];

        foreach ($users as $user) {
            $user_data[] = self::format_user_data($user);
        }

        return $user_data;
    }

    /**
     * Get a single user by ID.
     *
     * @param int $user_id User ID.
     * @return array|null
     */
    public static function get_user(int $user_id): ?array {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return null;
        }

        return self::format_user_data($user);
    }

    /**
     * Format user data for API response.
     *
     * @param WP_User $user User object.
     * @return array
     */
    private static function format_user_data(WP_User $user): array {
        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'displayName' => $user->display_name,
            'roles' => $user->roles,
            'allcaps' => array_keys(array_filter($user->allcaps)),
        ];
    }

    /**
     * Update user roles.
     *
     * @param int   $user_id User ID.
     * @param array $roles   Array of role slugs.
     * @return bool
     */
    public static function update_user_roles(int $user_id, array $roles): bool {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        // Validate all roles exist
        foreach ($roles as $role) {
            if (!get_role($role)) {
                return false;
            }
        }

        // Remove all existing roles
        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }

        // Add new roles
        foreach ($roles as $role) {
            $user->add_role($role);
        }

        return true;
    }

    /**
     * Add a role to a user.
     *
     * @param int    $user_id User ID.
     * @param string $role    Role slug.
     * @return bool
     */
    public static function add_role_to_user(int $user_id, string $role): bool {
        $user = get_user_by('id', $user_id);

        if (!$user || !get_role($role)) {
            return false;
        }

        $user->add_role($role);

        return true;
    }

    /**
     * Remove a role from a user.
     *
     * @param int    $user_id User ID.
     * @param string $role    Role slug.
     * @return bool
     */
    public static function remove_role_from_user(int $user_id, string $role): bool {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return false;
        }

        $user->remove_role($role);

        return true;
    }

    /**
     * Get effective capabilities for a user.
     *
     * This returns the union of all capabilities from all roles,
     * accounting for disabled roles and capabilities.
     *
     * @param int $user_id User ID.
     * @return array
     */
    public static function get_effective_capabilities(int $user_id): array {
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return [];
        }

        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $disabled_caps = get_option('wpe_rm_disabled_caps', []);

        $effective_caps = [];

        // Get capabilities from each role
        foreach ($user->roles as $role_slug) {
            // Skip disabled roles
            if (in_array($role_slug, $disabled_roles, true)) {
                continue;
            }

            $role = get_role($role_slug);
            if (!$role) {
                continue;
            }

            $role_disabled_caps = $disabled_caps[$role_slug] ?? [];

            foreach ($role->capabilities as $cap => $grant) {
                // Skip disabled capabilities
                if (in_array($cap, $role_disabled_caps, true)) {
                    continue;
                }

                if ($grant) {
                    $effective_caps[$cap] = true;
                }
            }
        }

        // Add user-specific capabilities
        if (isset($user->caps) && is_array($user->caps)) {
            foreach ($user->caps as $cap => $grant) {
                if ($grant && !isset($effective_caps[$cap])) {
                    $effective_caps[$cap] = true;
                }
            }
        }

        return [
            'userId' => $user_id,
            'username' => $user->user_login,
            'roles' => $user->roles,
            'capabilities' => array_keys($effective_caps),
            'capabilityCount' => count($effective_caps),
        ];
    }

    /**
     * Migrate users from one role to another.
     *
     * @param string $from_role Source role slug.
     * @param string $to_role   Destination role slug.
     * @param bool   $remove    Whether to remove the old role.
     * @return array Result with counts.
     */
    public static function migrate_users(string $from_role, string $to_role, bool $remove = true): array {
        // Validate roles exist
        if (!get_role($from_role) || !get_role($to_role)) {
            return [
                'success' => false,
                'message' => 'Invalid role(s)',
                'migrated' => 0,
            ];
        }

        $users = get_users([
            'role' => $from_role,
            'fields' => 'ID',
            'number' => -1,
        ]);

        $migrated = 0;

        foreach ($users as $user_id) {
            $user = get_user_by('id', $user_id);

            if (!$user) {
                continue;
            }

            // Add new role
            $user->add_role($to_role);

            // Remove old role if requested
            if ($remove) {
                $user->remove_role($from_role);
            }

            $migrated++;
        }

        return [
            'success' => true,
            'message' => sprintf('Migrated %d user(s)', $migrated),
            'migrated' => $migrated,
        ];
    }

    /**
     * Get user count by role.
     *
     * @return array
     */
    public static function get_user_count_by_role(): array {
        $counts = count_users();
        $role_counts = [];

        if (isset($counts['avail_roles'])) {
            foreach ($counts['avail_roles'] as $role => $count) {
                $role_counts[$role] = $count;
            }
        }

        return $role_counts;
    }

    /**
     * Search users by username or email.
     *
     * @param string $search Search term.
     * @param int    $limit  Result limit.
     * @return array
     */
    public static function search_users(string $search, int $limit = 50): array {
        $users = get_users([
            'search' => '*' . $search . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number' => $limit,
        ]);

        $user_data = [];

        foreach ($users as $user) {
            $user_data[] = self::format_user_data($user);
        }

        return $user_data;
    }
}
