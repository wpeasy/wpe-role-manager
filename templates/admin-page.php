<?php
/**
 * Admin Page Template
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wpe-rm-admin wpea">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Svelte App Mount Point -->
    <div id="wpe-rm-app">
        <!-- Loading indicator while Svelte app initializes -->
        <div class="wpe-rm-loading">
            <p><?php esc_html_e('Loading...', WPE_RM_TEXTDOMAIN); ?></p>
        </div>
    </div>
</div>
