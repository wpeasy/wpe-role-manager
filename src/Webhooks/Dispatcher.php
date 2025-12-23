<?php
/**
 * Webhook Dispatcher
 *
 * Hooks into WordPress actions and queues webhooks for delivery.
 *
 * @package WP_Easy\RoleManager\Webhooks
 */

namespace WP_Easy\RoleManager\Webhooks;

defined('ABSPATH') || exit;

/**
 * Dispatcher class for queueing webhooks.
 */
final class Dispatcher {

    /**
     * Option key for webhook queue.
     */
    private const QUEUE_KEY = 'wpe_rm_webhooks_queue';

    /**
     * Initialize dispatcher - register all action hooks.
     *
     * @return void
     */
    public static function init(): void {
        // Role events
        add_action('wpe_rm_after_role_create', [self::class, 'on_role_created'], 10, 4);
        add_action('wpe_rm_after_role_update', [self::class, 'on_role_updated'], 10, 3);
        add_action('wpe_rm_after_role_delete', [self::class, 'on_role_deleted'], 10, 3);

        // Capability events
        add_action('wpe_rm_after_capability_add', [self::class, 'on_capability_added'], 10, 3);
        add_action('wpe_rm_after_capability_remove', [self::class, 'on_capability_removed'], 10, 2);
        add_action('wpe_rm_after_capability_toggle', [self::class, 'on_capability_toggled'], 10, 4);

        // User events
        add_action('wpe_rm_after_user_roles_update', [self::class, 'on_user_roles_updated'], 10, 4);

        // Import events
        add_action('wpe_rm_after_import', [self::class, 'on_import_completed'], 10, 4);

        // Settings events
        add_action('wpe_rm_after_settings_update', [self::class, 'on_settings_updated'], 10, 3);
    }

    /**
     * Handle role created event.
     *
     * @param string $slug      Role slug.
     * @param string $name      Role name.
     * @param object $role      WP_Role object.
     * @param string $copy_from Source role slug.
     * @return void
     */
    public static function on_role_created(string $slug, string $name, $role, string $copy_from): void {
        self::dispatch('role:created', [
            'slug' => $slug,
            'name' => $name,
            'copy_from' => $copy_from ?: null,
            'capabilities' => $role ? array_keys(array_filter($role->capabilities)) : [],
        ]);
    }

    /**
     * Handle role updated event.
     *
     * @param string $slug    Role slug.
     * @param array  $params  Update parameters.
     * @param bool   $success Whether update succeeded.
     * @return void
     */
    public static function on_role_updated(string $slug, array $params, bool $success): void {
        if (!$success) {
            return;
        }

        // Determine what kind of update
        $event = 'role:updated';
        $data = [
            'slug' => $slug,
            'changes' => $params,
        ];

        // Check if this was an enable/disable action
        if (isset($params['disabled'])) {
            $event = $params['disabled'] ? 'role:disabled' : 'role:enabled';
            $data = ['slug' => $slug];
        }

        self::dispatch($event, $data);
    }

    /**
     * Handle role deleted event.
     *
     * @param string $slug      Role slug.
     * @param array  $role_data Role data before deletion.
     * @param array  $caps      Capabilities that were removed.
     * @return void
     */
    public static function on_role_deleted(string $slug, array $role_data, array $caps): void {
        self::dispatch('role:deleted', [
            'slug' => $slug,
            'name' => $role_data['name'] ?? $slug,
            'capabilities_removed' => count($caps),
        ]);
    }

    /**
     * Handle capability added event.
     *
     * @param string $role       Role slug.
     * @param string $capability Capability name.
     * @param bool   $grant      Whether capability was granted.
     * @return void
     */
    public static function on_capability_added(string $role, string $capability, bool $grant): void {
        self::dispatch('capability:added', [
            'role' => $role,
            'capability' => $capability,
            'grant' => $grant,
        ]);
    }

    /**
     * Handle capability removed event.
     *
     * @param string $role       Role slug.
     * @param string $capability Capability name.
     * @return void
     */
    public static function on_capability_removed(string $role, string $capability): void {
        self::dispatch('capability:removed', [
            'role' => $role,
            'capability' => $capability,
        ]);
    }

    /**
     * Handle capability toggled event.
     *
     * @param string $role       Role slug.
     * @param string $capability Capability name.
     * @param string $action     Toggle action (grant/deny/unset).
     * @param bool   $success    Whether toggle succeeded.
     * @return void
     */
    public static function on_capability_toggled(string $role, string $capability, string $action, bool $success): void {
        if (!$success) {
            return;
        }

        self::dispatch('capability:toggled', [
            'role' => $role,
            'capability' => $capability,
            'action' => $action,
        ]);
    }

