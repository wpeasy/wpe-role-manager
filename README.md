# WP Easy Role Manager

**Version:** 0.0.1-alpha
**Requires:** WordPress 6.4+, PHP 8.1+
**License:** GPL v2 or later

## Description

Easy UI to add, remove, enable, disable WordPress roles. Visualise and assign multiple roles to users. Visualise, add, and remove capabilities on roles. Also visualise the effective capabilities a user has based on their roles.

## Features

### Role Management
- Create new custom roles (from scratch or based on existing roles)
- Disable custom roles (soft-disable - users keep role mapping but role grants no capabilities)
- Delete custom roles (with user migration wizard)
- Core roles are read-only and protected

### Capability Management
- Add/remove capabilities from roles
- Disable capabilities per-role without deleting
- Visual matrix of Roles × Capabilities
- Filter and search capabilities
- Core capabilities are read-only

### User Management
- Assign multiple roles to users
- View effective capabilities (union across all user roles)
- Test capability feature: check if a user has a specific capability
- Generate code snippets for capability checks (Shortcode, PHP, Fetch, REST URL)
- Bulk role assignment operations
- User migration wizard when deleting roles

### Activity Logging
- Comprehensive logging of all role/capability/user changes
- Stores up to 500 log entries
- Filter logs by Action (dropdown) and Details (search)
- Tracks who changed what and when
- Timestamps in both local and GMT

### Safety Features
- Core roles and capabilities are protected (read-only)
- Typed confirmation required for role deletion
- Migration wizard when deleting roles assigned to users
- Activity logging for audit trail
- Rate limiting on bulk operations

### UX Features
- Single-page admin UI with tabbed navigation
- Autosave on change (no "Save" button needed)
- Status indicator (Saving… / Saved / Error)
- Filter/search by role, capability, user, and prefix
- Import/Export custom roles as JSON

### Multisite Compatible
- Fully compatible with WordPress multisite
- Network admin tools for bulk operations across sites
- Safeguards against accidental network-wide changes

## Installation

1. Upload the plugin files to `/wp-content/plugins/wpe-role-manager/`
2. Navigate to the plugin directory: `cd wp-content/plugins/wpe-role-manager/`
3. Install dependencies: `composer install`
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Access via **Role Manager** in the admin menu

## Development

### Requirements
- PHP 8.1 or higher
- WordPress 6.4 or higher
- Composer

### Setup
```bash
# Install dependencies
composer install

# Directory structure
wpe-role-manager/
├── wpe-role-manager.php    # Main plugin file
├── composer.json           # Composer configuration
├── uninstall.php          # Uninstall cleanup
├── src/                   # PHP classes (PSR-4)
│   ├── Plugin.php
│   ├── Admin/
│   └── REST/
├── assets/                # Frontend assets
│   ├── wpea/             # WPEA Framework
│   ├── css/
│   ├── js/
│   └── images/
├── languages/            # Translation files
└── templates/            # PHP templates
```

### Constants
- `WPE_RM_VERSION` - Plugin version
- `WPE_RM_PLUGIN_PATH` - Absolute path to plugin directory
- `WPE_RM_PLUGIN_URL` - Plugin URL
- `WPE_RM_TEXTDOMAIN` - Translation text domain
- `WPE_RM_MIN_WP` - Minimum WordPress version
- `WPE_RM_MIN_PHP` - Minimum PHP version

### Coding Standards
- Namespace: `WP_Easy\RoleManager`
- PSR-4 autoloading via Composer
- WordPress coding standards
- Security: All files use `defined('ABSPATH') || exit;`
- All user inputs sanitized and validated
- Nonce verification on all state-changing operations

## REST API

Namespace: `wpe-rm/v1`

All endpoints require:
- Authentication (WordPress nonce)
- `manage_options` capability (or custom capability from settings)
- Same-origin request

### Endpoints
- `GET /roles` - Get all roles
- `POST /roles` - Create new role
- `PATCH /roles/{role}` - Update role
- `DELETE /roles/{role}` - Delete role
- `POST /roles/{role}/caps` - Add capability to role
- `PATCH /roles/{role}/caps/{cap}` - Toggle capability state (grant/deny/unset)
- `DELETE /roles/{role}/caps/{cap}` - Remove capability from role
- `GET /users` - Get users with roles
- `PATCH /users/{id}/roles` - Update user roles
- `GET /users/{id}/effective-caps` - Get user's effective capabilities
- `GET /users/{id}/can/{capability}` - Test if user has a capability
- `GET /export` - Export custom roles to JSON
- `POST /import` - Import roles from JSON
- `GET /logs` - Get activity logs (with optional action/details filters)
- `DELETE /logs` - Clear all logs
- `GET /logs/actions` - Get unique action types for filtering

## Changelog

### 0.0.1-alpha
- Initial development release
- Basic plugin skeleton
- REST API routes structure
- Admin menu registration
- WPEA Framework integration

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please visit [https://wpeasy.au](https://wpeasy.au)
