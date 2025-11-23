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
        // Check if any post types have restrictions enabled
        $settings = get_option('wpe_rm_settings', []);
        $enabled_post_types = $settings['restrictions_enabled_post_types'] ?? [];

        // Handle legacy setting migration
        if (!isset($settings['restrictions_enabled_post_types']) && isset($settings['enable_restrictions_metabox'])) {
            if ($settings['enable_restrictions_metabox']) {
                $enabled_post_types = ['page', 'post'];
            }
        }

        if (empty($enabled_post_types)) {
            return;
        }

        // Register metabox for enabled post types only
        add_action('add_meta_boxes', [self::class, 'register_metabox']);

        // Save metabox data
        add_action('save_post', [self::class, 'save_metabox'], 10, 2);

        // Enforce restrictions on frontend
        add_action('template_redirect', [self::class, 'enforce_restrictions']);

        // Enqueue Select2 for capability multi-select
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);

        // Add custom column to post list tables for enabled post types only
        foreach ($enabled_post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [self::class, 'add_restrictions_column']);
            add_action("manage_{$post_type}_posts_custom_column", [self::class, 'display_restrictions_column'], 10, 2);
        }
    }

    /**
     * Register the metabox for enabled post types.
     *
     * @return void
     */
    public static function register_metabox(): void {
        // Get enabled post types from settings
        $settings = get_option('wpe_rm_settings', []);
        $enabled_post_types = $settings['restrictions_enabled_post_types'] ?? [];

        // Handle legacy setting migration
        if (!isset($settings['restrictions_enabled_post_types']) && isset($settings['enable_restrictions_metabox'])) {
            if ($settings['enable_restrictions_metabox']) {
                $enabled_post_types = ['page', 'post'];
            }
        }

        foreach ($enabled_post_types as $post_type) {
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
        $filter_type = get_post_meta($post->ID, '_wpe_rm_filter_type', true);
        $capabilities = get_post_meta($post->ID, '_wpe_rm_required_capabilities', true);
        $roles = get_post_meta($post->ID, '_wpe_rm_required_roles', true);
        $action_type = get_post_meta($post->ID, '_wpe_rm_action_type', true);
        $message = get_post_meta($post->ID, '_wpe_rm_message', true);
        $message_type = get_post_meta($post->ID, '_wpe_rm_message_type', true);
        $http_code = get_post_meta($post->ID, '_wpe_rm_http_code', true);
        $redirect_url = get_post_meta($post->ID, '_wpe_rm_redirect_url', true);

        // Defaults
        $enabled = $enabled === '1' || $enabled === 1;
        $include_children = $include_children === '1' || $include_children === 1;
        $filter_type = $filter_type ?: 'capability';
        $capabilities = is_array($capabilities) ? $capabilities : [];
        $roles = is_array($roles) ? $roles : [];
        $action_type = $action_type ?: 'message';
        $message = $message ?: 'Access Denied';
        $message_type = $message_type ?: 'wp_die';
        $http_code = $http_code ?: '403';
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

                <!-- Filter Type -->
                <p>
                    <strong><?php esc_html_e('Filter by:', WPE_RM_TEXTDOMAIN); ?></strong>
                </p>
                <p>
                    <label>
                        <input
                            type="radio"
                            name="wpe_rm_filter_type"
                            value="capability"
                            <?php checked($filter_type, 'capability'); ?>
                        />
                        <?php esc_html_e('Capability', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                    <br/>
                    <label>
                        <input
                            type="radio"
                            name="wpe_rm_filter_type"
                            value="role"
                            <?php checked($filter_type, 'role'); ?>
                        />
                        <?php esc_html_e('Role', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                </p>

                <!-- Required Capabilities -->
                <p class="wpe-rm-capability-field" style="<?php echo $filter_type === 'capability' ? '' : 'display:none;'; ?>">
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

                <!-- Required Roles -->
                <p class="wpe-rm-role-field" style="<?php echo $filter_type === 'role' ? '' : 'display:none;'; ?>">
                    <label for="wpe_rm_roles">
                        <?php esc_html_e('Required roles:', WPE_RM_TEXTDOMAIN); ?>
                    </label>
                    <select
                        id="wpe_rm_roles"
                        name="wpe_rm_roles[]"
                        multiple="multiple"
                        style="width: 100%;"
                    >
                        <?php foreach ($wp_roles->role_names as $role_slug => $role_name) : ?>
                            <option
                                value="<?php echo esc_attr($role_slug); ?>"
                                <?php selected(in_array($role_slug, $roles, true)); ?>
                            >
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 4px; color: #646970;">
                        <?php esc_html_e('User must have at least one of these roles to view this content.', WPE_RM_TEXTDOMAIN); ?>
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

                <!-- Message Fields Container -->
                <div class="wpe-rm-message-fields" style="<?php echo $action_type === 'message' ? '' : 'display:none;'; ?>">
                    <!-- Message Type -->
                    <p>
                        <strong><?php esc_html_e('Message display:', WPE_RM_TEXTDOMAIN); ?></strong>
                    </p>
                    <p>
                        <label>
                            <input
                                type="radio"
                                name="wpe_rm_message_type"
                                value="wp_die"
                                <?php checked($message_type, 'wp_die'); ?>
                            />
                            <?php esc_html_e('wp_die (full page)', WPE_RM_TEXTDOMAIN); ?>
                        </label>
                        <br/>
                        <label>
                            <input
                                type="radio"
                                name="wpe_rm_message_type"
                                value="plain_message"
                                <?php checked($message_type, 'plain_message'); ?>
                            />
                            <?php esc_html_e('Plain Message (inline)', WPE_RM_TEXTDOMAIN); ?>
                        </label>
                    </p>

                    <!-- HTTP Code (for Plain Message) -->
                    <p class="wpe-rm-http-code-field" style="<?php echo $message_type === 'plain_message' ? '' : 'display:none;'; ?>">
                        <label for="wpe_rm_http_code">
                            <?php esc_html_e('HTTP Status Code:', WPE_RM_TEXTDOMAIN); ?>
                        </label>
                        <select id="wpe_rm_http_code" name="wpe_rm_http_code" style="width: 100%;">
                            <option value="401" <?php selected($http_code, '401'); ?>>401 - Unauthorized</option>
                            <option value="403" <?php selected($http_code, '403'); ?>>403 - Forbidden</option>
                            <option value="404" <?php selected($http_code, '404'); ?>>404 - Not Found</option>
                            <option value="410" <?php selected($http_code, '410'); ?>>410 - Gone</option>
                        </select>
                        <small style="display: block; margin-top: 4px; color: #646970;">
                            <?php esc_html_e('HTTP response code sent with the message.', WPE_RM_TEXTDOMAIN); ?>
                        </small>
                    </p>

                    <!-- Message Text -->
                    <p class="wpe-rm-message-field">
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
                </div>

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

        // Save filter type
        $filter_type = isset($_POST['wpe_rm_filter_type'])
            ? sanitize_text_field(wp_unslash($_POST['wpe_rm_filter_type']))
            : 'capability';
        update_post_meta($post_id, '_wpe_rm_filter_type', $filter_type);

        // Save capabilities
        $capabilities = isset($_POST['wpe_rm_capabilities']) && is_array($_POST['wpe_rm_capabilities'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['wpe_rm_capabilities']))
            : [];
        update_post_meta($post_id, '_wpe_rm_required_capabilities', $capabilities);

        // Save roles
        $roles = isset($_POST['wpe_rm_roles']) && is_array($_POST['wpe_rm_roles'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['wpe_rm_roles']))
            : [];
        update_post_meta($post_id, '_wpe_rm_required_roles', $roles);

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

        // Save message type
        $message_type = isset($_POST['wpe_rm_message_type'])
            ? sanitize_text_field(wp_unslash($_POST['wpe_rm_message_type']))
            : 'wp_die';
        update_post_meta($post_id, '_wpe_rm_message_type', $message_type);

        // Save HTTP code
        $http_code = isset($_POST['wpe_rm_http_code'])
            ? sanitize_text_field(wp_unslash($_POST['wpe_rm_http_code']))
            : '403';
        // Validate HTTP code
        $valid_codes = ['401', '403', '404', '410'];
        if (!in_array($http_code, $valid_codes, true)) {
            $http_code = '403';
        }
        update_post_meta($post_id, '_wpe_rm_http_code', $http_code);

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

        $filter_type = $restriction['filter_type'] ?: 'capability';
        $has_access = false;

        if ($filter_type === 'role') {
            // Filter by role
            $roles = $restriction['roles'];
            if (!is_array($roles) || empty($roles)) {
                return;
            }

            // Check if user has any of the required roles
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                foreach ($roles as $role) {
                    if (in_array($role, $user->roles, true)) {
                        $has_access = true;
                        break;
                    }
                }
            }
        } else {
            // Filter by capability
            $capabilities = $restriction['capabilities'];
            if (!is_array($capabilities) || empty($capabilities)) {
                return;
            }

            // Check if user has any of the required capabilities
            if (is_user_logged_in()) {
                foreach ($capabilities as $capability) {
                    if (current_user_can($capability)) {
                        $has_access = true;
                        break;
                    }
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
            $message_type = $restriction['message_type'] ?: 'wp_die';
            $http_code = (int) ($restriction['http_code'] ?: 403);

            if ($message_type === 'plain_message') {
                // Plain message - set HTTP status and filter content
                status_header($http_code);

                // Store message for content filter
                $GLOBALS['wpe_rm_restricted_message'] = $message;

                // Filter the content to show restricted message
                add_filter('the_content', function($content) {
                    $message = $GLOBALS['wpe_rm_restricted_message'] ?? __('Access Denied', WPE_RM_TEXTDOMAIN);
                    return '<div id="restricted-content-wrapper">' . esc_html($message) . '</div>';
                }, 999);

                // Also hide the title if desired (optional - keeps page structure)
                return;
            } else {
                // wp_die - full page takeover
                wp_die(
                    '<h1>' . esc_html__('Restricted Content', WPE_RM_TEXTDOMAIN) . '</h1>' .
                    '<p>' . esc_html($message) . '</p>',
                    esc_html__('Access Denied', WPE_RM_TEXTDOMAIN),
                    ['response' => 403, 'back_link' => true]
                );
            }
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
                'filter_type' => get_post_meta($post_id, '_wpe_rm_filter_type', true) ?: 'capability',
                'capabilities' => get_post_meta($post_id, '_wpe_rm_required_capabilities', true),
                'roles' => get_post_meta($post_id, '_wpe_rm_required_roles', true),
                'action_type' => get_post_meta($post_id, '_wpe_rm_action_type', true),
                'message' => get_post_meta($post_id, '_wpe_rm_message', true),
                'message_type' => get_post_meta($post_id, '_wpe_rm_message_type', true),
                'http_code' => get_post_meta($post_id, '_wpe_rm_http_code', true),
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
                        'filter_type' => get_post_meta($parent_id, '_wpe_rm_filter_type', true) ?: 'capability',
                        'capabilities' => get_post_meta($parent_id, '_wpe_rm_required_capabilities', true),
                        'roles' => get_post_meta($parent_id, '_wpe_rm_required_roles', true),
                        'action_type' => get_post_meta($parent_id, '_wpe_rm_action_type', true),
                        'message' => get_post_meta($parent_id, '_wpe_rm_message', true),
                        'message_type' => get_post_meta($parent_id, '_wpe_rm_message_type', true),
                        'http_code' => get_post_meta($parent_id, '_wpe_rm_http_code', true),
                        'redirect_url' => get_post_meta($parent_id, '_wpe_rm_redirect_url', true),
                    ];
                }

                $parent = get_post($parent_id);
                $parent_id = $parent ? $parent->post_parent : 0;
            }
        }

        return false;
    }

    /**
     * Add restrictions column to post list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function add_restrictions_column(array $columns): array {
        // Insert before 'date' column
        $new_columns = [];
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['wpe_rm_restrictions'] = __('Restrictions', WPE_RM_TEXTDOMAIN);
            }
            $new_columns[$key] = $value;
        }
        return $new_columns;
    }

    /**
     * Display content in restrictions column.
     *
     * @param string $column_name Column name.
     * @param int    $post_id     Post ID.
     * @return void
     */
    public static function display_restrictions_column(string $column_name, int $post_id): void {
        if ($column_name !== 'wpe_rm_restrictions') {
            return;
        }

        $enabled = get_post_meta($post_id, '_wpe_rm_restrictions_enabled', true);
        if ($enabled !== '1' && $enabled !== 1) {
            echo '<span style="color: #787c82;">—</span>';
            return;
        }

        $filter_type = get_post_meta($post_id, '_wpe_rm_filter_type', true) ?: 'capability';
        $action_type = get_post_meta($post_id, '_wpe_rm_action_type', true) ?: 'message';
        $include_children = get_post_meta($post_id, '_wpe_rm_include_children', true);

        if ($filter_type === 'role') {
            $roles = get_post_meta($post_id, '_wpe_rm_required_roles', true);
            $items = is_array($roles) ? $roles : [];
        } else {
            $capabilities = get_post_meta($post_id, '_wpe_rm_required_capabilities', true);
            $items = is_array($capabilities) ? $capabilities : [];
        }

        // Format display
        echo '<div style="font-size: 12px; line-height: 1.5;">';

        // Restricted badge
        echo '<div style="margin-bottom: 4px;">';
        echo '<span style="display: inline-block; padding: 2px 6px; background: #d63638; color: #fff; border-radius: 3px; font-weight: 600; font-size: 11px;">';
        echo esc_html__('RESTRICTED', WPE_RM_TEXTDOMAIN);
        echo '</span>';

        // Include children indicator
        if (($include_children === '1' || $include_children === 1) && get_post_type($post_id) === 'page') {
            echo ' <span style="display: inline-block; padding: 2px 6px; background: #2271b1; color: #fff; border-radius: 3px; font-weight: 600; font-size: 11px;">';
            echo esc_html__('+ CHILDREN', WPE_RM_TEXTDOMAIN);
            echo '</span>';
        }
        echo '</div>';

        // Filter type
        echo '<div style="color: #50575e; margin-bottom: 2px;">';
        echo '<strong>' . esc_html__('By:', WPE_RM_TEXTDOMAIN) . '</strong> ';
        echo esc_html($filter_type === 'role' ? __('Role', WPE_RM_TEXTDOMAIN) : __('Capability', WPE_RM_TEXTDOMAIN));
        echo '</div>';

        // Action type
        echo '<div style="color: #50575e; margin-bottom: 2px;">';
        echo '<strong>' . esc_html__('Type:', WPE_RM_TEXTDOMAIN) . '</strong> ';
        echo esc_html($action_type === 'redirect' ? __('Redirect', WPE_RM_TEXTDOMAIN) : __('Message', WPE_RM_TEXTDOMAIN));
        echo '</div>';

        // List items
        if (!empty($items)) {
            echo '<div style="color: #50575e;">';
            echo '<strong>' . esc_html__('List:', WPE_RM_TEXTDOMAIN) . '</strong> ';

            // Show first 2 items, then count
            $display_items = array_slice($items, 0, 2);
            $remaining = count($items) - 2;

            echo '<span style="font-family: monospace; font-size: 11px;">';
            echo esc_html(implode(', ', $display_items));
            if ($remaining > 0) {
                echo ' <span style="color: #787c82;">+' . esc_html($remaining) . ' more</span>';
            }
            echo '</span>';
            echo '</div>';
        }

        echo '</div>';
    }
}
