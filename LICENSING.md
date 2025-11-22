# Licensing Implementation - FluentCart Integration

**Reference:** https://github.com/WPManageNinja/fluent-plugin-updater-example

Implement licensing on this plugin using the FluentCart License activation and automatic updater system.

---

## Configuration Parameters

**IMPORTANT:** All licensing/updater settings must be defined as global constants in the main plugin file (`wpe-role-manager.php`). This centralizes configuration and makes it easy to update values in one location.

### Required Constants (Define in wpe-role-manager.php)
```php
// Licensing Configuration Constants
define('WPE_RM_LICENSE_ITEM_ID', '[PENDING]'); // Get from FluentCart product page
define('WPE_RM_LICENSE_API_URL', 'https://alanblair.co/');
define('WPE_RM_LICENSE_SLUG', 'wpe-role-manager');
define('WPE_RM_LICENSE_SETTINGS_KEY', 'wpe_rm_license_settings');
```

### Optional Constants (Define in wpe-role-manager.php)
```php
// Optional Licensing Settings
define('WPE_RM_LICENSE_MENU_TYPE', 'submenu'); // submenu, options, or menu
define('WPE_RM_LICENSE_MENU_TITLE', 'License');
define('WPE_RM_LICENSE_PAGE_TITLE', 'WP Easy Role Manager - License');
define('WPE_RM_LICENSE_PURCHASE_URL', 'https://wpeasy.au/role-manager/');
define('WPE_RM_LICENSE_ACCOUNT_URL', 'https://alanblair.co/my-account/');

// Development Override (do not document publicly)
define('WPE_RM_LICENSE_DEV_OVERRIDE_KEY', 'wpe-activate-for-dev-20112026');
```

### Constants Reference
- **WPE_RM_LICENSE_ITEM_ID**: Product ID from FluentCart (required)
- **WPE_RM_LICENSE_API_URL**: WordPress site URL hosting FluentCart (required)
- **WPE_RM_LICENSE_SLUG**: Plugin slug for identification (required)
- **WPE_RM_LICENSE_SETTINGS_KEY**: WordPress option key for storing license data (required)
- **WPE_RM_LICENSE_MENU_TYPE**: Where to add license page (`submenu`, `options`, `menu`)
- **WPE_RM_LICENSE_MENU_TITLE**: Label for license menu item
- **WPE_RM_LICENSE_PAGE_TITLE**: Browser title for license page
- **WPE_RM_LICENSE_PURCHASE_URL**: Link to purchase license
- **WPE_RM_LICENSE_ACCOUNT_URL**: Link to manage existing licenses
- **WPE_RM_LICENSE_DEV_OVERRIDE_KEY**: Development override license key (do not document publicly)

---

## Implementation Checklist

### 1. File Structure (PSR-4 Compliant)
```
/src/Licensing/
  - FluentLicensing.php
  - LicenseSettings.php
  - PluginUpdater.php
```

**Namespace:** `WP_Easy\RoleManager\Licensing`
**Autoloading:** PSR-4 via composer.json (`"WP_Easy\\RoleManager\\": "src/"`)
**No manual requires needed** - classes are autoloaded by composer

### 2. Define Constants (wpe-role-manager.php)
Add licensing constants after existing plugin constants:

```php
// Define plugin constants
define('WPE_RM_VERSION', '0.1.5-beta');
define('WPE_RM_PLUGIN_FILE', __FILE__);
define('WPE_RM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPE_RM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPE_RM_TEXTDOMAIN', 'wp-easy-role-manager');
define('WPE_RM_MIN_WP', '6.4');
define('WPE_RM_MIN_PHP', '8.1');

// Licensing Configuration Constants
define('WPE_RM_LICENSE_ITEM_ID', '[PENDING]'); // TODO: Get from FluentCart
define('WPE_RM_LICENSE_API_URL', 'https://alanblair.co/');
define('WPE_RM_LICENSE_SLUG', 'wpe-role-manager');
define('WPE_RM_LICENSE_SETTINGS_KEY', 'wpe_rm_license_settings');
define('WPE_RM_LICENSE_MENU_TYPE', 'submenu');
define('WPE_RM_LICENSE_MENU_TITLE', 'License');
define('WPE_RM_LICENSE_PAGE_TITLE', 'WP Easy Role Manager - License');
define('WPE_RM_LICENSE_PURCHASE_URL', 'https://wpeasy.au/role-manager/');
define('WPE_RM_LICENSE_ACCOUNT_URL', 'https://alanblair.co/my-account/');
define('WPE_RM_LICENSE_DEV_OVERRIDE_KEY', 'wpe-activate-for-dev-20112026');
```

