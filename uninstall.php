<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package WP_Easy\RoleManager
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

/**
 * Clean up plugin data on uninstall.
 */
function wpe_rm_uninstall(): void {
    // Remove plugin options
    delete_option('wpe_rm_disabled_roles');
    delete_option('wpe_rm_disabled_caps');
    delete_option('wpe_rm_settings');
    delete_option('wpe_rm_logs');

    // For multisite, remove options from all sites
    if (is_multisite()) {
        global $wpdb;

        // Get all blog IDs
        // Note: $wpdb->blogs is a WordPress core property and is safe to use directly
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);

            try {
                // Remove plugin options for this site
                delete_option('wpe_rm_disabled_roles');
                delete_option('wpe_rm_disabled_caps');
                delete_option('wpe_rm_settings');
                delete_option('wpe_rm_logs');
            } finally {
                // Always restore context even if deletion fails
                restore_current_blog();
            }
        }

        // Remove network options if any
        delete_site_option('wpe_rm_network_settings');
    }

    // Note: We do NOT delete custom roles or user role assignments
    // as those are part of WordPress core data and should persist
    // even if the plugin is uninstalled. Users should manually
    // clean up custom roles if desired before uninstalling.

    // Clear any cached data
    wp_cache_flush();
}

wpe_rm_uninstall();
