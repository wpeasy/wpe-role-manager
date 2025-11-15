<?php
/**
 * Revisions Helper
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Helpers;

defined('ABSPATH') || exit;

/**
 * Helper class for managing revisions.
 *
 * @since 0.0.8-alpha
 */
final class Revisions {
    /**
     * Get complete snapshot of all roles and their capabilities.
     *
     * @return array Complete state of all roles and capabilities.
     */
    public static function get_complete_snapshot(): array {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $disabled_caps = get_option('wpe_rm_disabled_caps', []);
        $plugin_created_roles = get_option('wpe_rm_created_roles', []);
        $plugin_created_caps = get_option('wpe_rm_created_caps', []);
        $plugin_managed_caps = get_option('wpe_rm_managed_role_caps', []);

        $snapshot = [
            'timestamp' => current_time('mysql'),
            'roles' => [],
            'plugin_metadata' => [
                'created_roles' => $plugin_created_roles,
                'created_caps' => $plugin_created_caps,
                'managed_role_caps' => $plugin_managed_caps,
                'disabled_roles' => $disabled_roles,
                'disabled_caps' => $disabled_caps,
            ],
        ];

        foreach ($wp_roles->roles as $slug => $role_data) {
            $is_core = in_array($slug, ['administrator', 'editor', 'author', 'contributor', 'subscriber'], true);
            $is_plugin_created = in_array($slug, $plugin_created_roles, true);

            $snapshot['roles'][$slug] = [
                'slug' => $slug,
                'name' => $role_data['name'],
                'capabilities' => $role_data['capabilities'] ?? [],
                'isCore' => $is_core,
                'isExternal' => !$is_core && !$is_plugin_created,
                'disabled' => in_array($slug, $disabled_roles, true),
                'disabled_caps' => $disabled_caps[$slug] ?? [],
            ];
        }

        return $snapshot;
    }

    /**
     * Save a revision before making changes.
     *
     * @param string $revision_type Type of revision (role, capability, user_roles).
     * @param string $action Action being performed (created, deleted, modified, assigned, unassigned).
     * @param string $note Human-readable description of the change.
     * @param array  $snapshot Current state before the change.
     * @return int|false Revision ID on success, false on failure.
     */
    public static function save(string $revision_type, string $action, string $note, array $snapshot): int|false {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';
        $user_id = get_current_user_id();

        // Insert the revision
        $result = $wpdb->insert(
            $table_name,
            [
                'revision_type' => sanitize_key($revision_type),
                'action' => sanitize_key($action),
                'note' => sanitize_text_field($note),
                'snapshot' => wp_json_encode($snapshot),
                'user_id' => $user_id,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        if (!$result) {
            return false;
        }

        $revision_id = $wpdb->insert_id;

        // Enforce revision limit
        self::enforce_limit();

        return $revision_id;
    }

    /**
     * Get all revisions with optional filtering.
     *
     * @param array $args {
     *     Optional. Array of query parameters.
     *
     *     @type string $revision_type Filter by revision type.
     *     @type string $action        Filter by action.
     *     @type int    $limit          Number of results to return (default 100).
     *     @type int    $offset         Offset for pagination (default 0).
     * }
     * @return array Array of revision objects.
     */
    public static function get_all(array $args = []): array {
        global $wpdb;

        $defaults = [
            'revision_type' => '',
            'action' => '',
            'limit' => 100,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        $where = ['1=1'];
        $where_values = [];

        if (!empty($args['revision_type'])) {
            $where[] = 'revision_type = %s';
            $where_values[] = $args['revision_type'];
        }

        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $where_values[] = $args['action'];
        }

        $where_clause = implode(' AND ', $where);
        $where_values[] = (int) $args['limit'];
        $where_values[] = (int) $args['offset'];

        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        if (!$results) {
            return [];
        }

        // Decode snapshots and enrich with user data
        foreach ($results as &$revision) {
            $revision['snapshot'] = json_decode($revision['snapshot'], true);
            $user = get_userdata($revision['user_id']);
            $revision['user_name'] = $user ? $user->display_name : __('Unknown User', WPE_RM_TEXTDOMAIN);
        }

        return $results;
    }

    /**
     * Get a single revision by ID.
     *
     * @param int $revision_id Revision ID.
     * @return array|null Revision data or null if not found.
     */
    public static function get(int $revision_id): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        $revision = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $revision_id),
            ARRAY_A
        );

        if (!$revision) {
            return null;
        }

        // Decode snapshot
        $revision['snapshot'] = json_decode($revision['snapshot'], true);

        // Get user name
        $user = get_userdata($revision['user_id']);
        $revision['user_name'] = $user ? $user->display_name : __('Unknown User', WPE_RM_TEXTDOMAIN);

