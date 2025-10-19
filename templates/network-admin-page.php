<?php
/**
 * Network Admin Page Template (Multisite)
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wpe-rm-network-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wpe-rm-network-container">
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Network Admin: Use this page to apply role changes across multiple sites in your network.', WPE_RM_TEXTDOMAIN); ?>
            </p>
        </div>

        <h2><?php esc_html_e('Network Tools', WPE_RM_TEXTDOMAIN); ?></h2>
        <p><?php esc_html_e('Select sites to apply role management operations.', WPE_RM_TEXTDOMAIN); ?></p>

        <!-- TODO: Implement network admin UI -->
        <p>
            <em><?php esc_html_e('Network admin functionality coming soon.', WPE_RM_TEXTDOMAIN); ?></em>
        </p>
    </div>
</div>
