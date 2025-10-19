# Changelog

All notable changes to WP Easy Role Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
