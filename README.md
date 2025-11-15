# WP Easy Role Manager

**Version:** 0.0.1-alpha
**Requires:** WordPress 6.4+, PHP 8.1+
**License:** GPL v2 or later

## Description

Easy UI to add, remove, enable, disable WordPress roles. Visualise and assign multiple roles to users. Visualise, add, and remove capabilities on roles. Also visualise the effective capabilities a user has based on their roles.

## Features

### Role Management
- Create new custom roles (from scratch or based on existing roles)
- Disable custom roles completely:
  - Users retain role assignment in admin for visibility
  - Role becomes invisible to WordPress core, plugins, and themes
  - No capabilities granted from disabled roles
  - Role membership checks (e.g., Bricks Builder conditions) return false
  - Visual indicators (red badges) when assigning disabled roles
- Delete custom roles (with user migration wizard)
- Core roles are read-only and protected

### Capability Management
- Add/remove capabilities from roles
- Disable capabilities per-role without deleting
- Visual matrix of Roles × Capabilities
- Filter and search capabilities
- Core capabilities are read-only

### User Management
- Assign multiple roles to users via Select2 interface on user edit screens
- Default WordPress role selector hidden on user-new.php to avoid confusion
- Visual warnings when assigning disabled roles (red badges)
- View effective capabilities (union across all user roles, excluding disabled roles)
- Test capability feature:
  - Check if a user has a specific capability
  - Shows "Role Disabled" status for capabilities from disabled roles
  - Generate code snippets (Shortcode, PHP, Fetch, REST URL)
- Bulk role assignment operations
- User migration wizard when deleting roles

### Activity Logging
- Comprehensive logging of all role/capability/user changes
- Stores up to 500 log entries
- Filter logs by Action (dropdown) and Details (search)
- Tracks who changed what and when
- Timestamps in both local and GMT

### Revision History
- **Complete state snapshots** for all role and capability changes
- Automatic revision tracking for all operations:
  - Role creation, modification, deletion
  - Capability addition, removal, toggle (grant/deny/unset)
  - Role enable/disable operations
- **Full plugin metadata preservation** in snapshots:
  - Tracks which roles and capabilities were created by the plugin
  - Ensures proper classification (Core, External, Custom) after restoration
  - Preserves disabled states and managed capability assignments
- One-click restoration to any previous state
- Complete audit trail of configuration changes

### Safety Features
- Core roles and capabilities are protected (read-only)
- Typed confirmation required for role deletion
- Migration wizard when deleting roles assigned to users
- Activity logging for audit trail
- Rate limiting on bulk operations

### UX Features
- Single-page admin UI with tabbed navigation (Roles, Capabilities, Users, Import/Export, Settings, Tools, Revisions, Logs)
- Autosave on change (no "Save" button needed)
- Status indicator (Saving… / Saved / Error)
- Filter/search by role, capability, user, and prefix
- **Sortable table columns:** Role Name and Capability columns with toggle sort direction (↑/↓) and alphabetical default sort
- Import/Export custom roles as JSON

### Third-Party Integrations
- **Bricks Builder Integration**:
  - Custom element conditions: "User Has Capability", "Specific User Has Capability"
  - Dynamic data tags: `{wpe_rm_capability_status:cap_name}` and `{wpe_rm_capability_status:cap_name:user_id}`
  - Tag returns: "granted", "not-granted", or "denied"
  - Respects disabled roles (conditions return false for disabled role membership)
- **WordPress User Edit Screens**:
  - Enhanced Select2 multi-role assignment
  - Replaces default WordPress single-role dropdown
  - Shows disabled roles with visual warnings

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
- `POST /roles` - Create new role (saves revision)
- `PATCH /roles/{role}` - Update role (saves revision)
- `DELETE /roles/{role}` - Delete role (saves revision)
- `POST /roles/{role}/caps` - Add capability to role (saves revision)
- `PATCH /roles/{role}/caps/{cap}` - Toggle capability state (grant/deny/unset) (saves revision)
- `DELETE /roles/{role}/caps/{cap}` - Remove capability from role (saves revision)
- `GET /users` - Get users with roles
- `PATCH /users/{id}/roles` - Update user roles
- `GET /users/{id}/effective-caps` - Get user's effective capabilities
- `GET /users/{id}/can/{capability}` - Test if user has a capability
- `GET /export` - Export custom roles to JSON
- `POST /import` - Import roles from JSON
- `GET /revisions` - Get all revision snapshots
- `POST /revisions/{id}/restore` - Restore to a specific revision
- `DELETE /revisions/{id}` - Delete a specific revision
- `GET /logs` - Get activity logs (with optional action/details filters)
- `DELETE /logs` - Clear all logs
- `GET /logs/actions` - Get unique action types for filtering

## Changelog

### 0.0.3-alpha
- Added custom scrollbar styling with light/dark mode support
- Added double scrollbar (top/bottom) for wide tables
- Added Bricks Builder integration (conditions and dynamic tags)
- Added Light/Dark/Auto theme switcher with OS preference support
- Added compact mode setting for denser UI
- Added custom menu icon replacing default dashicons
- Added log retention setting (100-10000 entries)
- Added Appearance settings section
- Fixed all dark mode badge issues across all tabs
- Fixed Bricks Builder integration timing and type issues
- Comprehensive disabled role enforcement:
  - Disabled roles now filter from `$user->roles` on frontend
  - Blocks capability grants from disabled roles
  - Blocks role membership checks in third-party plugins
  - Visual indicators (red badges) in Select2 on user edit screens
- Enhanced Select2 role assignment on WordPress user edit/new pages
- Improved card background contrast for better visual hierarchy

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
