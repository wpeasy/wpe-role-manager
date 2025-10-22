<?php
/**
 * Plugin Name: WP Easy Role Manager
 * Plugin URI: https://wpeasy.au/plugins/role-manager
 * Description: Easy UI to add, remove, enable, disable WordPress roles. Visualise and assign multiple roles to users. Visualise, add, and remove capabilities on roles. Also visualise the effective capabilities a user has based on their roles.
 * Version: 0.0.5-alpha
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: WP Easy
 * Author URI: https://wpeasy.au
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
define('WPE_RM_VERSION', '0.0.5-alpha');
define('WPE_RM_PLUGIN_FILE', __FILE__);
define('WPE_RM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPE_RM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPE_RM_TEXTDOMAIN', 'wp-easy-role-manager');
define('WPE_RM_MIN_WP', '6.4');
define('WPE_RM_MIN_PHP', '8.1');

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
}

add_action('plugins_loaded', 'wpe_rm_init');

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

    // Set default options
    add_option('wpe_rm_disabled_roles', []);
    add_option('wpe_rm_disabled_caps', []);
    add_option('wpe_rm_settings', [
        'rate_limit_enabled' => true,
        'rate_limit_requests' => 30,
        'rate_limit_window' => 60,
        'autosave_debounce' => 500,
        'required_capability' => 'manage_options',
    ]);

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'wpe_rm_activate');

/**
 * Deactivation hook.
 */
function wpe_rm_deactivate(): void {
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'wpe_rm_deactivate');

/**
 * Uninstall hook - defined in uninstall.php
 */
