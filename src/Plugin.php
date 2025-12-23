<?php
/**
 * Main Plugin Class
 *
 * @package WP_Easy\RoleManager
 */

declare(strict_types=1);

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

        // Initialize content restrictions metabox
        Admin\RestrictionsMetabox::init();

        // Initialize capability filtering (enforce disabled roles/caps)
        Helpers\CapabilityFilter::init();

        // Initialize Bricks Builder integration
        Integrations\BricksBuilder::init();

        // Initialize Elementor integration
        Integrations\Elementor::init();

        // Initialize shortcodes
        Helpers\Shortcodes::init();

        // Initialize block visibility conditions
        Blocks\ConditionalVisibility::init();

        // Initialize webhooks only if enabled in settings
        $settings = get_option('wpe_rm_settings', []);
        if (!empty($settings['enable_webhooks'])) {
            // Initialize webhook dispatcher (listens to WP hooks)
            Webhooks\Dispatcher::init();

            // Initialize webhook processor (WP Cron for queue processing)
            Webhooks\Processor::init();
        }

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
        // DEBUG: Uncomment to see actual hook name
        // error_log('[WPE_RM] Hook: ' . $hook);

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

        // Determine which app module to load
        $app_module = self::get_app_module_for_page($hook);

        // Check for new modular build structure
        $shared_js_path = WPE_RM_PLUGIN_PATH . 'assets/dist/shared.js';
        $has_modular_build = file_exists($shared_js_path);

        if ($has_modular_build) {
            // New modular architecture
            self::enqueue_modular_assets($hook, $app_module);
        } else {
            // Fallback to legacy single-bundle architecture
            self::enqueue_legacy_assets();
        }
    }

    /**
     * Enqueue modular Svelte assets (shared + app-specific).
     *
     * @param string $hook Current admin page hook.
     * @param string|null $app_module App module name (main, settings, instructions).
     * @return void
     */
    private static function enqueue_modular_assets(string $hook, ?string $app_module): void {
        // 1. Output inline data script BEFORE modules load
        // Using a regular script that runs before modules
        wp_register_script('wpe-rm-data', false);
        wp_enqueue_script('wpe-rm-data');
        wp_add_inline_script(
            'wpe-rm-data',
            'window.wpeRmData = ' . wp_json_encode(self::get_localization_data()) . ';'
        );

        // 2. Enqueue shared module (ES module)
        wp_enqueue_script_module(
            '@wpe-rm/shared',
            WPE_RM_PLUGIN_URL . 'assets/dist/shared.js',
            [],
            WPE_RM_VERSION
        );

        // 3. Enqueue app-specific assets
        if ($app_module) {
            $app_css_path = WPE_RM_PLUGIN_PATH . "assets/dist/{$app_module}/style.css";
            $app_js_path = WPE_RM_PLUGIN_PATH . "assets/dist/{$app_module}/main.js";

            // Enqueue app CSS
            if (file_exists($app_css_path)) {
                wp_enqueue_style(
                    "wpe-rm-{$app_module}",
                    WPE_RM_PLUGIN_URL . "assets/dist/{$app_module}/style.css",
                    ['wpe-rm-admin'],
                    WPE_RM_VERSION
                );
            }

            // Enqueue app JS (ES module with shared dependency)
            if (file_exists($app_js_path)) {
                wp_enqueue_script_module(
                    "@wpe-rm/{$app_module}",
                    WPE_RM_PLUGIN_URL . "assets/dist/{$app_module}/main.js",
                    ['@wpe-rm/shared'],
                    WPE_RM_VERSION
                );
            }
        }
    }

    /**
     * Enqueue legacy single-bundle Svelte assets.
     *
     * @return void
     */
    private static function enqueue_legacy_assets(): void {
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
            wp_enqueue_script(
                'wpe-rm-svelte-app',
                WPE_RM_PLUGIN_URL . 'assets/dist/admin.js',
                [],
                WPE_RM_VERSION,
                true
            );

            // Legacy localization (wpeRmAdmin)
            wp_localize_script(
                'wpe-rm-svelte-app',
                'wpeRmAdmin',
                self::get_localization_data()
            );
        } else {
            add_action('admin_notices', function() {
                printf(
                    '<div class="notice notice-warning"><p>%s</p></div>',
                    esc_html__('WP Easy Role Manager: Svelte app not built. Run "npm install && npm run build" in the plugin directory.', WPE_RM_TEXTDOMAIN)
                );
            });
        }
    }

    /**
     * Get localization data for JavaScript.
     *
     * @return array
     */
    public static function get_localization_data(): array {
        $settings = get_option('wpe_rm_settings', []);

        return [
            'apiUrl' => rest_url('wpe-rm/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => WPE_RM_PLUGIN_URL,
            'version' => WPE_RM_VERSION,
            'settings' => [
                'color_scheme' => $settings['color_scheme'] ?? 'auto',
                'compact_mode' => $settings['compact_mode'] ?? false,
                'autosave_debounce' => $settings['autosave_debounce'] ?? 500,
                'allow_core_cap_assignment' => $settings['allow_core_cap_assignment'] ?? false,
                'allow_external_deletion' => $settings['allow_external_deletion'] ?? false,
            ],
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
                'toolsTab' => __('Tools', WPE_RM_TEXTDOMAIN),
                'revisionsTab' => __('Revisions', WPE_RM_TEXTDOMAIN),
                'logsTab' => __('Logs', WPE_RM_TEXTDOMAIN),
            ],
        ];
    }

    /**
     * Determine which app module to load based on the current page.
     *
     * @param string $hook Current admin page hook.
     * @return string|null App module name or null.
     */
    private static function get_app_module_for_page(string $hook): ?string {
        // Check for specific pages first (more specific patterns)
        if (str_contains($hook, 'wpe-role-manager-instructions')) {
            return 'instructions';
        }

        if (str_contains($hook, 'wpe-role-manager-settings')) {
            return 'settings';
        }

        if (str_contains($hook, 'wpe-role-manager-history')) {
            return 'history';
        }

        if (str_contains($hook, 'wpe-role-manager-webhooks')) {
            return 'webhooks';
        }

        // Check for main plugin page (must be last - most generic)
        if (str_contains($hook, 'wpe-role-manager')) {
            return 'main';
        }

        return null;
    }

    /**
     * Check if current page is a plugin admin page.
     *
     * @param string $hook Current admin page hook.
     * @return bool
     */
    private static function is_plugin_page(string $hook): bool {
        // Check for main plugin page or instructions page
        return str_contains($hook, 'wpe-role-manager');
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