    /**
     * Handle user roles updated event.
     *
     * @param int    $user_id   User ID.
     * @param array  $roles     New roles.
     * @param array  $old_roles Previous roles.
     * @param object $user      WP_User object.
     * @return void
     */
    public static function on_user_roles_updated(int $user_id, array $roles, array $old_roles, $user): void {
        self::dispatch('user:roles_updated', [
            'user_id' => $user_id,
            'user_login' => $user->user_login ?? '',
            'user_email' => $user->user_email ?? '',
            'roles' => $roles,
            'previous_roles' => $old_roles,
            'roles_added' => array_values(array_diff($roles, $old_roles)),
            'roles_removed' => array_values(array_diff($old_roles, $roles)),
        ]);
    }

    /**
     * Handle import completed event.
     *
     * @param int   $imported_count Number of roles imported.
     * @param array $restored_caps  Capabilities restored.
     * @param array $errors         Any errors during import.
     * @param array $messages       Messages from import.
     * @return void
     */
    public static function on_import_completed(int $imported_count, array $restored_caps, array $errors, array $messages): void {
        self::dispatch('import:completed', [
            'roles_imported' => $imported_count,
            'capabilities_restored' => count($restored_caps),
            'errors' => count($errors),
            'success' => empty($errors),
        ]);
    }

    /**
     * Handle settings updated event.
     *
     * @param array $settings     New settings.
     * @param array $old_settings Previous settings.
     * @param array $changes      Changed settings.
     * @return void
     */
    public static function on_settings_updated(array $settings, array $old_settings, array $changes): void {
        self::dispatch('settings:updated', [
            'changes' => $changes,
        ]);
    }

    /**
     * Dispatch an event to all subscribed webhooks.
     *
     * @param string $event Event name.
     * @param array  $data  Event data.
     * @return void
     */
    public static function dispatch(string $event, array $data): void {
        $webhooks = Manager::get_webhooks_for_event($event);

        if (empty($webhooks)) {
            return;
        }

        foreach ($webhooks as $webhook) {
            self::queue($webhook['id'], $event, $data);
        }
    }

    /**
     * Queue a webhook for delivery.
     *
     * @param string $webhook_id Webhook ID.
     * @param string $event      Event name.
     * @param array  $data       Event data.
     * @return void
     */
    public static function queue(string $webhook_id, string $event, array $data): void {
        $queue = self::get_queue();

        $entry = [
            'id' => 'q_' . bin2hex(random_bytes(8)),
            'webhook_id' => $webhook_id,
            'event' => $event,
            'payload' => [
                'event' => $event,
                'timestamp' => gmdate('c'),
                'site_url' => home_url(),
                'data' => $data,
            ],
            'attempts' => 0,
            'next_attempt' => time(),
            'created' => time(),
            'status' => 'pending',
        ];

        $queue[] = $entry;
        update_option(self::QUEUE_KEY, $queue);

        // Update last triggered timestamp
        Manager::update_last_triggered($webhook_id);
    }

    /**
     * Get the webhook queue.
     *
     * @return array Queue entries.
     */
    public static function get_queue(): array {
        return get_option(self::QUEUE_KEY, []);
    }

    /**
     * Update the queue.
     *
     * @param array $queue Updated queue.
     * @return void
     */
    public static function update_queue(array $queue): void {
        update_option(self::QUEUE_KEY, $queue);
    }

    /**
     * Remove an entry from the queue.
     *
     * @param string $entry_id Queue entry ID.
     * @return void
     */
    public static function remove_from_queue(string $entry_id): void {
        $queue = self::get_queue();
        $queue = array_filter($queue, fn($e) => $e['id'] !== $entry_id);
        self::update_queue(array_values($queue));
    }

    /**
     * Clear the entire queue.
     *
     * @return bool True on success.
     */
    public static function clear_queue(): bool {
        return delete_option(self::QUEUE_KEY);
    }

    /**
     * Get queue entries ready for processing.
     *
     * @param int $limit Maximum entries to return.
     * @return array Queue entries ready for processing.
     */
    public static function get_pending_entries(int $limit = 10): array {
        $queue = self::get_queue();
        $pending = [];
        $now = time();

        foreach ($queue as $entry) {
            if ($entry['status'] === 'pending' && $entry['next_attempt'] <= $now) {
                $pending[] = $entry;

                if (count($pending) >= $limit) {
                    break;
                }
            }
        }

        return $pending;
    }

    /**
     * Update a queue entry's status.
     *
     * @param string $entry_id    Queue entry ID.
     * @param string $status      New status.
     * @param int    $attempts    Updated attempt count.
     * @param int    $next_attempt Next attempt timestamp (0 = now).
     * @return void
     */
    public static function update_entry_status(string $entry_id, string $status, int $attempts = 0, int $next_attempt = 0): void {
        $queue = self::get_queue();

        foreach ($queue as &$entry) {
            if ($entry['id'] === $entry_id) {
                $entry['status'] = $status;
                $entry['attempts'] = $attempts;
                $entry['next_attempt'] = $next_attempt;
                break;
            }
        }

        self::update_queue($queue);
    }
}
