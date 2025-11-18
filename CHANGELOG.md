# Changelog

All notable changes to WP Easy Role Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.5-beta] - 2025-01-18

### Added

- **WP Easy Branding**
  - Added WP Easy logo to Role Manager admin panel header
  - Added WP Easy logo to Instructions page "Welcome" section
  - Logo appears aligned to right edge of page/card headers

### Changed

- **Dark/Light Mode Logo Support**
  - Automatic logo switching based on color scheme preference
  - Dark-colored logo displays in light mode
  - Light-colored logo displays in dark mode
  - Respects OS preference when color scheme is set to "Auto"

### Technical

- **admin.css**:
  - Added `.wpe-rm-header` flexbox styles for logo positioning
  - Added `.wpe-rm-logo`, `.wpe-rm-logo-light`, `.wpe-rm-logo-dark` styles
  - Implemented dark/light mode logo switching with `[data-color-scheme]` selectors
  - Added OS preference support via `@media (prefers-color-scheme: dark)`
- **admin-page.php**: Added logo images to page header
- **instructions-page.php**: Added logo images to "Welcome to Role Manager" card header
- **assets/images**: Added `logo-light-mode.svg` and `logo-dark-mode.svg`

## [0.1.4-beta] - 2025-01-17

### Added

- **Role-Based Filtering**
  - Content restrictions now support filtering by Role OR Capability
  - New radio buttons in metabox: "Filter by Capability" or "Filter by Role"
  - Select2 multi-select dropdown for roles (same as capabilities)
  - Sample code generation adapts based on filter type selection
  - Role filtering checks `in_array($role, $user->roles)`

- **Restrictions Column in Post Tables**
  - New "Restrictions" column added to Pages, Posts, and CPT admin tables
  - Shows at-a-glance restriction summary with badges
  - Displays: RESTRICTED badge, +CHILDREN badge (for pages), filter type, action type, and list of roles/capabilities
  - Shows first 2 items then "+X more" if there are additional
  - Column appears before "Date" column

### Changed

- **Metabox UI Enhancement**
  - Added filter type radio selection (Capability/Role)
  - Conditional display of capability or role fields based on selection
  - Smooth jQuery transitions when switching filter types

- **Sample Code Generation**
  - Updated `generatePHPRestrictPage()` to support both role and capability filtering
  - Generated code includes proper role checking logic
  - Example comments updated to show both filter types

- **Documentation Updates**
  - Instructions page updated with role filtering information
  - Added step to choose filter type in setup instructions
  - Clarified how role vs capability filtering works

### Technical

- **RestrictionsMetabox.php**:
  - Added `_wpe_rm_filter_type` meta field
  - Added `_wpe_rm_required_roles` meta field
  - Updated `enforce_restrictions()` to handle both filter types
  - Added `add_restrictions_column()` and `display_restrictions_column()` methods
  - Column registration for all public post types
- **metabox.js**: Added filter type toggle functionality for capability/role fields
- **UsersTab.svelte**: Updated PHP code generator with role/capability filtering support
- **instructions-page.php**: Updated documentation for role filtering

## [0.1.3-beta] - 2025-01-17

### Added

- **Content Restrictions Metabox**
  - New optional metabox for Pages, Posts, and CPTs to restrict content access
  - Enable via Settings → Content Restrictions
  - Select required capabilities (users must have at least one)
  - Option to include child pages in restrictions
  - Choose to show message or redirect when access denied
  - Administrators always bypass restrictions
  - Non-logged-in users are automatically restricted

- **Settings & Documentation**
  - New "Content Restrictions" section in Settings tab
  - Added comprehensive metabox documentation to Instructions page
  - Settings to enable/disable metabox feature globally

### Fixed

- **Select2 Styling**
  - Fixed remove button "×" positioning on multi-select badges
  - Remove button now appears on the right side of text (not covering it)
  - Applied fix globally to User Roles selector and Capability selector
  - Used `position: static` override and JavaScript DOM reordering

### Changed

- **Sample Code Generation**
  - Removed `<?php` opening tag from all generated code snippets
  - Code now ready for direct paste into WPCodeBox or functions.php
  - All restriction code now excludes administrators from restrictions
  - Login redirect code excludes administrators from custom redirects
  - Updated code comments to clarify administrator exclusion

### Technical

- **RestrictionsMetabox.php**: New class handling metabox registration, rendering, saving, and enforcement
- **metabox.js**: JavaScript for Select2 initialization and conditional field display
- **metabox.css**: Styling for metabox with proper Select2 integration
- **UsersTab.svelte**: Updated all PHP code generators to exclude administrators
- **UserProfile.php**: Fixed Select2 badge styling with flexbox and DOM manipulation
- **Routes.php**: Added `enable_restrictions_metabox` setting support
- **Plugin.php**: Initialize RestrictionsMetabox on plugins_loaded
- **SettingsTab.svelte**: Added Content Restrictions settings UI

