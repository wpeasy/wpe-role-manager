<?php
/**
 * Uninstall Handler
 *
 * This file is executed when the plugin is deleted from the Plugins page.
 * It removes all plugin data if clean uninstall is enabled in settings.
 *
 * @package WP_Easy\RoleManager
 */

// Exit if not called by WordPress uninstall process
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Main uninstall function.
 *
 * @return void
 */
function wpe_rm_uninstall(): void {
    // Get settings to check if clean uninstall is enabled
    $settings = get_option('wpe_rm_settings', []);

    // Only proceed with full cleanup if clean uninstall is explicitly enabled
    $clean_uninstall = !empty($settings['enable_clean_uninstall']);

    // Store options for role/cap removal before deleting settings
    $remove_roles = $clean_uninstall && !empty($settings['uninstall_remove_roles']);
    $remove_caps = $clean_uninstall && !empty($settings['uninstall_remove_capabilities']);

    // Get created roles and capabilities before deleting options
    $created_roles = get_option('wpe_rm_created_roles', []);
    $created_caps = get_option('wpe_rm_created_caps', []);

    // =========================================================================
    // Remove WordPress Options (only if clean uninstall is enabled)
    // =========================================================================
    if ($clean_uninstall) {
        $options_to_delete = [
            'wpe_rm_settings',
            'wpe_rm_disabled_roles',
            'wpe_rm_disabled_caps',
            'wpe_rm_created_caps',
            'wpe_rm_created_roles',
            'wpe_rm_managed_role_caps',
            'wpe_rm_logs',
            'wpe_rm_revisions',
            'wpe_rm_webhooks_outgoing',
            'wpe_rm_webhooks_queue',
            'wpe_rm_webhooks_log',
            'wpe_rm_custom_roles', // Legacy option key
        ];

        foreach ($options_to_delete as $option) {
            delete_option($option);
        }

        // =====================================================================
        // Remove Transients (rate limiting, etc.)
        // =====================================================================
        global $wpdb;

        // Delete all transients with our prefix
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_wpe_rm_%',
                '_transient_timeout_wpe_rm_%'
            )
        );

        // =====================================================================
        // For Multisite: Remove options from all sites
        // =====================================================================
        if (is_multisite()) {
            // Get all blog IDs
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);

                try {
                    // Remove plugin options for this site
                    foreach ($options_to_delete as $option) {
                        delete_option($option);
                    }

                    // Delete transients for this site
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                            '_transient_wpe_rm_%',
                            '_transient_timeout_wpe_rm_%'
                        )
                    );
                } finally {
                    restore_current_blog();
                }
            }

            // Remove network options if any
            delete_site_option('wpe_rm_network_settings');
        }
    }

    // =========================================================================
    // Remove Scheduled Cron Events (always clean these up)
    // =========================================================================
    $cron_hooks = [
        'wpe_rm_process_webhook_queue',
    ];

    foreach ($cron_hooks as $hook) {
        $timestamp = wp_next_scheduled($hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
        }
        // Also clear any other scheduled instances
        wp_unschedule_hook($hook);
    }

    // =========================================================================
    // Optionally Remove Roles Created by Plugin
    // =========================================================================
    if ($remove_roles && !empty($created_roles)) {
        foreach ($created_roles as $role_slug) {
            // Check if role exists before removing
            if (get_role($role_slug)) {
                // Remove role from all users first
                $users = get_users(['role' => $role_slug]);
                foreach ($users as $user) {
                    $user->remove_role($role_slug);
                }
                // Remove the role
                remove_role($role_slug);
            }
        }
    }

    // =========================================================================
    // Optionally Remove Capabilities Created by Plugin
    // =========================================================================
    if ($remove_caps && !empty($created_caps)) {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // Remove each created capability from all roles
        foreach ($created_caps as $capability) {
            foreach ($wp_roles->role_objects as $role) {
                if ($role->has_cap($capability)) {
                    $role->remove_cap($capability);
                }
            }
        }
    }

    // =========================================================================
    // Clear Object Caches
    // =========================================================================
    wp_cache_flush();
}

// Execute uninstall
wpe_rm_uninstall();
