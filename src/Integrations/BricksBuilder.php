<?php
/**
 * Bricks Builder Integration
 *
 * Provides custom element conditions and dynamic data tags for Bricks Builder
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Integrations;

defined('ABSPATH') || exit;

/**
 * Bricks Builder Integration Class
 */
final class BricksBuilder {
    /**
     * Initialize Bricks Builder integration.
     *
     * @return void
     */
    public static function init(): void {
        // Hook into after_setup_theme to ensure Bricks is loaded
        add_action('after_setup_theme', [self::class, 'register_bricks_integration'], 20);
    }

    /**
     * Register Bricks Builder integration after theme is loaded.
     *
     * @return void
     */
    public static function register_bricks_integration(): void {
        // Only load if Bricks is active
        if (!defined('BRICKS_VERSION')) {
            return;
        }

        // Register custom condition group
        add_filter('bricks/conditions/groups', [self::class, 'register_condition_group'], 10, 1);

        // Register custom conditions
        add_filter('bricks/conditions/options', [self::class, 'register_conditions'], 10, 1);

        // Hook into condition evaluation
        add_filter('bricks/conditions/result', [self::class, 'evaluate_condition'], 10, 3);

        // Register custom dynamic data tags
        add_filter('bricks/dynamic_tags_list', [self::class, 'register_dynamic_tags']);
        add_filter('bricks/dynamic_data/render_tag', [self::class, 'render_dynamic_tag'], 10, 3);
        add_filter('bricks/dynamic_data/render_content', [self::class, 'render_dynamic_content'], 10, 3);
    }

    /**
     * Register custom condition group in Bricks Builder.
     *
     * @param array $groups Existing groups.
     * @return array Modified groups array.
     */
    public static function register_condition_group(array $groups): array {
        $groups[] = [
            'name'  => 'role_manager',
            'label' => __('Role Manager', 'wp-easy-role-manager'),
        ];

        return $groups;
    }

    /**
     * Register custom conditions in Bricks Builder.
     *
     * @param array $options Existing conditions.
     * @return array Modified conditions array.
     */
    public static function register_conditions(array $options): array {
        // Condition: Current user has capability
        $options[] = [
            'key'     => 'wpe_rm_user_capability',
            'group'   => 'role_manager',
            'label'   => __('User Has Capability', 'wp-easy-role-manager'),
            'compare' => [
                'type'        => 'select',
                'options'     => [
                    '==' => __('has', 'wp-easy-role-manager'),
                    '!=' => __('does not have', 'wp-easy-role-manager'),
                ],
                'placeholder' => __('has', 'wp-easy-role-manager'),
            ],
            'value'   => [
                'type'        => 'text',
                'placeholder' => __('Capability name (e.g. edit_posts)', 'wp-easy-role-manager'),
            ],
        ];

        // Condition: Specific user has capability
        $options[] = [
            'key'     => 'wpe_rm_user_capability_for_user',
            'group'   => 'role_manager',
            'label'   => __('Specific User Has Capability', 'wp-easy-role-manager'),
            'compare' => [
                'type'        => 'select',
                'options'     => [
                    '==' => __('has', 'wp-easy-role-manager'),
                    '!=' => __('does not have', 'wp-easy-role-manager'),
                ],
                'placeholder' => __('has', 'wp-easy-role-manager'),
            ],
            'value'   => [
                'type'        => 'text',
                'placeholder' => __('capability:user_id (e.g. edit_posts:5)', 'wp-easy-role-manager'),
            ],
        ];

        return $options;
    }

    /**
     * Evaluate custom conditions.
     *
     * @param bool   $render_set Current render decision.
     * @param string $key        Condition key.
     * @param array  $condition  Condition data.
     * @return bool True if condition is met, false otherwise.
     */
    public static function evaluate_condition(bool $render_set, string $key, array $condition): bool {
        // Only handle our conditions
        if ($key !== 'wpe_rm_user_capability' && $key !== 'wpe_rm_user_capability_for_user') {
            return $render_set;
        }

        $compare = $condition['compare'] ?? '==';
        $value = $condition['value'] ?? '';

        if (empty($value)) {
            return false;
        }

        $has_capability = false;

        if ($key === 'wpe_rm_user_capability') {
            // Check current user
            $capability = sanitize_key($value);
            $has_capability = is_user_logged_in() && current_user_can($capability);
        } elseif ($key === 'wpe_rm_user_capability_for_user') {
            // Check specific user
            $parts = explode(':', $value);

            if (count($parts) === 2) {
                $capability = sanitize_key($parts[0]);
                $user_id = absint($parts[1]);
                $has_capability = $user_id && user_can($user_id, $capability);
            }
        }

        // Apply comparison
        if ($compare === '==') {
            return $has_capability;
        } elseif ($compare === '!=') {
            return !$has_capability;
        }

        return $render_set;
    }