### 3. Initialize Licensing (wpe-role-manager.php)
Add licensing initialization using the defined constants (PSR-4 autoloads the classes):

```php
// Initialize licensing on 'init' hook
add_action('init', function() {
    // Check if licensing class exists (PSR-4 autoloaded from src/Licensing/)
    if (!class_exists('WP_Easy\\RoleManager\\Licensing\\FluentLicensing')) {
        return; // Licensing files not installed
    }

    $licensing = WP_Easy\RoleManager\Licensing\FluentLicensing::getInstance();
    $licensing->register([
        'version' => WPE_RM_VERSION,
        'item_id' => WPE_RM_LICENSE_ITEM_ID,
        'basename' => plugin_basename(__FILE__),
        'api_url' => WPE_RM_LICENSE_API_URL,
        'slug' => WPE_RM_LICENSE_SLUG,
        'settings_key' => WPE_RM_LICENSE_SETTINGS_KEY,
    ]);

    // Initialize settings page if LicenseSettings class exists
    if (class_exists('WP_Easy\\RoleManager\\Licensing\\LicenseSettings')) {
        $licenseSettings = new WP_Easy\RoleManager\Licensing\LicenseSettings();
        $licenseSettings->register($licensing, [
            'menu_title' => WPE_RM_LICENSE_MENU_TITLE,
            'page_title' => WPE_RM_LICENSE_PAGE_TITLE,
            'title' => WPE_RM_LICENSE_PAGE_TITLE,
            'purchase_url' => WPE_RM_LICENSE_PURCHASE_URL,
            'account_url' => WPE_RM_LICENSE_ACCOUNT_URL,
            'plugin_name' => 'WP Easy Role Manager',
        ]);

        // Add the license page as submenu
        $licenseSettings->addPage([
            'type' => WPE_RM_LICENSE_MENU_TYPE,
            'page_title' => WPE_RM_LICENSE_PAGE_TITLE,
            'menu_title' => WPE_RM_LICENSE_MENU_TITLE,
            'parent_slug' => 'wpe-role-manager',
            'capability' => 'manage_options',
        ]);
    }
});
```

### 4. Access Control Logic
```php
// Check license status before displaying main plugin
$licensing = WP_Easy\RoleManager\Licensing\FluentLicensing::getInstance();
$status = $licensing->getStatus(); // Local check (fast)

if ($status->status !== 'valid' && !is_dev_override()) {
    // Show license activation page only
    display_license_required_notice();
    return;
}

// Show full plugin interface
```

### 5. Status Checking Strategy
- **On Admin Load**: Use `getStatus()` for fast local verification
- **Daily Cron**: Use `getStatus(true)` for remote server validation
- **Handle Status Values**:
  - `valid` → Full plugin access
  - `invalid` → Show expired notice with renewal link
  - `disabled` → Show refund/disabled message
  - `unregistered` → Show activation form
  - `error` → Show error + allow retry

### 6. Error Handling Pattern
```php
$result = $licensing->activate($license_key);

if (is_wp_error($result)) {
    // Handle error
    $error_message = $result->get_error_message();
    display_error_notice($error_message);
} else {
    // Success
    $license_data = $result;
    display_success_notice();
}
```

---

## Local/Dev Site Exclusions

### Detection Methods
Check for common local/dev indicators:
- Domain: `localhost`, `.local`, `.test`, `.dev`, `.invalid`
- IP addresses: `127.0.0.1`, `::1`, `10.*.*.*`, `192.168.*.*`, `172.16-31.*.*`
- URLs containing: `staging`, `dev`, `development`
- WordPress constants: `WP_LOCAL_DEV === true`

### Implementation
```php
function is_local_dev_site() {
    $host = parse_url(get_site_url(), PHP_HOST);

    // Check TLDs
    $local_tlds = ['.local', '.test', '.dev', '.invalid', '.localhost'];
    foreach ($local_tlds as $tld) {
        if (str_ends_with($host, $tld)) return true;
    }

    // Check IP ranges
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
    }

    // Check keywords
    $dev_keywords = ['localhost', 'staging', 'dev', 'development'];
    foreach ($dev_keywords as $keyword) {
        if (stripos($host, $keyword) !== false) return true;
    }

    // Check WP constant
    if (defined('WP_LOCAL_DEV') && WP_LOCAL_DEV) return true;

    return false;
}
```

