<?php
/**
 * Database Schema
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Database;

defined('ABSPATH') || exit;

/**
 * Handle database schema creation and updates.
 *
 * @since 0.0.8-alpha
 */
final class Schema {
    /**
     * Create all required database tables.
     *
     * @return void
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        // Check if table already exists
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name) {
            return;
        }

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            revision_type varchar(50) NOT NULL,
            action varchar(50) NOT NULL,
            note text NOT NULL,
            snapshot longtext NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY revision_type (revision_type),
            KEY action (action),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Store the schema version
        update_option('wpe_rm_db_version', WPE_RM_VERSION);
    }

    /**
     * Drop all plugin tables (used during uninstall).
     *
     * @return void
     */
    public static function drop_tables(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

        delete_option('wpe_rm_db_version');
    }
}
