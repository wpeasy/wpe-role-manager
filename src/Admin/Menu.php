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

        // Custom SVG icon
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="black" d="M2.25,4 C2.25,3.033 3.034,2.249 4.001,2.25 L7.001,2.252 C7.967,2.253 8.75,3.036 8.75,4.002 L8.75,4.5 L15.25,4.5 L15.25,4 C15.25,3.033 16.034,2.249 17.001,2.25 L20.001,2.252 C20.967,2.253 21.75,3.036 21.75,4.002 L21.75,6.999 C21.75,7.966 20.966,8.749 20,8.749 L17,8.749 C16.895,8.749 16.792,8.74 16.692,8.722 L8.723,16.692 C8.741,16.792 8.75,16.896 8.75,17.002 L8.75,17.5 L17.25,17.5 L17.25,16 C17.25,15.709 17.418,15.444 17.682,15.321 C17.945,15.197 18.257,15.238 18.48,15.424 L21.48,17.924 C21.651,18.066 21.75,18.277 21.75,18.5 C21.75,18.723 21.651,18.934 21.48,19.076 L18.48,21.576 C18.257,21.763 17.945,21.803 17.682,21.679 C17.418,21.556 17.25,21.291 17.25,21 L17.25,19.5 L8.75,19.5 L8.75,19.999 C8.75,20.966 7.966,21.749 7,21.749 L4,21.749 C3.033,21.749 2.25,20.966 2.25,19.999 L2.25,17 C2.25,16.033 3.034,15.249 4.001,15.25 L7.001,15.252 C7.105,15.252 7.208,15.261 7.307,15.279 L15.277,7.309 C15.259,7.208 15.25,7.105 15.25,6.999 L15.25,6.5 L8.75,6.5 L8.75,6.999 C8.75,7.966 7.966,8.749 7,8.749 L4,8.749 C3.033,8.749 2.25,7.966 2.25,6.999 Z" /></svg>');

        // Add top-level menu page
        add_menu_page(
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            $capability,
            'wpe-role-manager',
            [self::class, 'render_page'],
            $icon_svg,
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
        // Custom SVG icon
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path fill="black" d="M2.25,4 C2.25,3.033 3.034,2.249 4.001,2.25 L7.001,2.252 C7.967,2.253 8.75,3.036 8.75,4.002 L8.75,4.5 L15.25,4.5 L15.25,4 C15.25,3.033 16.034,2.249 17.001,2.25 L20.001,2.252 C20.967,2.253 21.75,3.036 21.75,4.002 L21.75,6.999 C21.75,7.966 20.966,8.749 20,8.749 L17,8.749 C16.895,8.749 16.792,8.74 16.692,8.722 L8.723,16.692 C8.741,16.792 8.75,16.896 8.75,17.002 L8.75,17.5 L17.25,17.5 L17.25,16 C17.25,15.709 17.418,15.444 17.682,15.321 C17.945,15.197 18.257,15.238 18.48,15.424 L21.48,17.924 C21.651,18.066 21.75,18.277 21.75,18.5 C21.75,18.723 21.651,18.934 21.48,19.076 L18.48,21.576 C18.257,21.763 17.945,21.803 17.682,21.679 C17.418,21.556 17.25,21.291 17.25,21 L17.25,19.5 L8.75,19.5 L8.75,19.999 C8.75,20.966 7.966,21.749 7,21.749 L4,21.749 C3.033,21.749 2.25,20.966 2.25,19.999 L2.25,17 C2.25,16.033 3.034,15.249 4.001,15.25 L7.001,15.252 C7.105,15.252 7.208,15.261 7.307,15.279 L15.277,7.309 C15.259,7.208 15.25,7.105 15.25,6.999 L15.25,6.5 L8.75,6.5 L8.75,6.999 C8.75,7.966 7.966,8.749 7,8.749 L4,8.749 C3.033,8.749 2.25,7.966 2.25,6.999 Z" /></svg>');

        add_menu_page(
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            __('Role Manager', WPE_RM_TEXTDOMAIN),
            'manage_network_options',
            'wpe-role-manager-network',
            [self::class, 'render_network_page'],
            $icon_svg,
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

        // Check license status (optional: can be used to restrict access)
        // For now, only show a notice if license is invalid
        // To fully restrict access, uncomment the code in the LICENSING.md file
        $has_license = LicenseHelper::has_valid_license();
        if (!$has_license && !LicenseHelper::is_local_dev_site()) {
            add_action('admin_notices', function() {
                $license_info = LicenseHelper::get_license_info();
                printf(
                    '<div class="notice notice-%s"><p><strong>%s:</strong> %s</p></div>',
                    esc_attr($license_info['class'] ?? 'warning'),
                    esc_html__('License Status', WPE_RM_TEXTDOMAIN),
                    esc_html($license_info['message'] ?? __('License check unavailable', WPE_RM_TEXTDOMAIN))
                );
            });
        }

        // Apply theme immediately to prevent flash
        self::output_theme_script();

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

        // Apply theme immediately to prevent flash
        self::output_theme_script();

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

    /**
     * Output inline script to apply theme immediately (prevent flash).
     *
     * @return void
     */
    private static function output_theme_script(): void {
        $settings = get_option('wpe_rm_settings', []);
        $color_scheme = $settings['color_scheme'] ?? 'auto';
        $compact_mode = $settings['compact_mode'] ?? false;
        ?>
        <script>
        (function() {
            var scheme = <?php echo json_encode($color_scheme); ?>;
            var compact = <?php echo json_encode($compact_mode); ?>;

            if (scheme === 'light') {
                document.documentElement.setAttribute('data-color-scheme', 'light');
            } else if (scheme === 'dark') {
                document.documentElement.setAttribute('data-color-scheme', 'dark');
            }

            if (compact) {
                document.documentElement.setAttribute('data-compact-mode', 'true');
            }
        })();
        </script>
        <?php
    }
}
