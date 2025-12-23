<?php
/**
 * Admin Menu Handler
 *
 * @package WP_Easy\RoleManager
 */

declare(strict_types=1);

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

        // Add settings submenu page
        add_submenu_page(
            'wpe-role-manager',
            __('Settings', WPE_RM_TEXTDOMAIN),
            __('Settings', WPE_RM_TEXTDOMAIN),
            $capability,
            'wpe-role-manager-settings',
            [self::class, 'render_settings_page']
        );

        // Add history submenu page
        add_submenu_page(
            'wpe-role-manager',
            __('History', WPE_RM_TEXTDOMAIN),
            __('History', WPE_RM_TEXTDOMAIN),
            $capability,
            'wpe-role-manager-history',
            [self::class, 'render_history_page']
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

        // Add webhooks submenu page (only if enabled in settings)
        if (!empty($settings['enable_webhooks'])) {
            add_submenu_page(
                'wpe-role-manager',
                __('Webhooks', WPE_RM_TEXTDOMAIN),
                __('Webhooks', WPE_RM_TEXTDOMAIN),
                $capability,
                'wpe-role-manager-webhooks',
                [self::class, 'render_webhooks_page']
            );
        }

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

        // Check license status - block access if no valid license on production sites
        if (!LicenseHelper::is_local_dev_site() && !LicenseHelper::has_valid_license()) {
            self::render_license_required_page();
            return;
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

        // Load the instructions page template (no license check - always accessible)
        include WPE_RM_PLUGIN_PATH . 'templates/instructions-page.php';
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public static function render_settings_page(): void {
        // Check user capabilities
        if (!current_user_can(self::get_required_capability())) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', WPE_RM_TEXTDOMAIN),
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403]
            );
        }

        // Check license status
        if (!LicenseHelper::is_local_dev_site() && !LicenseHelper::has_valid_license()) {
            self::render_license_required_page();
            return;
        }

        // Apply theme immediately to prevent flash
        self::output_theme_script();

        // Load the settings page template
        include WPE_RM_PLUGIN_PATH . 'templates/settings-page.php';
    }

    /**
     * Render the history page.
     *
     * @return void
     */
    public static function render_history_page(): void {
        // Check user capabilities
        if (!current_user_can(self::get_required_capability())) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', WPE_RM_TEXTDOMAIN),
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403]
            );
        }

        // Check license status
        if (!LicenseHelper::is_local_dev_site() && !LicenseHelper::has_valid_license()) {
            self::render_license_required_page();
            return;
        }

        // Apply theme immediately to prevent flash
        self::output_theme_script();

        // Load the history page template
        include WPE_RM_PLUGIN_PATH . 'templates/history-page.php';
    }

    /**
     * Render the webhooks page.
     *
     * @return void
     */
    public static function render_webhooks_page(): void {
        // Check user capabilities
        if (!current_user_can(self::get_required_capability())) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', WPE_RM_TEXTDOMAIN),
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403]
            );
        }

        // Check license status
        if (!LicenseHelper::is_local_dev_site() && !LicenseHelper::has_valid_license()) {
            self::render_license_required_page();
            return;
        }

        // Apply theme immediately to prevent flash
        self::output_theme_script();

        // Load the webhooks page template
        include WPE_RM_PLUGIN_PATH . 'templates/webhooks-page.php';
    }

    /**
     * Render the license required page when no valid license is found.
     *
     * @return void
     */
    private static function render_license_required_page(): void {
        $license_page_url = admin_url('admin.php?page=wpe-role-manager-manage-license');
        $get_license_url = defined('WPE_RM_LICENSE_PURCHASE_URL') ? WPE_RM_LICENSE_PURCHASE_URL : 'https://wpeasy.au/role-manager/';
        ?>
        <div class="wrap wpea">
            <div style="max-width: 600px; margin: 50px auto; text-align: center;">
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 48px; margin-bottom: 20px;">🔐</div>
                    <h1 style="font-size: 24px; font-weight: 600; margin: 0 0 15px 0; color: #1d2327;">
                        <?php esc_html_e('License Required', WPE_RM_TEXTDOMAIN); ?>
                    </h1>
                    <p style="color: #646970; font-size: 14px; margin: 0 0 25px 0; line-height: 1.6;">
                        <?php esc_html_e('WP Easy Role Manager requires a valid license to access its features. Please activate your license to continue.', WPE_RM_TEXTDOMAIN); ?>
                    </p>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="<?php echo esc_url($license_page_url); ?>" class="button button-primary button-hero">
                            <?php esc_html_e('Activate License', WPE_RM_TEXTDOMAIN); ?>
                        </a>
                        <a href="<?php echo esc_url($get_license_url); ?>" class="button button-secondary button-hero" target="_blank">
                            <?php esc_html_e('Get License', WPE_RM_TEXTDOMAIN); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
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
     * Output inline script and styles to apply theme immediately (prevent flash).
     *
     * @return void
     */
    private static function output_theme_script(): void {
        $settings = get_option('wpe_rm_settings', []);
        $color_scheme = $settings['color_scheme'] ?? 'auto';
        $framework_settings = $settings['framework_settings'] ?? null;

        // Default framework settings
        $defaults = [
            'compact_mode' => false,
            'compact_multiplier' => 0.7,
            'theme_mode' => 'system',
            'space_base' => 8,
            'space_scale' => 1.5,
            'font_base' => 13,
            'type_scale' => 1.2,
            'radius_base' => 6,
            'radius_scale' => 1.67,
            'primary_light' => '#a402ba',
            'primary_dark' => '#a402ba',
            'secondary_light' => '#32a8ac',
            'secondary_dark' => '#32a8ac',
            'neutral_light' => '#777777',
            'neutral_dark' => '#9aa0a6',
            'success_light' => '#22c55e',
            'success_dark' => '#4ade80',
            'warning_light' => '#f59e0b',
            'warning_dark' => '#fbbf24',
            'danger_light' => '#ef4444',
            'danger_dark' => '#f87171',
            'info_light' => '#3b82f6',
            'info_dark' => '#60a5fa',
        ];

        // Merge with saved settings
        $fs = is_array($framework_settings) ? array_merge($defaults, $framework_settings) : $defaults;

        // Use theme_mode from framework_settings if available, fallback to color_scheme
        $theme_mode = $fs['theme_mode'] ?? $color_scheme;
        ?>
        <script>
        (function() {
            var STORAGE_KEY = 'wpe_rm_display_settings';
            var serverSettings = <?php echo wp_json_encode($fs); ?>;
            var themeMode = <?php echo wp_json_encode($theme_mode); ?>;

            // Try to load from localStorage first (faster)
            var settings = serverSettings;
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    settings = JSON.parse(stored);
                    themeMode = settings.theme_mode || themeMode;
                }
            } catch(e) {}

            // Apply color-scheme CSS property for light-dark() function
            if (themeMode === 'light') {
                document.documentElement.style.setProperty('color-scheme', 'light only');
            } else if (themeMode === 'dark') {
                document.documentElement.style.setProperty('color-scheme', 'dark only');
            } else {
                document.documentElement.style.setProperty('color-scheme', 'light dark');
            }

            // Store server settings to localStorage for future instant loads
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(serverSettings));
            } catch(e) {}
        })();
        </script>
        <style id="wpea-display-overrides">
        :root {
            --wpea-space-base: <?php echo (float) $fs['space_base']; ?>px !important;
            --wpea-space-scale: <?php echo (float) $fs['space_scale']; ?> !important;
            --wpea-space-compact: <?php echo (float) $fs['compact_multiplier']; ?> !important;
            --wpea-fs-base: <?php echo (float) $fs['font_base']; ?>px !important;
            --wpea-type-scale: <?php echo (float) $fs['type_scale']; ?> !important;
            --wpea-radius-base: <?php echo (float) $fs['radius_base']; ?>px !important;
            --wpea-radius-scale: <?php echo (float) $fs['radius_scale']; ?> !important;
            --wpea-color--primary-light-override: <?php echo esc_attr($fs['primary_light']); ?>;
            --wpea-color--primary-dark-override: <?php echo esc_attr($fs['primary_dark']); ?>;
            --wpea-color--secondary-light-override: <?php echo esc_attr($fs['secondary_light']); ?>;
            --wpea-color--secondary-dark-override: <?php echo esc_attr($fs['secondary_dark']); ?>;
            --wpea-color--neutral-light-override: <?php echo esc_attr($fs['neutral_light']); ?>;
            --wpea-color--neutral-dark-override: <?php echo esc_attr($fs['neutral_dark']); ?>;
            --wpea-color--success-light-override: <?php echo esc_attr($fs['success_light']); ?>;
            --wpea-color--success-dark-override: <?php echo esc_attr($fs['success_dark']); ?>;
            --wpea-color--warning-light-override: <?php echo esc_attr($fs['warning_light']); ?>;
            --wpea-color--warning-dark-override: <?php echo esc_attr($fs['warning_dark']); ?>;
            --wpea-color--danger-light-override: <?php echo esc_attr($fs['danger_light']); ?>;
            --wpea-color--danger-dark-override: <?php echo esc_attr($fs['danger_dark']); ?>;
            --wpea-color--info-light-override: <?php echo esc_attr($fs['info_light']); ?>;
            --wpea-color--info-dark-override: <?php echo esc_attr($fs['info_dark']); ?>;
        }
        </style>
        <?php
    }
}
