<?php
/**
 * User Profile Integration
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Admin;

defined('ABSPATH') || exit;

use WP_User;

/**
 * Enhance user profile/edit screen with better role management.
 *
 * @since 0.0.1-alpha
 */
final class UserProfile {
    /**
     * Initialize hooks.
     *
     * @return void
     */
    public static function init(): void {
        // Enqueue assets on user edit screens
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);

        // Remove default WordPress role dropdown
        add_action('admin_head-user-edit.php', [self::class, 'hide_default_role_select']);
        add_action('admin_head-profile.php', [self::class, 'hide_default_role_select']);
        add_action('admin_head-user-new.php', [self::class, 'hide_default_role_select']);

        // Add our custom role selector
        add_action('show_user_profile', [self::class, 'render_role_selector']);
        add_action('edit_user_profile', [self::class, 'render_role_selector']);
        add_action('user_new_form', [self::class, 'render_role_selector_new_user']);

        // Save custom role assignments
        add_action('personal_options_update', [self::class, 'save_role_assignments']);
        add_action('edit_user_profile_update', [self::class, 'save_role_assignments']);
        add_action('user_register', [self::class, 'save_role_assignments_new_user']);
    }

    /**
     * Enqueue Select2 and custom scripts on user edit screens.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public static function enqueue_assets(string $hook): void {
        // Only load on user edit screens
        if (!in_array($hook, ['user-edit.php', 'profile.php', 'user-new.php'], true)) {
            return;
        }

        // Enqueue Select2 locally
        wp_enqueue_style(
            'wpe-rm-select2',
            WPE_RM_PLUGIN_URL . 'assets/libs/select2/select2.min.css',
            [],
            '4.1.0'
        );

        wp_enqueue_script(
            'wpe-rm-select2',
            WPE_RM_PLUGIN_URL . 'assets/libs/select2/select2.min.js',
            ['jquery'],
            '4.1.0',
            true
        );

        // Custom styles for Select2 integration
        wp_add_inline_style('wpe-rm-select2', '
            .wpe-rm-role-selector {
                margin-top: 20px;
            }
            .wpe-rm-role-selector .select2-container {
                max-width: 25em;
            }
            .wpe-rm-role-selector .select2-container--default .select2-selection--multiple {
                border: 1px solid #8c8f94;
                border-radius: 4px;
                min-height: 30px;
                padding: 0 8px;
                background-color: #fff;
            }
            .wpe-rm-role-selector .select2-container--default.select2-container--focus .select2-selection--multiple {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .wpe-rm-role-selector .select2-container--default .select2-selection--multiple .select2-selection__rendered {
                padding: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                align-items: center;
            }
            .wpe-rm-role-selector .select2-container--default .select2-selection--multiple .select2-selection__choice {
                background-color: #2271b1;
                border: 1px solid #2271b1;
                border-radius: 3px;
                color: #fff;
                padding: 3px 8px;
                margin: 4px 0;
                display: inline-flex;
                align-items: center;
                line-height: 1.4;
                font-size: 13px;
            }
            .wpe-rm-role-selector .select2-container--default .select2-selection--multiple .select2-selection__choice.role-disabled {
                background-color: #d63638;
                border-color: #d63638;
            }
            .wpe-rm-role-selector .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                color: #fff;
                margin-right: 6px;
                font-weight: bold;
                cursor: pointer;
                border: none;
                background: transparent;
                font-size: 16px;
                line-height: 1;
            }
            .wpe-rm-role-selector .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
                color: #f0f0f1;
            }
            .wpe-rm-role-selector .select2-container--default .select2-search--inline {
                display: none !important;
            }
            .wpe-rm-role-selector .select2-container--default .select2-search--inline .select2-search__field {
                display: none !important;
                width: 0 !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .wpe-rm-role-selector .select2-dropdown {
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            .wpe-rm-role-selector .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background-color: #2271b1;
                color: #fff;
            }
            .wpe-rm-role-selector .select2-container--default .select2-results__option[aria-selected=true] {
                background-color: #f0f0f1;
            }
            .wpe-rm-role-selector .select2-container--default .select2-results__option.role-disabled {
                color: #d63638;
                font-style: italic;
            }
            .wpe-rm-disabled-role-notice {
                background: #fcf3cd;
                border-left: 4px solid #dba617;
                padding: 8px 12px;
                margin-top: 8px;
                display: none;
            }
            .wpe-rm-disabled-role-notice.visible {
                display: block;
            }
        ');

        // Initialize Select2
        wp_add_inline_script('wpe-rm-select2', '
            jQuery(document).ready(function($) {
                var disabledRoles = ' . json_encode(get_option('wpe_rm_disabled_roles', [])) . ';

                $("#wpe_rm_user_roles").select2({
                    placeholder: "Select roles...",
                    allowClear: false,
                    width: "100%",
                    dropdownAutoWidth: true,
                    minimumResultsForSearch: Infinity,
                    closeOnSelect: false,
                    templateResult: function(data) {
                        if (!data.id) return data.text;
                        var $result = $("<span></span>");
                        $result.text(data.text);
                        if (disabledRoles.indexOf(data.id) !== -1) {
                            $result.addClass("role-disabled");
                        }
                        return $result;
                    }
                });

                // Update badge styling and notice when selection changes
                function updateDisabledRoleIndicators() {
                    var hasDisabled = false;
                    $("#wpe_rm_user_roles").find("option:selected").each(function() {
                        if (disabledRoles.indexOf($(this).val()) !== -1) {
                            hasDisabled = true;
                        }
                    });

                    // Add red styling to disabled role badges
                    $(".select2-selection__choice").each(function() {
                        var roleSlug = $(this).attr("title");
                        if (roleSlug && disabledRoles.indexOf(roleSlug) !== -1) {
                            $(this).addClass("role-disabled");
                        }
                    });

                    // Show/hide notice
                    if (hasDisabled) {
                        $(".wpe-rm-disabled-role-notice").addClass("visible");
                    } else {
                        $(".wpe-rm-disabled-role-notice").removeClass("visible");
                    }
                }

                $("#wpe_rm_user_roles").on("change", updateDisabledRoleIndicators);
                updateDisabledRoleIndicators();

                // Prevent typing in the select2 container
                $(".wpe-rm-role-selector").on("keydown", ".select2-search__field", function(e) {
                    e.preventDefault();
                    return false;
                });
            });
        ');
    }

    /**
     * Hide the default WordPress role selector.
     *
     * @return void
     */
    public static function hide_default_role_select(): void {
        echo '<style>.user-role-wrap { display: none !important; }</style>';
    }

    /**
     * Render custom role selector with Select2.
     *
     * @param WP_User $user User object.
     * @return void
     */
    public static function render_role_selector(WP_User $user): void {
        // Check permissions
        if (!current_user_can('promote_users')) {
            return;
        }

        global $wp_roles, $wpdb;
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        // Get raw user roles directly from database (bypassing filters)
        // This ensures we can see disabled roles that are assigned
        $capabilities = get_user_meta($user->ID, $wpdb->get_blog_prefix() . 'capabilities', true);
        $user_roles = is_array($capabilities) ? array_keys($capabilities) : [];

        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        ?>

        <h2><?php esc_html_e('Role Manager', 'wp-easy-role-manager'); ?></h2>

        <table class="form-table wpe-rm-role-selector" role="presentation">
            <tr>
                <th>
                    <label for="wpe_rm_user_roles">
                        <?php esc_html_e('User Roles', 'wp-easy-role-manager'); ?>
                    </label>
                </th>
                <td>
                    <select
                        name="wpe_rm_user_roles[]"
                        id="wpe_rm_user_roles"
                        multiple="multiple"
                        style="width: 25em;"
                    >
                        <?php foreach ($wp_roles->roles as $role_slug => $role_info): ?>
                            <?php
                            $is_disabled = in_array($role_slug, $disabled_roles, true);
                            $role_name = translate_user_role($role_info['name']);

                            if ($is_disabled) {
                                $role_name .= ' (' . __('Disabled', 'wp-easy-role-manager') . ')';
                            }
                            ?>
                            <option
                                value="<?php echo esc_attr($role_slug); ?>"
                                <?php selected(in_array($role_slug, $user_roles, true)); ?>
                            >
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class="description">
                        <?php esc_html_e('Select one or more roles for this user. Users can have multiple roles and will receive the combined capabilities of all assigned roles.', 'wp-easy-role-manager'); ?>
                    </p>

                    <div class="wpe-rm-disabled-role-notice">
                        <strong><?php esc_html_e('Warning:', 'wp-easy-role-manager'); ?></strong>
                        <?php esc_html_e('One or more selected roles are disabled. Users with disabled roles will not have access to any capabilities from those roles.', 'wp-easy-role-manager'); ?>
                    </div>

                    <?php if (count($user_roles) > 1): ?>
                        <p class="description">
                            <strong><?php esc_html_e('Current roles:', 'wp-easy-role-manager'); ?></strong>
                            <?php
                            $role_names = array_map(function($role_slug) use ($wp_roles) {
                                return isset($wp_roles->roles[$role_slug])
                                    ? translate_user_role($wp_roles->roles[$role_slug]['name'])
                                    : $role_slug;
                            }, $user_roles);
                            echo esc_html(implode(', ', $role_names));
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php wp_nonce_field('wpe_rm_update_user_roles', 'wpe_rm_user_roles_nonce'); ?>
                </td>
            </tr>
        </table>

        <?php
    }

    /**
     * Save custom role assignments.
     *
     * @param int $user_id User ID being updated.
     * @return void
     */
    public static function save_role_assignments(int $user_id): void {
        $current_user_id = get_current_user_id();

        // Prevent self-modification
        if ($user_id === $current_user_id) {
            add_settings_error(
                'wpe_rm_user_roles',
                'cannot_modify_self',
                __('You cannot modify your own roles.', 'wp-easy-role-manager'),
                'error'
            );
            return;
        }

        // Use WordPress's built-in capability check
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Require promote_users capability
        if (!current_user_can('promote_users')) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['wpe_rm_user_roles_nonce']) ||
            !wp_verify_nonce($_POST['wpe_rm_user_roles_nonce'], 'wpe_rm_update_user_roles')) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // Prevent modification of administrators by non-super-admins
        if (user_can($user, 'manage_options') && !current_user_can('manage_network')) {
            add_settings_error(
                'wpe_rm_user_roles',
                'cannot_modify_admin',
                __('You cannot modify administrator accounts.', 'wp-easy-role-manager'),
                'error'
            );
            return;
        }

        // Get selected roles
        $new_roles = isset($_POST['wpe_rm_user_roles']) && is_array($_POST['wpe_rm_user_roles'])
            ? array_map('sanitize_key', $_POST['wpe_rm_user_roles'])
            : [];

        // Remove empty values
        $new_roles = array_filter($new_roles);

        // Ensure at least one role
        if (empty($new_roles)) {
            add_settings_error(
                'wpe_rm_user_roles',
                'no_roles',
                __('User must have at least one role.', 'wp-easy-role-manager'),
                'error'
            );
            return;
        }

        // Get current roles
        $current_roles = $user->roles;

        // Remove all current roles
        foreach ($current_roles as $role) {
            $user->remove_role($role);
        }

        // Add new roles
        foreach ($new_roles as $role) {
            $user->add_role($role);
        }

        // Log the action
        if (class_exists('WP_Easy\RoleManager\Helpers\Logger')) {
            $role_list = implode(', ', $new_roles);
            \WP_Easy\RoleManager\Helpers\Logger::log(
                'User Roles Updated',
                sprintf('Updated roles for user "%s" (ID: %d): %s', $user->user_login, $user_id, $role_list)
            );
        }
    }

    /**
     * Render role selector for new user screen.
     *
     * @return void
     */
    public static function render_role_selector_new_user(): void {
        if (!current_user_can('promote_users')) {
            return;
        }

        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $disabled_roles = get_option('wpe_rm_disabled_roles', []);
        $default_role = get_option('default_role', 'subscriber');
        ?>

        <h2><?php esc_html_e('Role Manager', 'wp-easy-role-manager'); ?></h2>

        <table class="form-table wpe-rm-role-selector" role="presentation">
            <tr>
                <th>
                    <label for="wpe_rm_user_roles">
                        <?php esc_html_e('User Roles', 'wp-easy-role-manager'); ?>
                    </label>
                </th>
                <td>
                    <select name="wpe_rm_user_roles[]" id="wpe_rm_user_roles" multiple="multiple" style="width: 25em;">
                        <?php foreach ($wp_roles->roles as $role_slug => $role_info): ?>
                            <?php
                            $is_disabled = in_array($role_slug, $disabled_roles, true);
                            $role_name = translate_user_role($role_info['name']);
                            if ($is_disabled) {
                                $role_name .= ' (' . __('Disabled', 'wp-easy-role-manager') . ')';
                            }
                            ?>
                            <option value="<?php echo esc_attr($role_slug); ?>" <?php selected($role_slug, $default_role); ?>>
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Select one or more roles for this user. Users can have multiple roles and will receive the combined capabilities of all assigned roles.', 'wp-easy-role-manager'); ?>
                    </p>

                    <div class="wpe-rm-disabled-role-notice">
                        <strong><?php esc_html_e('Warning:', 'wp-easy-role-manager'); ?></strong>
                        <?php esc_html_e('One or more selected roles are disabled. Users with disabled roles will not have access to any capabilities from those roles.', 'wp-easy-role-manager'); ?>
                    </div>

                    <?php wp_nonce_field('wpe_rm_update_user_roles', 'wpe_rm_user_roles_nonce'); ?>
                </td>
            </tr>
        </table>

        <?php
    }

    /**
     * Save role assignments for new user.
     *
     * @param int $user_id User ID.
     * @return void
     */
    public static function save_role_assignments_new_user(int $user_id): void {
        if (!current_user_can('promote_users')) {
            return;
        }

        if (!isset($_POST['wpe_rm_user_roles_nonce']) || !wp_verify_nonce($_POST['wpe_rm_user_roles_nonce'], 'wpe_rm_update_user_roles')) {
            return;
        }

        $new_roles = isset($_POST['wpe_rm_user_roles']) && is_array($_POST['wpe_rm_user_roles'])
            ? array_map('sanitize_key', $_POST['wpe_rm_user_roles'])
            : [];

        $new_roles = array_filter($new_roles);

        if (empty($new_roles)) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }

        foreach ($new_roles as $role) {
            $user->add_role($role);
        }

        if (class_exists('WP_Easy\RoleManager\Helpers\Logger')) {
            $role_list = implode(', ', $new_roles);
            \WP_Easy\RoleManager\Helpers\Logger::log(
                'User Created',
                sprintf('Created user "%s" (ID: %d) with roles: %s', $user->user_login, $user_id, $role_list)
            );
        }
    }
}
