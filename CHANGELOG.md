# Changelog

All notable changes to WP Easy Role Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.4-alpha] - 2025-01-21

### Added

- **Enhanced Select2 Role Assignment**
  - Multi-role Select2 interface on WordPress user-edit.php and user-new.php screens
  - Replaces default WordPress single-role dropdown
  - Visual indicators for disabled roles (red italic text in dropdown, red badges when selected)
  - Warning notice when disabled roles are selected
  - Fully functional save with proper WordPress integration

- **Comprehensive Disabled Role Enforcement**
  - Disabled roles completely hidden from `$user->roles` array on frontend
  - Role membership checks return false for disabled roles in all contexts
  - Bricks Builder conditions correctly evaluate disabled roles as not present
  - Capabilities from disabled roles completely blocked via `user_has_cap` filter
  - Admin contexts bypass filtering to allow role management

- **Test Capability "Role Disabled" Status**
  - Test Capability feature now detects when capability comes from disabled role
  - Returns special `role_disabled` status with list of disabled role names
  - UI displays "🚫 Role Disabled" instead of "✗ Denied"
  - Shows which specific roles are disabled affecting the capability

### Fixed

- **Select2 Save Bug**
  - Fixed WordPress's default role field overwriting custom role assignments
  - Added hook to remove `$_POST['role']` before WordPress processes it (priority 1)
  - Select2 role changes now persist correctly on both user-edit.php and user-new.php
  - User cache properly cleared to display fresh data after save

- **Metadata Filter Admin Bypass**
  - Disabled role filter now properly bypasses all admin contexts
  - Select2 can display and edit disabled roles in admin
  - Direct database reads for admin display to bypass all filters
  - Filter still applies on frontend and in third-party plugins

### Changed

- **Instructions Page**
  - Added "How Disabled Roles Work" section with comprehensive explanation
  - Documented visual indicators and enforcement behavior
  - Added use cases for disabling roles (seasonal, testing, deprecation)
  - Updated user assignment section with disabled role warnings

- **README Documentation**
  - Expanded disabled role functionality documentation
  - Added Third-Party Integrations section (Bricks Builder, WordPress User Screens)
  - Updated changelog with v0.0.3-alpha and v0.0.4-alpha features

### Technical

- **New Filter Hook**: `remove_default_role_field()` - Prevents WordPress role field from overwriting custom assignments
- **Enhanced CapabilityFilter**: Admin context detection via `is_admin()` check
- **Database Direct Reads**: `$wpdb->get_var()` for user capabilities in admin render functions
- **User Cache Management**: `clean_user_cache()` calls ensure fresh data display

## [0.0.3-alpha] - 2025-01-20

### Added

- **Custom Scrollbar Styling**
  - Styled scrollbars throughout plugin to match light/dark mode theme
  - Custom scrollbar colors: light mode (neutral-l-6 thumb, neutral-l-9 track), dark mode (neutral-d-5 thumb, neutral-d-9 track)
  - Rounded scrollbar design with hover states
  - Applied to all table views (Roles, Capabilities, Users, Logs)

- **Double Scrollbar Feature**
  - Synchronized top scrollbar for horizontally scrollable tables
  - Top scrollbar appears automatically when table has horizontal overflow
  - Bidirectional scroll synchronization (top ↔ bottom)
  - ResizeObserver integration for responsive updates
  - Svelte action: `doubleScrollbar` for reusable implementation

- **Bricks Builder Integration**
  - Custom condition group: "Role Manager" in Bricks Builder conditionals
  - Condition: "User Has Capability" - check if current user has a capability
  - Condition: "Specific User Has Capability" - check capability for specific user ID
  - Dynamic data tag: `{wpe_has_capability:cap_name}` returns true/false/denied
  - Optional user ID parameter: `{wpe_has_capability:cap_name:user_id}`
  - "Bricks Token" button in Test Capability modal generates properly formatted tokens

- **Light/Dark/Auto Theme Switcher**
  - Setting: Color Scheme with three options (Light, Dark, Respect OS Setting)
  - Auto-detect system preference when set to "Respect OS Setting" (default)
  - Theme changes applied via `data-color-scheme` attribute on document root
  - Theme persisted via REST API settings
  - Theme changes logged to activity log