        return $revision;
    }

    /**
     * Delete a revision.
     *
     * @param int $revision_id Revision ID.
     * @return bool True on success, false on failure.
     */
    public static function delete(int $revision_id): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        $result = $wpdb->delete(
            $table_name,
            ['id' => $revision_id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete all revisions.
     *
     * @return bool True on success, false on failure.
     */
    public static function delete_all(): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        $result = $wpdb->query("TRUNCATE TABLE {$table_name}");

        return $result !== false;
    }

    /**
     * Restore a revision.
     *
     * @param int $revision_id Revision ID.
     * @return bool True on success, false on failure.
     */
    public static function restore(int $revision_id): bool {
        $revision = self::get($revision_id);

        if (!$revision || !isset($revision['snapshot'])) {
            return false;
        }

        $snapshot = $revision['snapshot'];
        $revision_type = $revision['revision_type'];

        try {
            switch ($revision_type) {
                case 'role':
                    return self::restore_role($snapshot);

                case 'capability':
                    return self::restore_capability($snapshot);

                case 'user_roles':
                    return self::restore_user_roles($snapshot);

                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log('WPE RM Revision Restore Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore all roles from complete snapshot.
     *
     * @param array $snapshot Complete state snapshot.
     * @return bool True on success, false on failure.
     */
    private static function restore_role(array $snapshot): bool {
        if (!isset($snapshot['roles']) || !is_array($snapshot['roles'])) {
            return false;
        }

        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        // Restore plugin metadata if present in snapshot
        if (isset($snapshot['plugin_metadata']) && is_array($snapshot['plugin_metadata'])) {
            $metadata = $snapshot['plugin_metadata'];

            if (isset($metadata['created_roles'])) {
                update_option('wpe_rm_created_roles', $metadata['created_roles']);
            }

            if (isset($metadata['created_caps'])) {
                update_option('wpe_rm_created_caps', $metadata['created_caps']);
            }

            if (isset($metadata['managed_role_caps'])) {
                update_option('wpe_rm_managed_role_caps', $metadata['managed_role_caps']);
            }

            if (isset($metadata['disabled_roles'])) {
                update_option('wpe_rm_disabled_roles', $metadata['disabled_roles']);
            }

            if (isset($metadata['disabled_caps'])) {
                update_option('wpe_rm_disabled_caps', $metadata['disabled_caps']);
            }
        }

        // Restore each role from snapshot
        foreach ($snapshot['roles'] as $role_slug => $role_data) {
            $role_name = $role_data['name'] ?? '';
            $capabilities = $role_data['capabilities'] ?? [];

            $role = get_role($role_slug);

            if ($role) {
                // Remove all current capabilities
                foreach (array_keys($role->capabilities) as $cap) {
                    $role->remove_cap($cap);
                }

                // Add back the snapshot capabilities
                foreach ($capabilities as $cap => $granted) {
                    if ($granted) {
                        $role->add_cap($cap);
                    }
                }
            } else {
                // Create the role with capabilities
                $role = add_role($role_slug, $role_name, $capabilities);

                if (!$role) {
                    continue; // Skip this role but continue with others
                }
            }
        }

        // Log the restoration
        Logger::log('Revision Restored', 'Restored complete roles snapshot from revision');

        return true;
    }

    /**
     * Restore capability assignments from complete snapshot.
     *
     * @param array $snapshot Complete state snapshot.
     * @return bool True on success, false on failure.
     */
    private static function restore_capability(array $snapshot): bool {
        // Capabilities are restored as part of role restoration
        return self::restore_role($snapshot);
    }

    /**
     * Restore user roles from snapshot.
     *
     * @param array $snapshot User roles snapshot data.
     * @return bool True on success, false on failure.
     */
    private static function restore_user_roles(array $snapshot): bool {
        $user_id = $snapshot['user_id'] ?? 0;
        $roles = $snapshot['roles'] ?? [];

        if (empty($user_id)) {
            return false;
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Remove all current roles
        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }

        // Add back the snapshot roles
        foreach ($roles as $role) {
            $user->add_role($role);
        }

        // Log the restoration
        Logger::log(
            'Revision Restored',
            sprintf('Restored roles for user "%s" (ID: %d) from revision', $user->user_login, $user_id)
        );

        return true;
    }

    /**
     * Enforce the revision limit setting.
     *
     * @return void
     */
    private static function enforce_limit(): void {
        global $wpdb;

        $settings = get_option('wpe_rm_settings', ['revision_retention' => 300]);
        $limit = (int) ($settings['revision_retention'] ?? 300);

        if ($limit <= 0) {
            return;
        }

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        // Count total revisions
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        if ($count > $limit) {
            // Delete oldest revisions beyond the limit
            $to_delete = $count - $limit;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} ORDER BY created_at ASC LIMIT %d",
                    $to_delete
                )
            );
        }
    }

    /**
     * Get unique revision types for filtering.
     *
     * @return array Array of revision type strings.
     */
    public static function get_revision_types(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        $types = $wpdb->get_col("SELECT DISTINCT revision_type FROM {$table_name} ORDER BY revision_type ASC");

        return $types ?: [];
    }

    /**
     * Get unique actions for filtering.
     *
     * @return array Array of action strings.
     */
    public static function get_actions(): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wpe_rm_revisions';

        $actions = $wpdb->get_col("SELECT DISTINCT action FROM {$table_name} ORDER BY action ASC");

        return $actions ?: [];
    }
}
