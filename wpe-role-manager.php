<?php
/**
 * Plugin Name: WP Easy Role Manager
 * Plugin URI: https://alanblair.co/item/role-and-capability-manager/
 * Description: Easy UI to add, remove, enable, disable WordPress roles. Visualise and assign multiple roles to users. Visualise, add, and remove capabilities on roles. Also visualise the effective capabilities a user has based on their roles.
 * Version: 0.1.9-beta
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Alan Blair
 * Author URI: https://alanblair.co/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-easy-role-manager
 * Domain Path: /languages
 * Network: true
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WPE_RM_VERSION', '0.1.9-beta');
define('WPE_RM_PLUGIN_FILE', __FILE__);
define('WPE_RM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPE_RM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPE_RM_TEXTDOMAIN', 'wp-easy-role-manager');
define('WPE_RM_MIN_WP', '6.4');
define('WPE_RM_MIN_PHP', '8.1');

// Licensing Configuration Constants
define('WPE_RM_LICENSE_ITEM_ID', '800');
define('WPE_RM_LICENSE_API_URL', 'https://alanblair.co/');
define('WPE_RM_LICENSE_SLUG', 'wpe-role-manager');
define('WPE_RM_LICENSE_SETTINGS_KEY', 'wpe_rm_license_settings');
define('WPE_RM_LICENSE_MENU_TYPE', 'submenu');
define('WPE_RM_LICENSE_MENU_TITLE', 'License');
define('WPE_RM_LICENSE_PAGE_TITLE', 'WP Easy Role Manager - License');
define('WPE_RM_LICENSE_PURCHASE_URL', 'https://alanblair.co/item/role-and-capability-manager/');
define('WPE_RM_LICENSE_ACCOUNT_URL', 'https://alanblair.co/my-account/');
define('WPE_RM_LICENSE_DEV_OVERRIDE_KEY', 'wpe-activate-for-dev-20112026');


// Autoloader
if (file_exists(WPE_RM_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WPE_RM_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Check minimum requirements before initializing the plugin.
 *
 * @return bool True if requirements are met, false otherwise.
 */
function wpe_rm_check_requirements(): bool {
    global $wp_version;

    $errors = [];

    // Check PHP version
    if (version_compare(PHP_VERSION, WPE_RM_MIN_PHP, '<')) {
        $errors[] = sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            __('WP Easy Role Manager requires PHP %1$s or higher. You are running PHP %2$s.', WPE_RM_TEXTDOMAIN),
            WPE_RM_MIN_PHP,
            PHP_VERSION
        );
    }

    // Check WordPress version
    if (version_compare($wp_version, WPE_RM_MIN_WP, '<')) {
        $errors[] = sprintf(
            /* translators: 1: Required WordPress version, 2: Current WordPress version */
            __('WP Easy Role Manager requires WordPress %1$s or higher. You are running WordPress %2$s.', WPE_RM_TEXTDOMAIN),
            WPE_RM_MIN_WP,
            $wp_version
        );
    }

    // Display errors if any
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            foreach ($errors as $error) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html($error)
                );
            }
        });

        return false;
    }

    return true;
}

/**
 * Initialize the plugin.
 */
function wpe_rm_init(): void {
    if (!wpe_rm_check_requirements()) {
        return;
    }

    // Check if autoloader exists
    if (!class_exists('WP_Easy\\RoleManager\\Plugin')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('WP Easy Role Manager: Autoloader not found. Please run "composer install" in the plugin directory.', WPE_RM_TEXTDOMAIN)
            );
        });

        return;
    }

    // Initialize the plugin
    WP_Easy\RoleManager\Plugin::init();

    // Initialize licensing system
    wpe_rm_init_licensing();
}

add_action('plugins_loaded', 'wpe_rm_init');

/**
 * Initialize licensing system.
 */
