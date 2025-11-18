<?php
/**
 * Admin Page Template
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wpe-rm-admin wpea">
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
    <div id="wpe-rm-app">
        <!-- Loading indicator while Svelte app initializes -->
        <div class="wpe-rm-loading">
            <p><?php esc_html_e('Loading...', WPE_RM_TEXTDOMAIN); ?></p>
        </div>
    </div>
</div>
