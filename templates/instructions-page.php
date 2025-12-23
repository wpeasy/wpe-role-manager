<?php
/**
 * Instructions Page Template
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;

// Get settings for compact mode
$settings = get_option('wpe_rm_settings', []);
$compact_mode = !empty($settings['compact_mode']);
$wrapper_classes = 'wrap wpe-rm-admin wpe-rm-instructions wpea';
if ($compact_mode) {
    $wrapper_classes .= ' wpea-compact';
}
?>

<div class="<?php echo esc_attr($wrapper_classes); ?>">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Svelte App Mount Point for Instructions -->
    <div id="wpe-rm-instructions-app">
        <div class="wpe-rm-loading">
            <p><?php esc_html_e('Loading...', WPE_RM_TEXTDOMAIN); ?></p>
        </div>
    </div>
</div>
