<?php
/**
 * Admin Menu Handler
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Admin;

defined('ABSPATH') || exit;

/**
 * Register and handle admin menu pages.
 *
 * @since 0.0.1-alpha
 */
final class Menu {
    /**
     * Register admin menu pages.
     *
     * @return void
     */
    public static function register(): void {
        // Get required capability from settings
        $settings = get_option('wpe_rm_settings', []);
        $capability = $settings['required_capability'] ?? 'manage_options';

        // Add top-level menu page
        add_menu_page(
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            $capability,
            'wpe-role-manager',
            [self::class, 'render_page'],
            'dashicons-admin-users',
            71
        );

        // Add submenu page (duplicates top-level for consistency)
        add_submenu_page(
            'wpe-role-manager',
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            __('Manage Roles', WPE_RM_TEXTDOMAIN),
            $capability,
            'wpe-role-manager',
            [self::class, 'render_page']
        );

        // Add instructions submenu page
        add_submenu_page(
            'wpe-role-manager',
            __('Instructions', WPE_RM_TEXTDOMAIN),
            __('Instructions', WPE_RM_TEXTDOMAIN),
            $capability,
            'wpe-role-manager-instructions',
            [self::class, 'render_instructions_page']
        );

        // Add network admin menu for multisite
        if (is_multisite()) {
            add_action('network_admin_menu', [self::class, 'register_network_menu']);
        }
    }

    /**
     * Register network admin menu for multisite.
     *
     * @return void
     */
    public static function register_network_menu(): void {
        add_menu_page(
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            'manage_network_options',
            'wpe-role-manager-network',
            [self::class, 'render_network_page'],
            'dashicons-admin-users',
            71
        );
    }

    /**
     * Render the main admin page.
     *
     * @return void
     */
    public static function render_page(): void {
        // Check user capabilities
        if (!current_user_can(self::get_required_capability())) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', WPE_RM_TEXTDOMAIN),
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403]
            );
        }

        // Load the admin page template
        include WPE_RM_PLUGIN_PATH . 'templates/admin-page.php';
    }

    /**
     * Render the instructions page.
     *
     * @return void
     */
    public static function render_instructions_page(): void {
        // Check user capabilities
        if (!current_user_can(self::get_required_capability())) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', WPE_RM_TEXTDOMAIN),
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403]
            );
        }

        // Load the instructions page template
        include WPE_RM_PLUGIN_PATH . 'templates/instructions-page.php';
    }

    /**
     * Render the network admin page (multisite).
     *
     * @return void
     */
    public static function render_network_page(): void {
        // Check user capabilities
        if (!current_user_can('manage_network_options')) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', WPE_RM_TEXTDOMAIN),
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403]
            );
        }

        // Load the network admin page template
        include WPE_RM_PLUGIN_PATH . 'templates/network-admin-page.php';
    }

    /**
     * Get the required capability from settings.
     *
     * @return string
     */
    private static function get_required_capability(): string {
        $settings = get_option('wpe_rm_settings', []);
        return $settings['required_capability'] ?? 'manage_options';
    }
}