    /**
     * Register custom dynamic data tags in Bricks Builder.
     *
     * @param array $tags Existing dynamic tags.
     * @return array Modified tags array.
     */
    public static function register_dynamic_tags(array $tags): array {
        $tags[] = [
            'name'  => '{wpe_rm_capability_status}',
            'label' => 'WPE RM: Capability Status',
            'group' => 'Role Manager',
        ];

        return $tags;
    }

    /**
     * Render the dynamic tag value.
     *
     * @param mixed  $tag     The tag being rendered (can be string or array).
     * @param object $post    The post object.
     * @param array  $context Additional context.
     * @return mixed The rendered value.
     */
    public static function render_dynamic_tag($tag, $post, $context) {
        // If tag is not a string, return as-is
        if (!is_string($tag)) {
            return $tag;
        }

        // Check if this is our tag
        if (strpos($tag, 'wpe_rm_capability_status') === false) {
            return $tag;
        }

        return self::process_capability_tag($tag);
    }

    /**
     * Render dynamic content (alternative method).
     *
     * @param string $content The content being rendered.
     * @param object $post    The post object.
     * @param string $context The context.
     * @return string The rendered content.
     */
    public static function render_dynamic_content(string $content, $post, string $context): string {
        // Check if content contains our tag
        if (strpos($content, '{wpe_rm_capability_status') === false) {
            return $content;
        }

        // Replace all instances of our tag
        return preg_replace_callback(
            '/\{wpe_rm_capability_status:([^}]+)\}/',
            function ($matches) {
                return self::process_capability_tag($matches[0]);
            },
            $content
        );
    }

    /**
     * Process the capability tag and return the result.
     *
     * Syntax: {wpe_rm_capability_status:cap_name} or {wpe_rm_capability_status:cap_name:user_id}
     *
     * @param string $tag The full tag string.
     * @return string Returns 'granted', 'not-granted', or 'denied'.
     */
    private static function process_capability_tag(string $tag): string {
        // Extract parameters from tag: {wpe_rm_capability_status:cap_name} or {wpe_rm_capability_status:cap_name:user_id}
        if (!preg_match('/\{wpe_rm_capability_status:([^:}]+)(?::(\d+))?\}/', $tag, $matches)) {
            return 'not-granted';
        }

        $capability = sanitize_key($matches[1]);
        $user_id = isset($matches[2]) ? absint($matches[2]) : get_current_user_id();

        // If no user ID, return not-granted
        if (!$user_id) {
            return 'not-granted';
        }

        // Get the user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return 'not-granted';
        }

        // Check if capability is explicitly denied
        if (self::is_capability_denied($user, $capability)) {
            return 'denied';
        }

        // Check if user has the capability
        if (user_can($user, $capability)) {
            return 'granted';
        }

        return 'not-granted';
    }

    /**
     * Check if a capability is explicitly denied for a user.
     *
     * @param \WP_User $user       The user object.
     * @param string   $capability The capability to check.
     * @return bool True if explicitly denied, false otherwise.
     */
    private static function is_capability_denied(\WP_User $user, string $capability): bool {
        // Check each of the user's roles for explicit denial
        foreach ($user->roles as $role_slug) {
            $role = get_role($role_slug);
            if (!$role) {
                continue;
            }

            // Check if capability is explicitly set to false
            if (isset($role->capabilities[$capability]) && $role->capabilities[$capability] === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate Bricks token syntax for testing.
     *
     * @param string $capability The capability name.
     * @param int    $user_id    Optional user ID.
     * @return string The Bricks token syntax.
     */
    public static function generate_token(string $capability, int $user_id = 0): string {
        if ($user_id > 0) {
            return sprintf('{wpe_rm_capability_status:%s:%d}', $capability, $user_id);
        }

        return sprintf('{wpe_rm_capability_status:%s}', $capability);
    }
}
