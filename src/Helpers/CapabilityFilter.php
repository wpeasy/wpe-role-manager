<?php
/**
 * Capability Filter
 *
 * Filters WordPress capability checks to respect disabled roles/capabilities
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

use WP_User;

/**
 * Filter capability checks to exclude disabled roles and capabilities.
 *
 * @since 0.0.3-alpha
 */
final class CapabilityFilter {
    /**
     * Initialize capability filtering.
     *
     * @return void
     */
    public static function init(): void {
        // Filter user capabilities to exclude disabled roles/caps
        add_filter('user_has_cap', [self::class, 'filter_user_capabilities'], 10, 4);

        // Filter user metadata to hide disabled roles from roles array
        add_filter('get_user_metadata', [self::class, 'filter_user_roles'], 10, 4);
    }

    /**
     * Filter user capabilities to exclude disabled roles and capabilities.
     *
     * @param array   $allcaps All capabilities the user has.
     * @param array   $caps    Required capabilities being checked.
     * @param array   $args    Arguments passed to has_cap().
     * @param WP_User $user    The user object.
     * @return array Filtered capabilities.
     */
    public static function filter_user_capabilities(array $allcaps, array $caps, array $args, WP_User $user): array {
        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $disabled_caps = get_option('wpe_rm_disabled_caps', []);

        // If no disabled roles or caps, return early
        if (empty($disabled_roles) && empty($disabled_caps)) {
            return $allcaps;
        }

        // Get capabilities to remove from disabled roles
        $caps_to_remove = [];

        foreach ($user->roles as $role_slug) {
            // If this role is disabled, remove ALL its capabilities
            if (in_array($role_slug, $disabled_roles, true)) {
                $role = get_role($role_slug);
                if ($role) {
                    foreach ($role->capabilities as $cap => $grant) {
                        $caps_to_remove[$cap] = true;
                    }
                }
            }

            // Remove disabled capabilities from this role
            $role_disabled_caps = $disabled_caps[$role_slug] ?? [];
            foreach ($role_disabled_caps as $cap) {
                $caps_to_remove[$cap] = true;
            }
        }

        // Remove the identified capabilities
        foreach ($caps_to_remove as $cap => $val) {
            unset($allcaps[$cap]);
        }

        return $allcaps;
    }

    /**
     * Filter user metadata to hide disabled roles from the roles array.
     *
     * This ensures disabled roles don't appear in $user->roles, which affects
     * third-party plugins like Bricks Builder that check role membership directly.
     *
     * @param mixed  $value     The metadata value (null if not set yet).
     * @param int    $object_id User ID.
     * @param string $meta_key  The metadata key being retrieved.
     * @param bool   $single    Whether to return a single value.
     * @return mixed Filtered metadata value or null to use default behavior.
     */
    public static function filter_user_roles($value, int $object_id, string $meta_key, bool $single) {
        global $wpdb;

        // Only filter the capabilities meta key
        if ($meta_key !== $wpdb->get_blog_prefix() . 'capabilities') {
            return $value;
        }

        // Get disabled roles
        $disabled_roles = get_option('wpe_rm_disabled_roles', []);

        // If no disabled roles, skip filtering
        if (empty($disabled_roles)) {
            return $value;
        }

        // Let WordPress get the original value if we haven't intercepted yet
        if ($value === null) {
            // Remove this filter temporarily to avoid recursion
            remove_filter('get_user_metadata', [self::class, 'filter_user_roles'], 10);

            // Get the original capabilities
            $capabilities = get_user_meta($object_id, $meta_key, $single);

            // Re-add the filter
            add_filter('get_user_metadata', [self::class, 'filter_user_roles'], 10, 4);

            // If not an array, return as-is
            if (!is_array($capabilities)) {
                return $value;
            }

            // Remove disabled roles from capabilities array
            foreach ($disabled_roles as $disabled_role) {
                unset($capabilities[$disabled_role]);
            }

            // Return the filtered capabilities
            // WordPress will use this instead of the database value
            return $single ? [$capabilities] : [[$capabilities]];
        }

        return $value;
    }
}
