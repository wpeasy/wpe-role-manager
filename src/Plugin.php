<?php
/**
 * Main Plugin Class
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager;

defined('ABSPATH') || exit;

/**
 * Main plugin initialization class.
 *
 * @since 0.0.1-alpha
 */
final class Plugin {
    /**
     * Initialize the plugin.
     *
     * @return void
     */
    public static function init(): void {
        // Load text domain for translations
        add_action('init', [self::class, 'load_textdomain']);

        // Register admin menu
        add_action('admin_menu', [Admin\Menu::class, 'register']);

        // Register REST API routes
        add_action('rest_api_init', [REST\Routes::class, 'register']);

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);

        // Initialize user profile enhancements
        Admin\UserProfile::init();

        // Initialize capability filtering (enforce disabled roles/caps)
        Helpers\CapabilityFilter::init();

        // Initialize Bricks Builder integration
        Integrations\BricksBuilder::init();

        // Initialize shortcodes
        Helpers\Shortcodes::init();

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(WPE_RM_PLUGIN_FILE), [self::class, 'add_settings_link']);
    }

    /**
     * Load plugin text domain for translations.
     *
     * @return void
     */
    public static function load_textdomain(): void {
        load_plugin_textdomain(
            WPE_RM_TEXTDOMAIN,
            false,
            dirname(plugin_basename(WPE_RM_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Enqueue admin assets (CSS and JS).
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public static function enqueue_admin_assets(string $hook): void {
        // Only load on our plugin pages
        if (!self::is_plugin_page($hook)) {
            return;
        }

        // Enqueue WPEA framework CSS
        wp_enqueue_style(
            'wpe-rm-wpea-resets',
            WPE_RM_PLUGIN_URL . 'assets/wpea/wpea-wp-resets.css',
            [],
            WPE_RM_VERSION
        );

        wp_enqueue_style(
            'wpe-rm-wpea-framework',
            WPE_RM_PLUGIN_URL . 'assets/wpea/wpea-framework.css',
            ['wpe-rm-wpea-resets'],
            WPE_RM_VERSION
        );

        // Enqueue plugin admin CSS
        wp_enqueue_style(
            'wpe-rm-admin',
            WPE_RM_PLUGIN_URL . 'assets/css/admin.css',
            ['wpe-rm-wpea-framework'],
            WPE_RM_VERSION
        );

        // Check if Svelte build files exist
        $admin_js_path = WPE_RM_PLUGIN_PATH . 'assets/dist/admin.js';
        $admin_css_path = WPE_RM_PLUGIN_PATH . 'assets/dist/admin.css';

        if (file_exists($admin_css_path)) {
            wp_enqueue_style(
                'wpe-rm-svelte',
                WPE_RM_PLUGIN_URL . 'assets/dist/admin.css',
                ['wpe-rm-admin'],
                WPE_RM_VERSION
            );
        }

        if (file_exists($admin_js_path)) {
            // Enqueue Svelte app
            wp_enqueue_script(
                'wpe-rm-svelte-app',
                WPE_RM_PLUGIN_URL . 'assets/dist/admin.js',
                [],
                WPE_RM_VERSION,
                true
            );

            // Localize script with WordPress data BEFORE the script loads
            wp_localize_script(
                'wpe-rm-svelte-app',
                'wpeRmAdmin',
                [
                    'restUrl' => rest_url('wpe-rm/v1'),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'pluginUrl' => WPE_RM_PLUGIN_URL,
                    'version' => WPE_RM_VERSION,
                    'i18n' => [
                        'saving' => __('Saving...', WPE_RM_TEXTDOMAIN),
                        'saved' => __('Saved', WPE_RM_TEXTDOMAIN),
                        'error' => __('Error', WPE_RM_TEXTDOMAIN),
                        'confirmDelete' => __('Are you sure you want to delete this?', WPE_RM_TEXTDOMAIN),
                        'rolesTab' => __('Roles', WPE_RM_TEXTDOMAIN),
                        'capabilitiesTab' => __('Capabilities', WPE_RM_TEXTDOMAIN),
                        'usersTab' => __('Users', WPE_RM_TEXTDOMAIN),
                        'importExportTab' => __('Import/Export', WPE_RM_TEXTDOMAIN),
                        'settingsTab' => __('Settings', WPE_RM_TEXTDOMAIN),
                        'logsTab' => __('Logs', WPE_RM_TEXTDOMAIN),
                    ],
                ]
            );
        } else {
            // Show admin notice if Svelte build is missing
            add_action('admin_notices', function() {
                printf(
                    '<div class="notice notice-warning"><p>%s</p></div>',
                    esc_html__('WP Easy Role Manager: Svelte app not built. Run "npm install && npm run build" in the plugin directory.', WPE_RM_TEXTDOMAIN)
                );
            });
        }
    }

    /**
     * Check if current page is a plugin admin page.
     *
     * @param string $hook Current admin page hook.
     * @return bool
     */
    private static function is_plugin_page(string $hook): bool {
        $plugin_pages = [
            'toplevel_page_wpe-role-manager',
            'wp-easy_page_wpe-role-manager',
            'role-manager_page_wpe-role-manager-instructions',
        ];

        return in_array($hook, $plugin_pages, true);
    }

    /**
     * Add settings link to plugins page.
     *
     * @param array $links Existing plugin action links.
     * @return array
     */
    public static function add_settings_link(array $links): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=wpe-role-manager'),
            __('Settings', WPE_RM_TEXTDOMAIN)
        );

        array_unshift($links, $settings_link);

        return $links;
    }
}