---

## Development Override License

### Override Key
Use the constant: `WPE_RM_LICENSE_DEV_OVERRIDE_KEY`
Default value: `wpe-activate-for-dev-20112026`

### Implementation Requirements
When this license is entered:
1. **Do NOT** call FluentCart API (skip all remote calls)
2. Store override flag: `update_option('wpe_rm_dev_override', true)`
3. Return mock "valid" status locally
4. Set license status to `valid` immediately
5. Disable all update checks from FluentCart
6. Log override activation for debugging

### Override Check Function
```php
function is_dev_override() {
    return get_option('wpe_rm_dev_override', false) === true;
}

function check_license_key($key) {
    // Check for development override key (using constant)
    if ($key === WPE_RM_LICENSE_DEV_OVERRIDE_KEY) {
        update_option('wpe_rm_dev_override', true);
        update_option('wpe_rm_license_key', $key);
        return [
            'status' => 'valid',
            'expires' => 'never',
            'override' => true
        ];
    }

    // Normal FluentCart activation
    $licensing = WP_Easy\RoleManager\Licensing\FluentLicensing::getInstance();
    return $licensing->activate($key);
}
```

### Security Note
This override should:
- Be clearly marked as "Development Override" in UI
- Show warning that updates are disabled
- Not be documented in public-facing documentation
- Optionally expire after specific date (2026-11-20)

---

## UI/UX Requirements

### License Not Active State
- **Hide:** All main plugin tabs and functionality
- **Show:**
  - Clean license activation page
  - Benefits of activation
  - Purchase link
  - "Already have a license?" activation form
  - Support link

### License Active State
- **Show:** Small "Licensed to: [email/name]" indicator in admin footer or settings
- **Show:** "Manage License" link in plugin settings
- **Allow:** Full plugin functionality

### License Expired/Disabled State
- **Show:** Prominent renewal notice
- **Show:** Days until features are disabled (grace period: 7 days)
- **Allow:** Read-only access to existing configurations
- **Block:** Creating new roles, capabilities, or assignments

---

## Testing Checklist

- [ ] Get item_id from FluentCart product page
- [ ] Test license activation with valid key
- [ ] Test license activation with invalid key
- [ ] Test license deactivation
- [ ] Test local/dev site bypass (all detection methods)
- [ ] Test dev override key (`wpe-activate-for-dev-20112026`)
- [ ] Test expired license scenario
- [ ] Test disabled/refunded license scenario
- [ ] Test network/API error scenarios
- [ ] Test automatic updates with active license
- [ ] Verify license status caching and refresh timing
- [ ] Test settings page UI/UX
- [ ] Verify admin access blocking when unlicensed

---

## Critical Action Items

1. **Define Constants:** Add all `WPE_RM_LICENSE_*` constants to `wpe-role-manager.php` after existing plugin constants
2. **Get Item ID:** Contact FluentCart/create product to obtain item ID, then update `WPE_RM_LICENSE_ITEM_ID` constant
3. **Update Namespace:** Change `FluentUpdater` to `WP_Easy\RoleManager\Licensing` in all updater files
4. **Configure API URL:** Verify `https://alanblair.co/` has FluentCart installed and configured
5. **Test Override:** Ensure dev override works without API calls
6. **Initialize Licensing:** Add licensing initialization code using the defined constants
7. **Add Status Indicator:** Show license status in plugin admin area
8. **Error Handling:** Implement comprehensive WP_Error handling for all licensing methods
9. **Daily Cron:** Set up `wp_schedule_event` for daily remote license validation

---

## Notes

- **Use constants everywhere:** All licensing configuration must use the `WPE_RM_LICENSE_*` constants defined in the main plugin file. Never hardcode values.
- License checks should be **fast** - use local `getStatus()` for regular checks
- Remote validation via `getStatus(true)` should run via cron, not on every page load
- Always use `is_wp_error()` before processing results from activate/deactivate/getStatus
- Store license data in WordPress options, not in class properties
- Use singleton pattern: `FluentLicensing::getInstance()` for access throughout plugin
- **Centralized configuration:** All licensing settings are in one place (`wpe-role-manager.php`), making updates and maintenance simple
