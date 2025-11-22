<?php
/**
 * Conditional Visibility for Gutenberg Blocks
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Blocks;

defined('ABSPATH') || exit;

/**
 * Handles conditional visibility for Gutenberg blocks based on roles/capabilities.
 *
 * @since 0.1.7-beta
 */
final class ConditionalVisibility {

    /**
     * Initialize the conditional visibility feature.
     *
     * @return void
     */
    public static function init(): void {
        // Check if feature is enabled
        $settings = get_option('wpe_rm_settings', []);
        $enabled = $settings['enable_block_conditions'] ?? true;

        if (!$enabled) {
            return;
        }

        // Frontend: Filter block output
        add_filter('render_block', [self::class, 'filter_block_render'], 10, 2);

        // Editor: Enqueue block editor assets
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_assets']);
    }

    /**
     * Filter block render on frontend based on conditions.
     *
     * @param string $block_content The block content.
     * @param array  $block         The block data.
     * @return string Filtered block content.
     */
    public static function filter_block_render(string $block_content, array $block): string {
        // Check if block has visibility conditions
        $attrs = $block['attrs'] ?? [];

        if (empty($attrs['wpeRmConditionsEnabled'])) {
            return $block_content;
        }

        $condition_type = $attrs['wpeRmConditionType'] ?? 'roles';
        $condition_mode = $attrs['wpeRmConditionMode'] ?? 'has';
        $condition_values = $attrs['wpeRmConditionValues'] ?? [];

        if (empty($condition_values)) {
            return $block_content;
        }

        // Get current user
        $user = wp_get_current_user();
        $has_condition = false;

        if ($condition_type === 'roles') {
            // Check if user has any of the specified roles
            if ($user->ID === 0) {
                // Not logged in - check if 'guest' or similar is in conditions
                $has_condition = in_array('guest', $condition_values, true);
            } else {
                $has_condition = !empty(array_intersect($user->roles, $condition_values));
            }
        } else {
            // Check if user has any of the specified capabilities
            if ($user->ID === 0) {
                $has_condition = false;
            } else {
                foreach ($condition_values as $cap) {
                    if ($user->has_cap($cap)) {
                        $has_condition = true;
                        break;
                    }
                }
            }
        }

        // Apply condition mode (has / has_not)
        $should_show = ($condition_mode === 'has') ? $has_condition : !$has_condition;

        return $should_show ? $block_content : '';
    }

    /**
     * Enqueue block editor assets.
     *
     * @return void
     */
    public static function enqueue_editor_assets(): void {
        // Get all roles for the editor
        $roles = [];
        $wp_roles = wp_roles();
        foreach ($wp_roles->roles as $slug => $role) {
            $roles[] = [
                'value' => $slug,
                'label' => $role['name'],
            ];
        }
        // Add guest option
        array_unshift($roles, [
            'value' => 'guest',
            'label' => __('Guest (Not Logged In)', WPE_RM_TEXTDOMAIN),
        ]);

        // Get all capabilities
        $capabilities = [];
        $all_caps = [];
        foreach ($wp_roles->roles as $role) {
            $all_caps = array_merge($all_caps, array_keys($role['capabilities']));
        }
        $all_caps = array_unique($all_caps);
        sort($all_caps);
        foreach ($all_caps as $cap) {
            $capabilities[] = [
                'value' => $cap,
                'label' => $cap,
            ];
        }

        wp_enqueue_script(
            'wpe-rm-block-conditions',
            WPE_RM_PLUGIN_URL . 'assets/js/block-conditions.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-compose', 'wp-hooks', 'wp-block-editor', 'wp-i18n'],
            WPE_RM_VERSION,
            true
        );

        wp_localize_script('wpe-rm-block-conditions', 'wpeRmBlockConditions', [
            'roles' => $roles,
            'capabilities' => $capabilities,
        ]);
    }

    /**
     * Get available roles for REST API.
     *
     * @return array
     */
    public static function get_roles(): array {
        $roles = [];
        $wp_roles = wp_roles();
        foreach ($wp_roles->roles as $slug => $role) {
            $roles[$slug] = $role['name'];
        }
        return $roles;
    }

    /**
     * Get available capabilities for REST API.
     *
     * @return array
     */
    public static function get_capabilities(): array {
        $wp_roles = wp_roles();
        $all_caps = [];
        foreach ($wp_roles->roles as $role) {
            $all_caps = array_merge($all_caps, array_keys($role['capabilities']));
        }
        return array_unique($all_caps);
    }
}