function wpe_rm_init_licensing(): void {
    // Register licensing
    add_action('init', function() {
        // Check if licensing classes exist (PSR-4 autoloaded from src/Licensing/)
        if (!class_exists('WP_Easy\\RoleManager\\Licensing\\FluentLicensing')) {
            // Licensing files not installed - show admin notice
            add_action('admin_notices', function() {
                if (current_user_can('manage_options')) {
                    printf(
                        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                        esc_html__('WP Easy Role Manager: Licensing files not found. The FluentCart licensing classes should be in src/Licensing/ directory.', WPE_RM_TEXTDOMAIN)
                    );
                }
            });
            return;
        }

        // Create new instance and register licensing
        $licensing = new WP_Easy\RoleManager\Licensing\FluentLicensing();
        $licensing->register([
            'version' => WPE_RM_VERSION,
            'item_id' => WPE_RM_LICENSE_ITEM_ID,
            'basename' => plugin_basename(WPE_RM_PLUGIN_FILE),
            'api_url' => WPE_RM_LICENSE_API_URL,
            'slug' => WPE_RM_LICENSE_SLUG,
            'settings_key' => WPE_RM_LICENSE_SETTINGS_KEY,
        ]);

        // Initialize settings page if LicenseSettings class exists
        if (class_exists('WP_Easy\\RoleManager\\Licensing\\LicenseSettings')) {
            $licenseSettings = new WP_Easy\RoleManager\Licensing\LicenseSettings();
            $licenseSettings->register($licensing, [
                'menu_title' => WPE_RM_LICENSE_MENU_TITLE,
                'page_title' => WPE_RM_LICENSE_PAGE_TITLE,
                'title' => WPE_RM_LICENSE_PAGE_TITLE,
                'purchase_url' => WPE_RM_LICENSE_PURCHASE_URL,
                'account_url' => WPE_RM_LICENSE_ACCOUNT_URL,
                'plugin_name' => 'WP Easy Role Manager',
            ]);

            // Add the license page as submenu
            $licenseSettings->addPage([
                'type' => WPE_RM_LICENSE_MENU_TYPE,
                'page_title' => WPE_RM_LICENSE_PAGE_TITLE,
                'menu_title' => WPE_RM_LICENSE_MENU_TITLE,
                'parent_slug' => 'wpe-role-manager',
                'capability' => 'manage_options',
            ]);
        }
    });

    // Set up daily license check cron (skip on local/dev sites)
    if (!WP_Easy\RoleManager\Admin\LicenseHelper::is_local_dev_site()) {
        if (!wp_next_scheduled('wpe_rm_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'wpe_rm_daily_license_check');
        }

        // Handle daily license check
        add_action('wpe_rm_daily_license_check', 'wpe_rm_check_license_daily');
    }
}

/**
 * Daily license check via cron.
 */
function wpe_rm_check_license_daily(): void {
    // Skip if local/dev or override
    if (WP_Easy\RoleManager\Admin\LicenseHelper::is_local_dev_site() ||
        WP_Easy\RoleManager\Admin\LicenseHelper::is_dev_override()) {
        return;
    }

    // Perform remote license check
    WP_Easy\RoleManager\Admin\LicenseHelper::get_license_status(true);
}

/**
 * Activation hook.
 */
function wpe_rm_activate(): void {
    if (!wpe_rm_check_requirements()) {
        wp_die(
            esc_html__('WP Easy Role Manager cannot be activated due to unmet requirements.', WPE_RM_TEXTDOMAIN),
            esc_html__('Plugin Activation Error', WPE_RM_TEXTDOMAIN),
            ['back_link' => true]
        );
    }

    // Create database tables
    if (class_exists('WP_Easy\\RoleManager\\Database\\Schema')) {
        WP_Easy\RoleManager\Database\Schema::create_tables();
    }

    // Set default options
    add_option('wpe_rm_disabled_roles', []);
    add_option('wpe_rm_disabled_caps', []);
    add_option('wpe_rm_settings', [
        'rate_limit_enabled' => true,
        'rate_limit_requests' => 30,
        'rate_limit_window' => 60,
        'autosave_debounce' => 500,
        'required_capability' => 'manage_options',
        'revision_retention' => 300,
        'restrictions_enabled_post_types' => ['page'], // Default to page only
    ]);

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'wpe_rm_activate');

/**
 * Deactivation hook.
 */
function wpe_rm_deactivate(): void {
    // Clear scheduled license check
    $timestamp = wp_next_scheduled('wpe_rm_daily_license_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wpe_rm_daily_license_check');
    }

    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'wpe_rm_deactivate');

/**
 * Uninstall hook - defined in uninstall.php
 */
