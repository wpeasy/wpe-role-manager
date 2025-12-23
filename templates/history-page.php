<?php
/**
 * History Page Template
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;

// Get settings for compact mode
$settings = get_option('wpe_rm_settings', []);
$compact_mode = !empty($settings['compact_mode']);
$wrapper_classes = 'wrap wpe-rm-admin wpea';
if ($compact_mode) {
    $wrapper_classes .= ' wpea-compact';
}
?>

<div class="<?php echo esc_attr($wrapper_classes); ?>">
    <div class="wpe-rm-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <img
            src="<?php echo esc_url(WPE_RM_PLUGIN_URL . 'assets/images/logo-light-mode.svg'); ?>"
            alt="<?php esc_attr_e('WP Easy Logo', WPE_RM_TEXTDOMAIN); ?>"
            class="wpe-rm-logo wpe-rm-logo-light"
        />
        <img
            src="<?php echo esc_url(WPE_RM_PLUGIN_URL . 'assets/images/logo-dark-mode.svg'); ?>"
            alt="<?php esc_attr_e('WP Easy Logo', WPE_RM_TEXTDOMAIN); ?>"
            class="wpe-rm-logo wpe-rm-logo-dark"
        />
    </div>

    <!-- Svelte App Mount Point -->
    <div id="wpe-rm-history-app">
        <!-- Loading indicator while Svelte app initializes -->
        <div class="wpe-rm-loading">
            <p><?php esc_html_e('Loading...', WPE_RM_TEXTDOMAIN); ?></p>
        </div>
    </div>
</div>
