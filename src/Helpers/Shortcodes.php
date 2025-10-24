<?php
/**
 * Shortcode Handler
 *
 * Registers and handles WordPress shortcodes for capability checking.
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

/**
 * Shortcodes Class
 */
final class Shortcodes {
    /**
     * Initialize shortcodes.
     *
     * @return void
     */
    public static function init(): void {
        // Register capability check shortcode
        add_shortcode('wpe_rm_cap', [self::class, 'capability_shortcode']);
    }

    /**
     * Capability check shortcode handler.
     *
     * Usage:
     * [wpe_rm_cap capability="edit_posts" granted="true"]Content for users with capability[/wpe_rm_cap]
     * [wpe_rm_cap capability="edit_posts" granted="false"]Content for users without capability[/wpe_rm_cap]
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string Rendered content or empty string.
     */
    public static function capability_shortcode(array $atts, string $content = ''): string {
        // Parse attributes
        $atts = shortcode_atts([
            'capability' => '',
            'granted'    => 'true', // Default to showing content when user HAS the capability
            'user_id'    => 0,      // Optional: check for specific user (0 = current user)
        ], $atts, 'wpe_rm_cap');

        // Sanitize attributes
        $capability = sanitize_key($atts['capability']);
        $granted = filter_var($atts['granted'], FILTER_VALIDATE_BOOLEAN);
        $user_id = absint($atts['user_id']);

        // Validate capability
        if (empty($capability)) {
            return '';
        }

        // Determine which user to check
        if ($user_id > 0) {
            // Check specific user
            $has_capability = user_can($user_id, $capability);
        } else {
            // Check current user
            $has_capability = is_user_logged_in() && current_user_can($capability);
        }

        // Determine if content should be shown
        // If granted="true", show content when user HAS the capability
        // If granted="false", show content when user DOES NOT have the capability
        $show_content = ($granted && $has_capability) || (!$granted && !$has_capability);

        // Return content if condition is met
        if ($show_content) {
            return do_shortcode($content);
        }

        return '';
    }
}