- **Compact Mode**
  - New setting: "Compact Mode" to reduce font sizes, spacing, and padding
  - Reduces table font to 13px, row padding by ~30%
  - Affects buttons, badges, inputs, headings, cards
  - Applied via `data-compact-mode` attribute for global control
  - Particularly useful for viewing more data on screen

- **Custom Menu Icon**
  - Replaced default dashicons-admin-users with custom SVG icon
  - Icon represents role/capability management with interconnected nodes
  - Properly sized (20x20) for WordPress admin sidebar
  - Applied to both regular and network admin menus

- **Log Retention Setting**
  - Configurable log retention (100-10000 entries, default 500)
  - Setting persisted via REST API
  - Logger class uses configurable retention from settings
  - Allows users to adjust based on their monitoring needs

### Changed

- **Settings Tab Reorganization**
  - Removed "Access Control" section
  - Added "Appearance" section with Color Scheme and Compact Mode settings
  - Log Retention moved to Settings tab from hardcoded value
  - Simplified UI with clearer section grouping

- **WPEA Framework CSS Optimization**
  - Consolidated redundant dark mode color definitions
  - Reduced framework CSS by ~37 lines
  - Simplified from three dark mode blocks to two (OS preference + explicit dark mode)
  - Only override base colors in dark mode (variants auto-recalculate via color-mix)

- **Shadow Tokens Enhancement**
  - Shadows now use color tokens instead of static RGBA values
  - Dark mode shadows become subtle colored glows using primary color
  - Layered shadow approach: colored glow + black depth shadow
  - All shadow levels (s, m, l, xl) updated with dynamic color-mix

- **Table Structure**
  - All tables wrapped in `.wpea-table-wrapper` div for overflow control
  - Tables now properly scrollable horizontally when content overflows
  - Consistent structure across all tabs (Roles, Users, Capabilities, Logs)

### Removed

- **Capability Filters from Capabilities Tab**
  - Removed "Show Granted" and "Show not granted" filters
  - Simplified capability matrix to show all states clearly
  - Reduced UI complexity in Capabilities tab

### Fixed

- **Badge Dark Mode Issues (Comprehensive Fix)**
  - Fixed all badges throughout plugin not respecting dark mode
  - Removed hardcoded inline styles from RolesTab.svelte (Type, Status badges)
  - Removed hardcoded inline styles from CapabilitiesTab.svelte (Type badges)
  - Removed hardcoded inline styles from UsersTab.svelte (Role badges)
  - Removed hardcoded inline styles from templates/instructions-page.php (example badges)
  - Changed base `.badge` class to use CSS custom properties (`--wpea-badge--bg`, `--wpea-badge--fg`)
  - Added proper dark mode support for all badge variants (core, external, success, warning, danger)
  - Added all three dark mode trigger support: `[data-color-scheme="dark"]`, OS preference, `.wpea-dark` legacy

- **Bricks Builder Integration Timing**
  - Fixed BRICKS_VERSION not defined error on plugin initialization
  - Changed hook from `plugins_loaded` to `after_setup_theme` with priority 20
  - Ensures Bricks theme constant is available before integration attempts

- **Bricks Builder Type Error**
  - Fixed PHP Fatal error: `render_dynamic_tag(): Argument #1 ($tag) must be of type string, array given`
  - Changed parameter type from `string $tag` to mixed `$tag` with type check
  - Handles cases where Bricks passes array to filter

- **Card Background Contrast**
  - Added distinct backgrounds for app vs cards to improve visual separation
  - App background: neutral-l-9 (light) / neutral-d-9 (dark)
  - Card background: white (light) / neutral-d-8 (dark)
  - Better depth perception and content hierarchy

### Technical

- **New File**: `src-svelte/lib/doubleScrollbar.js` - Svelte action for synchronized scrollbars
- **New Integration**: `src/Integrations/BricksBuilder.php` - Complete Bricks Builder integration
- **CSS Enhancement**: `assets/css/admin.css` - Scrollbar styling, compact mode, badge fixes (lines 200-260)
- **Build System**: Vite build updated to include new Svelte action and imports

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