## [0.1.2-beta] - 2025-01-17

### Fixed

- **Restrict Page/Post PHP Code Generation**
  - Fixed undefined `$post` variable error in generated code
  - Now correctly uses `$page_id` from foreach loop when checking child pages
  - Added proper user validation with `is_user_logged_in()` check
  - Added `wp_get_current_user()` to get user object before capability check
  - Changed to `user_can($user, 'capability')` for reliability
  - Added capability check to conditional logic (only restrict if user lacks capability)

### Technical

- **UsersTab.svelte `generatePHPRestrictPage()`**:
  - Fixed children loop to iterate through `$restricted_pages` array properly
  - Each page ID now correctly passed to `get_page_children($page_id, get_pages())`
  - Capability check integrated into both restriction paths (with/without children)
  - Code now validates user is logged in before attempting restrictions

## [0.1.1-beta] - 2025-01-17

### Fixed

- **Login Redirect PHP Code Generation**
  - Fixed generated code to properly validate `$user` is `WP_User` instance before use
  - Changed from `current_user_can()` to `user_can($user, 'capability')` for better reliability
  - Added URL validation with `url_to_postid()` to prevent redirects to non-existent pages
  - Increased filter priority from 10 to 20 for better compatibility with other plugins

### Changed

- **Code Generation Documentation**
  - Updated PHP button description to mention multiple templates available
  - Added prominent warning about hook timing requirements for login redirect code
  - Documented WPCodeBox hook settings (Auto-Execute or "init" hook)
  - Added note that code must run before `login_redirect` hook is called

### Technical

- **UsersTab.svelte**:
  - `generatePHPRedirectLogin()`: Complete rewrite based on tested working code
  - Improved code comments explaining WPCodeBox hook requirements
  - Better error handling and validation patterns

- **Instructions Page**:
  - Added warning alert box for PHP hook timing requirements
  - Updated PHP code generation description
  - Clearer guidance for WPCodeBox and functions.php users

## [0.1.0-beta] - 2025-01-17

### Added

- **Automatic Capability Cleanup on Role Deletion**
  - When deleting a role, all capabilities belonging to that role are now automatically removed from ALL roles
  - Prevents orphaned custom capabilities from remaining after role deletion
  - Affects Administrator and all other roles that have the capabilities
  - New tracking system: `wpe_rm_role_capabilities` option maps roles to their owned capabilities
  - Comprehensive cleanup: removes from managed caps, created caps, and disabled caps lists
  - Activity log shows number of capabilities removed during deletion

- **WordPress Slug Validation**
  - Real-time validation for role and capability slugs
  - Role slugs: 1-20 characters (WordPress database constraint)
  - Capability slugs: 1-191 characters (WordPress meta key limit)
  - Visual error states with red border and error messages
  - Create/Add buttons disabled when validation fails
  - Both frontend and backend validation for consistency
  - Enforces WordPress slug character set: lowercase letters, numbers, hyphens, underscores

- **Standard Capabilities Toggle**
  - "Add Standard Capabilities" now behind optional checkbox in Create Role modal
  - Makes it clear that standard capabilities are optional, not required
  - Button only appears when checkbox is enabled
  - Prevents confusion about mandatory capability selection

### Changed

- **CapabilityManager Enhancement**
  - `add_capability()` method now accepts `belongs_to` parameter to track capability ownership
  - New method: `remove_role_capabilities_from_all_roles()` for comprehensive cleanup
  - Capability ownership tracked separately from managed role-capability pairs

- **Create Role Workflow**
  - Standard capabilities now passed with `belongs_to` parameter to mark ownership
  - Both new role and administrator capabilities tagged with owning role
  - Enables automatic cleanup when role is deleted

### Fixed

- **PHP Context Menu Dark Mode**
  - Fixed transparent background in dark mode (white text on transparent = invisible)
  - Changed from invalid `--wpea-surface--base` to correct `--wpea-surface--panel` variable
  - Menu and input backgrounds now properly adapt to light/dark themes
  - All WPEA framework variables verified and corrected

- **Capability Orphaning**
  - Fixed custom capabilities remaining on Administrator after role deletion
  - Fixed capabilities remaining on other roles after creating role is deleted
  - All role-owned capabilities now properly cleaned up across entire system

### Technical

- **New Tracking System**:
  - `wpe_rm_role_capabilities` option: Maps role slugs to array of owned capability names
  - Tracks capability ownership separately from assignment
  - Enables complete cleanup on role deletion

- **Validation System**:
  - `validateSlug()` function in `utils.js` for frontend validation
  - Returns `{valid: boolean, error: string|null}` object
  - Backend validation in `Routes.php` for both role and capability creation
  - Consistent error messages between frontend and backend

