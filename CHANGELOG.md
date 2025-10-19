# Changelog

All notable changes to WP Easy Role Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.2-alpha] - 2025-01-19

### Added

- **Security Documentation**
  - Comprehensive Security Features section in Instructions page
  - Documentation of all 28 dangerous capabilities that can lead to code execution or privilege escalation
  - Explanation of administrator-only access requirements
  - Security best practices and protection mechanisms

- **Dangerous Capability Override Setting**
  - New setting: "Allow assigning dangerous capabilities to roles"
  - Configurable override for security blacklist when explicitly enabled
  - Comprehensive warnings about security implications in UI
  - Affects `unfiltered_html`, `unfiltered_upload`, `edit_plugins`, `edit_themes`, and 24 other dangerous capabilities
  - Setting persists via REST API with activity logging

- **Capability Type Filtering**
  - Radio button filters in Capabilities tab: All, Core, External, Custom
  - Filter capabilities by origin/type for easier management
  - Visual filter UI integrated into matrix view

- **Full Backup & Restore**
  - Export type selection: "Roles Only" or "Full Backup"
  - Full backup exports all custom roles, capabilities, and role assignments
  - Smart import detection automatically identifies backup type
  - Confirmation dialogs for full backup restore operations
  - Backup includes metadata for proper restoration (version, timestamp, backup_type)
  - Different filenames for backup vs role-only exports

- **Settings Management**
  - REST API endpoints for settings: GET `/settings`, POST `/settings`
  - Settings persist to WordPress options: `wpe_rm_settings`
  - Autosave debounce setting (100-5000ms) in Performance section
  - Settings changes logged to activity log

### Changed

- **Settings Tab Redesign**
  - Removed rate limiting settings and UI (feature removed)
  - Added Security Settings section with dangerous capability override
  - Added Performance Settings section with autosave debounce control
  - Added Access Control information section

- **Import/Export Tab Enhancement**
  - Complete redesign with dual-mode export (roles vs full backup)
  - File upload and paste-JSON import methods
  - Download exported JSON as file with timestamped filenames
  - Smart restore with appropriate user confirmations

- **Default Activation Settings**
  - Removed rate limiting from default settings
  - Simplified to: `allow_core_cap_assignment` (false), `autosave_debounce` (500ms)

### Removed

- **Rate Limiting**
  - Removed all rate limiting functionality and UI
  - Removed settings: `rate_limit_enabled`, `rate_limit_requests`, `rate_limit_window`
  - Simplified plugin to focus on core role/capability management

### Fixed

- **Capability Type Filter Bug**
  - Fixed filter not working when selecting Core, External, or Custom
  - Changed from checking non-existent `cap.type` property to using `cap.isCore` and `cap.isExternal` booleans
  - Filter now correctly shows only capabilities matching selected type

- **Full Backup Export Error**
  - Fixed fatal error: "Call to undefined method RoleManager::get_custom_roles()"
  - Changed to use existing `RoleManager::get_all_roles()` method with filtering
  - Export now correctly includes all custom roles in backup

- **Full Backup Restore Not Applying Capabilities**
  - Fixed capabilities metadata being saved but not actually applied to roles
  - Added WordPress `$role->add_cap()` calls to apply each capability during restore
  - Restore now properly assigns all capabilities from backup to their respective roles
  - Added detailed feedback showing number of capability assignments applied

- **Dangerous Capability Setting Scope**
  - Corrected setting from "users" to "roles" (initial implementation error)
  - Setting now correctly controls role capability assignment, not user capability assignment
  - Updated all UI text and warnings to reflect correct scope

### Security

- **Enhanced Documentation**
  - All dangerous capabilities now documented in Instructions page
  - Security implications clearly explained to administrators
  - Best practices section emphasizes principle of least privilege

- **Configurable Security Override**
  - Dangerous capability protection can now be disabled when explicitly needed
  - Override setting requires intentional action with clear warnings
  - Error messages guide users to setting when blocked

## [0.0.1-alpha] - 2025-01-XX

### Added

#### Core Features
- **Role Management**
  - Create custom roles from scratch or based on existing roles
  - Enable/disable custom roles (soft-disable maintains user mapping)
  - Delete custom roles with typed confirmation ("delete")
  - Core roles (administrator, editor, author, contributor, subscriber) are protected and read-only
  - Track plugin-created roles vs external roles

