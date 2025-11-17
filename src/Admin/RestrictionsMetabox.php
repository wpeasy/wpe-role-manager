<?php
/**
 * Restrictions Metabox Handler
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Admin;

defined('ABSPATH') || exit;

/**
 * Register and handle content restrictions metabox.
 *
 * @since 0.1.2-beta
 */
final class RestrictionsMetabox {
    /**
     * Initialize hooks.
     *
     * @return void
     */
    public static function init(): void {
        // Check if metabox is enabled in settings
        $settings = get_option('wpe_rm_settings', []);
        $enabled = $settings['enable_restrictions_metabox'] ?? false;

        if (!$enabled) {
            return;
        }

        // Register metabox for all post types
        add_action('add_meta_boxes', [self::class, 'register_metabox']);

        // Save metabox data
        add_action('save_post', [self::class, 'save_metabox'], 10, 2);

        // Enforce restrictions on frontend
        add_action('template_redirect', [self::class, 'enforce_restrictions']);

        // Enqueue Select2 for capability multi-select
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Register the metabox for all post types.
     *
     * @return void
     */
    public static function register_metabox(): void {
        $post_types = get_post_types(['public' => true], 'names');

        foreach ($post_types as $post_type) {
            add_meta_box(
                'wpe_rm_restrictions',
                __('Content Restrictions', WPE_RM_TEXTDOMAIN),
                [self::class, 'render_metabox'],
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Enqueue assets for the metabox.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public static function enqueue_assets(string $hook): void {
        // Only load on post edit screens
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        // Enqueue Select2
        wp_enqueue_style(
            'wpe-rm-select2',
            WPE_RM_PLUGIN_URL . 'assets/libs/select2/select2.min.css',
            [],
            '4.1.0-rc.0'
        );

        wp_enqueue_script(
            'wpe-rm-select2',
            WPE_RM_PLUGIN_URL . 'assets/libs/select2/select2.min.js',
            ['jquery'],
            '4.1.0-rc.0',
            true
        );

        // Enqueue custom metabox script
        wp_enqueue_script(
            'wpe-rm-metabox',
            WPE_RM_PLUGIN_URL . 'assets/js/metabox.js',
            ['jquery', 'wpe-rm-select2'],
            WPE_RM_VERSION,
            true
        );

        // Enqueue custom styles
        wp_enqueue_style(
            'wpe-rm-metabox',
            WPE_RM_PLUGIN_URL . 'assets/css/metabox.css',
            [],
            WPE_RM_VERSION
        );
    }

    /**
     * Render the metabox content.
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public static function render_metabox(\WP_Post $post): void {
        // Get current settings
        $enabled = get_post_meta($post->ID, '_wpe_rm_restrictions_enabled', true);
        $include_children = get_post_meta($post->ID, '_wpe_rm_include_children', true);
        $capabilities = get_post_meta($post->ID, '_wpe_rm_required_capabilities', true);
        $action_type = get_post_meta($post->ID, '_wpe_rm_action_type', true);
        $message = get_post_meta($post->ID, '_wpe_rm_message', true);
        $redirect_url = get_post_meta($post->ID, '_wpe_rm_redirect_url', true);

        // Defaults
        $enabled = $enabled === '1' || $enabled === 1;
        $include_children = $include_children === '1' || $include_children === 1;
        $capabilities = is_array($capabilities) ? $capabilities : [];
        $action_type = $action_type ?: 'message';
        $message = $message ?: 'Access Denied';
        $redirect_url = $redirect_url ?: home_url();

        // Get all capabilities
        global $wp_roles;
        $all_capabilities = [];

        foreach ($wp_roles->roles as $role) {
            if (isset($role['capabilities'])) {
                $all_capabilities = array_merge($all_capabilities, array_keys($role['capabilities']));
            }
        }

        $all_capabilities = array_unique($all_capabilities);
        sort($all_capabilities);

        // Nonce field
        wp_nonce_field('wpe_rm_restrictions_metabox', 'wpe_rm_restrictions_nonce');
        ?>

        <div class="wpe-rm-metabox">
            <!-- Enable Restrictions -->
            <p>
                <label>
                    <input
                        type="checkbox"
                        name="wpe_rm_restrictions_enabled"
                        value="1"
                        <?php checked($enabled); ?>
                    />
                    <?php esc_html_e('Enable restrictions', WPE_RM_TEXTDOMAIN); ?>
                </label>
            </p>

            <div class="wpe-rm-restrictions-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                <!-- Include Children -->
                <?php if ($post->post_type === 'page') : ?>
                    <p>
                        <label>
                            <input
                                type="checkbox"
                                name="wpe_rm_include_children"
                                value="1"
                                <?php checked($include_children); ?>
                            />
                            <?php esc_html_e('Include child pages', WPE_RM_TEXTDOMAIN); ?>
                        </label>
                    </p>
                <?php endif; ?>

                <!-- Required Capabilities -->
                <p>
                    <label for="wpe_rm_capabilities">
                        <?php esc_html_e('Required capabilities:', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                    <select
                        id="wpe_rm_capabilities"
                        name="wpe_rm_capabilities[]"
                        multiple="multiple"
                        style="width: 100%;"
                    >
                        <?php foreach ($all_capabilities as $capability) : ?>
                            <option
                                value="<?php echo esc_attr($capability); ?>"
                                <?php selected(in_array($capability, $capabilities, true)); ?>
                            >
                                <?php echo esc_html($capability); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 4px; color: #646970;">
                        <?php esc_html_e('User must have at least one of these capabilities to view this content.', WPE_RM_TEXTDOMAIN); ?>
                    </small>
                </p>

                <!-- Action Type -->
                <p>
                    <strong><?php esc_html_e('If access denied:', WPE_RM_TEXTDOMAIN); ?></strong>
                </p>
                <p>
                    <label>
                        <input
                            type="radio"
                            name="wpe_rm_action_type"
                            value="message"
                            <?php checked($action_type, 'message'); ?>
                        />
                        <?php esc_html_e('Show message', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                    <br/>
                    <label>
                        <input
                            type="radio"
                            name="wpe_rm_action_type"
                            value="redirect"
                            <?php checked($action_type, 'redirect'); ?>
                        />
                        <?php esc_html_e('Redirect to URL', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                </p>

                <!-- Message Field -->
                <p class="wpe-rm-message-field" style="<?php echo $action_type === 'message' ? '' : 'display:none;'; ?>">
                    <label for="wpe_rm_message">
                        <?php esc_html_e('Message:', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                    <textarea
                        id="wpe_rm_message"
                        name="wpe_rm_message"
                        rows="3"
                        style="width: 100%;"
                    ><?php echo esc_textarea($message); ?></textarea>
                </p>

                <!-- Redirect URL Field -->
                <p class="wpe-rm-redirect-field" style="<?php echo $action_type === 'redirect' ? '' : 'display:none;'; ?>">
                    <label for="wpe_rm_redirect_url">
                        <?php esc_html_e('Redirect URL:', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                    <input
                        type="url"
                        id="wpe_rm_redirect_url"
                        name="wpe_rm_redirect_url"
                        value="<?php echo esc_url($redirect_url); ?>"
                        style="width: 100%;"
                        placeholder="<?php echo esc_attr(home_url()); ?>"
                    />
                </p>
            </div>
        </div>

        <?php
    }

    /**
     * Save metabox data.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @return void
     */
    public static function save_metabox(int $post_id, \WP_Post $post): void {
        // Verify nonce
        if (!isset($_POST['wpe_rm_restrictions_nonce']) ||
            !wp_verify_nonce(wp_unslash($_POST['wpe_rm_restrictions_nonce']), 'wpe_rm_restrictions_metabox')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save enabled status
        $enabled = isset($_POST['wpe_rm_restrictions_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_wpe_rm_restrictions_enabled', $enabled);

        // Save include children
        $include_children = isset($_POST['wpe_rm_include_children']) ? '1' : '0';
        update_post_meta($post_id, '_wpe_rm_include_children', $include_children);

        // Save capabilities
        $capabilities = isset($_POST['wpe_rm_capabilities']) && is_array($_POST['wpe_rm_capabilities'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['wpe_rm_capabilities']))
            : [];
        update_post_meta($post_id, '_wpe_rm_required_capabilities', $capabilities);

        // Save action type
        $action_type = isset($_POST['wpe_rm_action_type'])
            ? sanitize_text_field(wp_unslash($_POST['wpe_rm_action_type']))
            : 'message';
        update_post_meta($post_id, '_wpe_rm_action_type', $action_type);

        // Save message
        $message = isset($_POST['wpe_rm_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['wpe_rm_message']))
            : 'Access Denied';
        update_post_meta($post_id, '_wpe_rm_message', $message);

        // Save redirect URL
        $redirect_url = isset($_POST['wpe_rm_redirect_url'])
            ? esc_url_raw(wp_unslash($_POST['wpe_rm_redirect_url']))
            : home_url();
        update_post_meta($post_id, '_wpe_rm_redirect_url', $redirect_url);
    }

    /**
     * Enforce restrictions on frontend.
     *
     * @return void
     */
    public static function enforce_restrictions(): void {
        // Administrators always have access - skip restrictions
        if (current_user_can('manage_options')) {
            return;
        }

        // Only on singular pages
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        // Get restriction data (checks current page and parent pages with include_children)
        $restriction = self::get_restriction_data($post_id);
        if (!$restriction) {
            return;
        }

        // Get required capabilities
        $capabilities = $restriction['capabilities'];
        if (!is_array($capabilities) || empty($capabilities)) {
            return;
        }

        // Check if user has any of the required capabilities
        $has_access = false;

        if (is_user_logged_in()) {
            foreach ($capabilities as $capability) {
                if (current_user_can($capability)) {
                    $has_access = true;
                    break;
                }
            }
        }

        // If user has access, allow
        if ($has_access) {
            return;
        }

        // User doesn't have access - take action
        $action_type = $restriction['action_type'];

        if ($action_type === 'redirect') {
            // Redirect to URL
            $redirect_url = $restriction['redirect_url'] ?: home_url();

            wp_redirect($redirect_url);
            exit;
        } else {
            // Show message
            $message = $restriction['message'] ?: 'Access Denied';

            wp_die(
                '<h1>' . esc_html__('Restricted Content', WPE_RM_TEXTDOMAIN) . '</h1>' .
                '<p>' . esc_html($message) . '</p>',
                esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                ['response' => 403, 'back_link' => true]
            );
        }
    }

    /**
     * Check if a page is restricted (including parent pages if include_children is enabled).
     *
     * @param int $post_id Post ID to check.
     * @return array|false Restriction data if restricted, false otherwise.
     */
    public static function get_restriction_data(int $post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        // Check direct restriction
        $enabled = get_post_meta($post_id, '_wpe_rm_restrictions_enabled', true);
        if ($enabled === '1' || $enabled === 1) {
            return [
                'post_id' => $post_id,
                'capabilities' => get_post_meta($post_id, '_wpe_rm_required_capabilities', true),
                'action_type' => get_post_meta($post_id, '_wpe_rm_action_type', true),
                'message' => get_post_meta($post_id, '_wpe_rm_message', true),
                'redirect_url' => get_post_meta($post_id, '_wpe_rm_redirect_url', true),
            ];
        }

        // Check if any parent page has restrictions with include_children enabled
        if ($post->post_type === 'page' && $post->post_parent) {
            $parent_id = $post->post_parent;

            while ($parent_id) {
                $parent_enabled = get_post_meta($parent_id, '_wpe_rm_restrictions_enabled', true);
                $include_children = get_post_meta($parent_id, '_wpe_rm_include_children', true);

                if (($parent_enabled === '1' || $parent_enabled === 1) &&
                    ($include_children === '1' || $include_children === 1)) {
                    return [
                        'post_id' => $parent_id,
                        'capabilities' => get_post_meta($parent_id, '_wpe_rm_required_capabilities', true),
                        'action_type' => get_post_meta($parent_id, '_wpe_rm_action_type', true),
                        'message' => get_post_meta($parent_id, '_wpe_rm_message', true),
                        'redirect_url' => get_post_meta($parent_id, '_wpe_rm_redirect_url', true),
                    ];
                }

                $parent = get_post($parent_id);
                $parent_id = $parent ? $parent->post_parent : 0;
            }
        }

        return false;
    }
}