- **Frontend Updates**:
  - RolesTab.svelte: Added `addStandardCaps` toggle, `slugValidation` state, validation UI
  - CapabilitiesTab.svelte: Added `capValidation` state, validation UI
  - Both components: Error state styling with `wpea-input--error` and `wpea-help--error` classes

- **Backend Updates**:
  - Routes.php `add_capability` endpoint: Accepts and passes `belongs_to` parameter
  - Routes.php `delete_role` endpoint: Calls cleanup before role deletion
  - Routes.php `create_role` endpoint: Validates slug length (1-20 characters)
  - CapabilityManager: New comprehensive cleanup method with metadata removal

- **Build System**:
  - All changes compiled successfully with Vite
  - No breaking changes to existing functionality
  - Backward compatible with existing role/capability data

### Migration Notes

**Moving from Alpha to Beta**: This release marks the transition to beta status. The plugin has reached feature maturity with comprehensive role/capability management, safety mechanisms, and automatic cleanup. While still not recommended for production use without thorough testing, the beta status indicates increased stability and completeness.

**Capability Cleanup**: Existing installations will begin tracking capability ownership starting with this version. Previously created capabilities will not have ownership tracking, but any capabilities created or modified after this update will be properly tracked and cleaned up on role deletion.

**Slug Validation**: Existing slugs that exceed length limits will continue to work but cannot be recreated if deleted. New roles/capabilities must meet WordPress constraints (20 chars for roles, 191 for capabilities).

## [0.0.9-alpha] - 2025-01-25

### Added

- **Enhanced Role Deletion Safety**
  - Delete modal shows warning when role is assigned to users
  - Displays user count assigned to role
  - Required checkbox confirmation to remove role from all users before deletion
  - Delete button disabled until both confirmations complete (typed "delete" + checkbox if users exist)
  - Backend removes role from all users before deletion when `remove_from_users=true`
  - Activity log entry created when role removed from users before deletion

- **Automatic Data Refresh on Tab Switch**
  - Data automatically refreshes when switching between tabs
  - Roles tab: refreshes roles data
  - Capabilities tab: refreshes capability matrix
  - Users tab: refreshes users data
  - Settings tab: refreshes settings
  - Other tabs: refresh all core data
  - Console logging for tab change tracking

### Changed

- **Status Indicators Behavior**
  - Removed "Saving/Saved" status from data fetch operations
  - Status indicators now only appear for actual save/modify operations
  - Silent background data loading on tab switches
  - Error status still shown if fetch operations fail

### Technical

- **Routes.php Enhancement**:
  - `delete_role` endpoint: Added `remove_from_users` parameter handling
  - Removes role from all users via `$user->remove_role()` before deletion
  - Activity logging for user role removal before deletion

- **RolesTab.svelte Updates**:
  - Added `removeFromUsers` state variable
  - Enhanced delete modal with user count warning
  - Required checkbox for roles with users
  - Delete button disabled logic updated
  - API call includes `remove_from_users` query parameter

- **App.svelte Enhancements**:
  - Added `previousTab` state tracking
  - `$effect` hook watches for tab changes
  - Automatic data refresh based on active tab
  - Skip refresh on initial page load

- **Store Updates (app.svelte.js)**:
  - Removed `showSaving()` and `showSaved()` calls from all fetch methods
  - `fetchRoles()`, `fetchUsers()`, `fetchCapabilities()`, `fetchCapabilityMatrix()`: silent fetching
  - Error status still displayed on fetch failures

## [0.0.8-alpha] - 2025-01-25

### Added

- **Sortable Table Columns**
  - Role Name column in Roles tab now sortable with alphabetical default (ascending)
  - Capability column in Capabilities tab now sortable with alphabetical default (ascending)
  - Click column headers to toggle between ascending (↑) and descending (↓)
  - Visual indicators show current sort direction
  - Implemented with Svelte 5 `$derived.by()` for reactive sorting

- **Complete Revision Tracking**
  - Capability toggle operations (grant/deny/unset) now save revision snapshots
  - All 7 role/capability modification operations tracked:
    - Role creation, modification, deletion
    - Capability addition, removal, toggle
    - Role enable/disable operations
  - Comprehensive audit trail for all configuration changes

- **Plugin Metadata in Revision Snapshots**
  - Snapshots now include complete plugin metadata section
  - Tracks: `created_roles`, `created_caps`, `managed_role_caps`, `disabled_roles`, `disabled_caps`
  - Ensures proper restoration of plugin-created items and their classifications
  - Plugin-created capabilities/roles maintain "Custom" classification after restoration
  - Prevents items from incorrectly showing as "External" after restore

### Changed

