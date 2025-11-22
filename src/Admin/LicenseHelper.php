<?php
/**
 * License Helper Functions
 *
 * @package WP_Easy\RoleManager
 */

namespace WP_Easy\RoleManager\Admin;

defined('ABSPATH') || exit;

/**
 * Helper functions for licensing and development environment detection.
 *
 * @since 0.1.6-beta
 */
final class LicenseHelper {
    /**
     * Check if the current site is a local/dev environment.
     *
     * @return bool True if local/dev site, false otherwise.
     */
    public static function is_local_dev_site(): bool {
        $host = wp_parse_url(get_site_url(), PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        // Check TLDs
        $local_tlds = ['.local', '.test', '.dev', '.invalid', '.localhost'];
        foreach ($local_tlds as $tld) {
            if (str_ends_with($host, $tld)) {
                return true;
            }
        }

        // Check if it's an IP address (local ranges)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // Check if it's a private or reserved IP
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            }
        }

        // Check keywords
        $dev_keywords = ['localhost', 'staging', 'dev', 'development'];
        foreach ($dev_keywords as $keyword) {
            if (stripos($host, $keyword) !== false) {
                return true;
            }
        }

        // Check WP constant
        if (defined('WP_LOCAL_DEV') && WP_LOCAL_DEV) {
            return true;
        }

        return false;
    }

    /**
     * Check if development override is active.
     *
     * @return bool True if override is active, false otherwise.
     */
    public static function is_dev_override(): bool {
        return get_option('wpe_rm_dev_override', false) === true;
    }

    /**
     * Check and activate license key, handling dev override.
     *
     * @param string $key License key to check.
     * @return array|WP_Error License data on success, WP_Error on failure.
     */
    public static function check_license_key(string $key) {
        // Check for development override key (using constant)
        if ($key === WPE_RM_LICENSE_DEV_OVERRIDE_KEY) {
            update_option('wpe_rm_dev_override', true);
            update_option('wpe_rm_license_key', $key);

            return [
                'status' => 'valid',
                'expires' => 'never',
                'override' => true,
                'message' => 'Development override activated. Automatic updates are disabled.',
            ];
        }

        // Check if updater is available
        if (!class_exists('WP_Easy\\RoleManager\\Licensing\\FluentLicensing')) {
            return new \WP_Error(
                'licensing_unavailable',
                __('Licensing system is not available. Please ensure the updater files are installed.', WPE_RM_TEXTDOMAIN)
            );
        }

        // Normal FluentCart activation
        $licensing = \WP_Easy\RoleManager\Licensing\FluentLicensing::getInstance();
        return $licensing->activate($key);
    }

    /**
     * Get current license status.
     *
     * @param bool $remote Whether to check remote server (default: false for local check).
     * @return object|null License status object or null if not available.
     */
    public static function get_license_status(bool $remote = false): ?object {
        // Check dev override first
        if (self::is_dev_override()) {
            return (object) [
                'status' => 'valid',
                'expires' => 'never',
                'override' => true,
            ];
        }

        // Check if on local/dev site
        if (self::is_local_dev_site()) {
            return (object) [
                'status' => 'valid',
                'local_dev' => true,
                'expires' => 'never',
            ];
        }

        // Check if updater is available
        if (!class_exists('WP_Easy\\RoleManager\\Licensing\\FluentLicensing')) {
            return (object) [
                'status' => 'unregistered',
                'error' => 'Licensing system not available',
            ];
        }

        // Get status from licensing system
        $licensing = \WP_Easy\RoleManager\Licensing\FluentLicensing::getInstance();
        return $licensing->getStatus($remote);
    }

    /**
     * Check if user has valid license to access plugin.
     *
     * @return bool True if licensed, false otherwise.
     */
    public static function has_valid_license(): bool {
        $status = self::get_license_status();

        if (!$status) {
            return false;
        }

        return $status->status === 'valid';
    }

    /**
     * Deactivate the current license.
     *
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function deactivate_license() {
        // Clear dev override if set
        if (self::is_dev_override()) {
            delete_option('wpe_rm_dev_override');
            delete_option('wpe_rm_license_key');
            return true;
        }

        // Check if updater is available
        if (!class_exists('WP_Easy\\RoleManager\\Licensing\\FluentLicensing')) {
            return new \WP_Error(
                'licensing_unavailable',
                __('Licensing system is not available.', WPE_RM_TEXTDOMAIN)
            );
        }

        // Deactivate via licensing system
        $licensing = \WP_Easy\RoleManager\Licensing\FluentLicensing::getInstance();
        return $licensing->deactivate();
    }

    /**
     * Get license info for display.
     *
     * @return array License information array.
     */
    public static function get_license_info(): array {
        $status = self::get_license_status();

        if (!$status) {
            return [
                'status' => 'unregistered',
                'message' => __('No license activated', WPE_RM_TEXTDOMAIN),
                'class' => 'error',
            ];
        }

        $info = [
            'status' => $status->status,
        ];

        switch ($status->status) {
            case 'valid':
                if (isset($status->override) && $status->override) {
                    $info['message'] = __('Development Override Active', WPE_RM_TEXTDOMAIN);
                    $info['class'] = 'warning';
                    $info['note'] = __('Automatic updates are disabled', WPE_RM_TEXTDOMAIN);
                } elseif (isset($status->local_dev) && $status->local_dev) {
                    $info['message'] = __('Local/Development Site', WPE_RM_TEXTDOMAIN);
                    $info['class'] = 'info';
                    $info['note'] = __('No license required for local development', WPE_RM_TEXTDOMAIN);
                } else {
                    $info['message'] = __('License Active', WPE_RM_TEXTDOMAIN);
                    $info['class'] = 'success';
                    if (isset($status->expires)) {
                        $info['expires'] = $status->expires;
                    }
                }
                break;

            case 'invalid':
                $info['message'] = __('License Expired', WPE_RM_TEXTDOMAIN);
                $info['class'] = 'error';
                if (isset($status->renewal_url)) {
                    $info['renewal_url'] = $status->renewal_url;
                }
                break;

            case 'disabled':
                $info['message'] = __('License Disabled', WPE_RM_TEXTDOMAIN);
                $info['class'] = 'error';
                $info['note'] = __('This license has been disabled or refunded', WPE_RM_TEXTDOMAIN);
                break;

            case 'unregistered':
                $info['message'] = __('No License Activated', WPE_RM_TEXTDOMAIN);
                $info['class'] = 'info';
                $info['purchase_url'] = WPE_RM_LICENSE_PURCHASE_URL;
                break;

            default:
                $info['message'] = __('License Status Unknown', WPE_RM_TEXTDOMAIN);
                $info['class'] = 'warning';
        }

        return $info;
    }
}