- **Capability Management**
  - Three-state capability system: granted (true), denied (false), unset (null)
  - Add/remove capabilities from roles
  - Toggle capability states (grant/deny/unset)
  - Visual capability matrix showing Roles × Capabilities
  - Filter and search capabilities
  - Core capabilities are protected and read-only
  - Track managed role+capability pairs

- **User Management**
  - Assign multiple roles to users simultaneously
  - View effective capabilities (computed union across all user roles)
  - Test capability feature with code generation:
    - Check if a user has a specific capability
    - Generate Shortcode snippet with copy-to-clipboard
    - Generate PHP snippet with copy-to-clipboard
    - Generate Fetch (JavaScript) snippet with copy-to-clipboard
    - Generate REST URL for direct testing
  - Filterable capability list for testing
  - User count per role

- **Activity Logging**
  - Comprehensive logging of all role/capability/user changes
  - Stores up to 500 log entries (oldest auto-pruned)
  - Log entries include: action, details, user, user ID, timestamp (local and GMT)
  - Filter logs by Action type (dropdown select)
  - Search logs by Details, Action, or User (text input)
  - Clear all logs functionality
  - Logged actions:
    - Role Created
    - Role Enabled/Disabled
    - Role Deleted
    - Capability Added
    - Capability Toggled
    - Capability Removed
    - User Roles Updated
    - Roles Imported

- **Import/Export**
  - Export custom roles to JSON
  - Select specific roles to export or export all custom roles
  - Import roles from JSON file with validation
  - Track imported roles as plugin-created
  - Import status reporting (success count, error count, messages)

- **User Interface**
  - Single-page application built with Svelte 5 (runes mode)
  - Tabbed navigation: Roles, Capabilities, Users, Import/Export, Settings, Logs
  - WPEA Framework integration for consistent admin styling
  - Auto-save on change (no "Save" button required)
  - Status indicator showing: Saving… / Saved / Error
  - Search and filter throughout all tabs
  - Confirmation modals for destructive actions
  - Instructions submenu page with comprehensive documentation

#### Technical Implementation
- **REST API** (`wpe-rm/v1`)
  - Role endpoints: GET, POST, PATCH, DELETE `/roles`
  - Capability endpoints: POST, PATCH, DELETE `/roles/{role}/caps`
  - User endpoints: GET, PATCH `/users`, GET `/users/{id}/effective-caps`
  - Test capability endpoint: GET `/users/{id}/can/{capability}`
  - Import/Export endpoints: POST `/import`, GET `/export`
  - Logging endpoints: GET, DELETE `/logs`, GET `/logs/actions`
  - Nonce validation on all endpoints
  - `manage_options` capability requirement (configurable)
  - Same-origin enforcement

- **Security**
  - All inputs sanitized and validated
  - WordPress nonce verification
  - Core role/capability protection
  - Same-origin policy enforcement
  - PSR-4 autoloading
  - `ABSPATH` check on all PHP files

- **Code Organization**
  - Namespace: `WP_Easy\RoleManager`
  - PSR-4 autoloading via Composer
  - Helper classes: RoleManager, CapabilityManager, UserManager, Logger
  - REST route handling in dedicated class
  - Admin menu management
  - Svelte 5 frontend with reactive state management

- **WordPress Integration**
  - Menu: "Role Manager" with "Manage Roles" and "Instructions" submenus
  - Compatible with WordPress 6.4+
  - Multisite compatible
  - Translation-ready (text domain: `wp-easy-role-manager`)
  - Uninstall cleanup script

- **Frontend Stack**
  - Svelte 5 (runes: $state, $derived, $props, $effect)
  - Vite build system
  - ES6 modules (no jQuery)
  - WPEA Framework CSS
  - Color tokens with context variants (success, warning, danger, info)

### Changed
- N/A (initial release)

### Deprecated
- N/A (initial release)

### Removed
- N/A (initial release)

### Fixed
- N/A (initial release)

### Security
- N/A (initial release)

---

## Release Notes

### 0.0.1-alpha
This is the initial alpha release of WP Easy Role Manager. The plugin is feature-complete for basic role and capability management but should be considered experimental. Use with caution in production environments.

**Breaking Changes:** None (initial release)

**Known Issues:**
- Settings tab not yet implemented
- Bulk operations may need performance optimization for large user bases
- No revert functionality for logged actions (planned for future release)

**Migration Guide:** N/A (initial release)