- **Tab Navigation Reordering**
  - Updated tab order: Roles, Capabilities, Users, Import/Export, Settings, Tools, Revisions, Logs
  - Settings moved before Tools for better logical grouping
  - Revisions and Logs grouped at the end for historical/audit features

- **User Profile Enhancement**
  - Default WordPress role selector now hidden on user-new.php page
  - Prevents confusion between default dropdown and plugin's multi-role Select2
  - Enhanced CSS selectors for comprehensive hiding across all WordPress variations
  - Uses multiple selector patterns including `:has()` pseudo-class

### Fixed

- **Revision Restore Metadata Loss**
  - Fixed plugin-created capabilities restoring as "External" instead of "Custom"
  - Restore operation now applies metadata first, then individual roles
  - All plugin tracking options properly restored from snapshot metadata
  - Complete state restoration including disabled states and managed assignments

### Technical

- **Revisions.php Enhancements**:
  - `get_complete_snapshot()`: Added `plugin_metadata` section with all tracking options
  - `restore_role()`: Metadata restoration added at beginning of restore process
  - Ensures `wpe_rm_created_roles`, `wpe_rm_created_caps`, `wpe_rm_managed_role_caps` properly restored

- **Routes.php Enhancement**:
  - `toggle_capability` endpoint (line 744-751): Added complete snapshot revision save before toggle

- **UserProfile.php Update**:
  - `hide_default_role_select()`: Enhanced CSS selectors with multiple variations
  - Covers all WordPress markup patterns for role field hiding

- **Svelte Component Updates**:
  - RolesTab.svelte: Added `sortColumn`, `sortDirection` state and `toggleSort()` function
  - CapabilitiesTab.svelte: Added sortable capability column with reactive filtering
  - App.svelte: Updated tabs array order

## [0.0.7-alpha] - 2025-01-25

### Changed

- **Capabilities Tab UI Optimization**
  - Search, Role Select, and Radio filter now on single row
  - Reduced vertical space usage
  - More compact layout
  - Search input: 250px width
  - Role Select: 180px width

## [0.0.6-alpha] - 2025-01-21

### Added

- **WordPress Shortcode: `[wpe_rm_cap]`**
  - Conditionally display content based on user capabilities
  - Attributes: `capability` (required), `granted` (true/false), `user_id` (optional)
  - Supports both current user and specific user ID checks
  - Example: `[wpe_rm_cap capability="edit_posts" granted="true"]Content[/wpe_rm_cap]`

- **Bricks Builder Capability Dropdown**
  - "User Has Capability" condition now uses searchable dropdown
  - Automatically populated with all available capabilities
  - Capabilities sorted alphabetically
  - Filterable/searchable for easy selection

- **Developer Integrations Documentation**
  - Added section 3.5 to Instructions page
  - Complete shortcode documentation with examples
  - Bricks Builder integration guide
  - Code generation examples for all methods

### Changed

- **Bricks Builder Dynamic Tag Updates**
  - Tag renamed: `{wpe_rm_has_capability}` → `{wpe_rm_capability_status}`
  - Return values changed: `true/false/denied` → `granted/not-granted/denied`
  - More semantic and descriptive status strings
  - Tag label updated: "Has Capability" → "Capability Status"

### Technical

- **New Class**: `src/Helpers/Shortcodes.php` - WordPress shortcode handler
- **Bricks Integration**: Added `get_all_capabilities()` method for dropdown population
- **Shortcode Registration**: Initialized in `Plugin.php` via `Shortcodes::init()`

## [0.0.5-alpha] - 2025-01-21

### Added

- **Test Capability: Granting Roles Display**
  - Test Capability now shows which roles granted a capability
  - Displays "Granted by: Role1, Role2, ..." under granted results
  - REST API returns `granting_roles` array with role names
  - Helps administrators understand capability sources

### Changed

- **Bricks Builder Prefix Update**
  - Condition keys: `wpe_user_capability` → `wpe_rm_user_capability`
  - Condition keys: `wpe_user_capability_for_user` → `wpe_rm_user_capability_for_user`
  - Dynamic tag: `{wpe_has_capability}` → `{wpe_rm_capability_status}`
  - Tag label: "WPE: Has Capability" → "WPE RM: Has Capability"
  - Better namespacing specific to Role Manager plugin
  - Avoids conflicts with other WP Easy plugins

### Technical

- **REST API Enhancement**: `/users/{id}/can/{capability}` now includes `granting_roles` in response
- **Namespace Consistency**: All Bricks Builder integrations now use `wpe_rm_` prefix

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
  - Dynamic data tag: `{wpe_rm_capability_status:cap_name}` returns granted/not-granted/denied
  - Optional user ID parameter: `{wpe_rm_capability_status:cap_name:user_id}`
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
