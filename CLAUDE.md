# CLAUDE.md — WP Easy Role Manager

**Plugin Name:** WP Easy Role Manager  
**Description:** Easy UI to add, remove, enable, disable WordPress roles. Visualise and assign multiple roles to users. Visualise, add, and remove capabilities on roles. Also visualise the effective capabilities a user has based on their roles.  
**Namespace:** `WP_Easy\RoleManager`  
**Constants Prefix:** `WPE_RM_` (e.g., `WPE_RM_PLUGIN_PATH`, `WPE_RM_PLUGIN_URL`, `WPE_RM_VERSION`)  
**Text Domain (derived):** `wp-easy-role-manager`  
**Requires:** PHP 8.1+, WordPress 6.4+  
**Multisite:** Fully compatible; all role/cap operations must respect network context.

---

## Purpose

Provide a robust, admin-only Role and Capability manager with a clear, modern UI to:
- Create/disable/delete **non-core roles**.
- Add/remove/disable **non-core capabilities** on roles.
- Assign/remove **multiple roles** per user at once.
- Visualise **roles → capabilities** and **users → roles → effective capabilities**.

No public-facing functionality.

---

## Functionality

### Admin (Backend)

- **Role Management**
  - Create new roles (base from an existing role or from scratch).
  - Disable custom roles (soft-disable so users keep mapping; role cannot grant capabilities while disabled).
  - Delete custom roles (hard delete; blocked if assigned to users unless forced with migration wizard).
  - Disable capabilities (flag non-core capabilities as inactive for a role without losing config).
  - Delete capabilities from roles (non-core only; core caps are read-only).
  - Visualisation: matrix/table of **Roles × Capabilities** with filter, search, and bulk actions.

- **User Management**
  - Add/remove multiple roles from users in bulk.
  - Visualise each user’s **effective capabilities** (union across roles + explicit revocations).
  - Bulk reassign/migrate users from a disabled role to another role.

- **Safety & Guardrails**
  - **Core roles/capabilities are read-only.** No disable/delete on core items.
  - **Dangerous capability protection:** 26+ dangerous capabilities (like `unfiltered_html`, `edit_plugins`, `edit_themes`) are blacklisted from being assigned to roles by default.
  - **Configurable security override:** Setting to allow dangerous capability assignment when explicitly needed (disabled by default with prominent warnings).
  - Confirmation modals for destructive actions; typed confirmation required for role deletion.
  - Activity logging (up to 500 entries) with action and details filtering.
  - Test capability feature to check if a user has a specific capability with code generation (Shortcode, PHP, Fetch, REST URL).

- **UX**
  - Single-page admin UI with **tabbed navigation** (no page reloads).
  - **Autosave on change** (no "Save" button) with a **status indicator** (Saving… / Saved / Error).
  - Filter/search: by role, capability, user, and capability prefix.
  - Capability type filtering: All, Core, External, Custom.
  - **Import/Export** with two modes:
    - **Roles Only:** Export/import selected custom roles
    - **Full Backup:** Complete backup/restore of all custom roles, capabilities, and role assignments

### Frontend

- **Nothing is exposed on the frontend.** No shortcodes, no public REST.

---

## Code Style Guidelines

### General
1. All libraries are to be downloaded and served locally.

### PHP Conventions

1. **Namespace:** All classes use `WP_Easy\RoleManager`.
2. **Loading:** Use PSR-4 autoloading via Composer.
3. **Class Structure:** Final classes with static methods for WordPress hooks.
4. **Security:** Always use `defined('ABSPATH') || exit;` at the top of files.
5. **Sanitization:** Use WordPress sanitization/validation (`sanitize_text_field`, `sanitize_key`, `wp_unslash`, `rest_validate_*`).
6. **Nonces:** WordPress nonces for security; custom nonce for REST API.
7. **Constants:** Define:
   - `WPE_RM_PLUGIN_PATH`
   - `WPE_RM_PLUGIN_URL`
   - `WPE_RM_VERSION`
   - `WPE_RM_TEXTDOMAIN`
   - `WPE_RM_MIN_WP`, `WPE_RM_MIN_PHP`

### Method Patterns

- `init()`: Static method to register WordPress hooks.
- `render()`, `handle_*()`: Methods that output HTML or handle requests.
- Private helpers prefixed with underscore when appropriate.
- Extensive parameter validation and type checking.

### JavaScript Conventions
1. Download and serve all libraries locally.
2. Use **AlpineJS** wherever it makes sense; initialise with the `"init"` event.
3. If using **Svelte**, use **version 5**.
4. Use **ES6** only — never use jQuery.
5. **Admin Interfaces:**
   - Tab switching via JS/CSS (no page reloads).
   - **Autosave on change**, no "Save" button.
   - **Status indicator** showing when settings are saved.

### CSS
1. Use `@layer` on all generated CSS for the frontend; **never** use `@layer` for Admin area.
2. Use nested CSS where it makes sense.
3. Favour **Container Queries** over media queries.
4. Download files from https://www.wpeasy.au/ext/wpea, claude.md, test-ui.html, wpea-framework.css, wpea-wp-resets.css. Place them in assets/wpea
   - Use this framework as the basis for all Admin CSS for UI's etc.
5. **IMPORTANT:** Always add the `wpea` class to the main wrap div in all admin templates to ensure WPEA resets are applied.

---

## Security Practices

- **Dangerous capability blacklist:** 26+ capabilities that enable code execution or privilege escalation are blocked from being assigned to roles by default (configurable override available with warnings).
- **Same-origin enforcement** in REST API.
- **Nonce validation** on all endpoints.
- **Sanitization** of all user inputs.
- Cap all REST endpoints to users with `manage_options` (or custom high-privilege caps) and verify intent with nonces.
- **Administrator-only access:** All role/capability management requires `manage_options` capability.

---

## WordPress Integration

- Follows WordPress coding standards.
- Uses WordPress APIs extensively (Settings API, REST API, Users/Roles APIs, List Tables where appropriate).
- **Translation ready** with `wp-easy-role-manager` textdomain.
- Hooks into Users screen via actions/filters for role assignment tools.
- Compatible with **WordPress multisite**:
  - Role changes are **per-site** by default; network tools page can apply to selected sites.
  - Safeguards to prevent accidental network-wide destructive operations.
- All code must be **Multisite compatible**.

---

## Development Features

- **CodeMirror 6** integration (optional) for JSON role export/import editor in admin.
- Composer autoloading (PSR-4).
- Graceful fallbacks (Alpine.js optional; JSON editor falls back to `<textarea>`).
- Extensive error handling and validation.

---

## Implementation Notes

### Data Model & Storage
- Roles & caps are managed through WP's native role API (`WP_Roles`, `add_role`, `remove_role`, `add_cap`, `remove_cap`).
- "Disabled" state for roles/caps is stored in **plugin options**:
  - `wpe_rm_disabled_roles` (array of role slugs)
  - `wpe_rm_disabled_caps` (array of cap keys by role: `role => [cap, cap, ...]`)
  - `wpe_rm_settings` (plugin settings: `allow_core_cap_assignment`, `autosave_debounce`)
  - `wpe_rm_created_caps` (array of capability slugs created by this plugin)
  - `wpe_rm_managed_role_caps` (capability assignments managed by this plugin: `role => [cap, ...]`)
- Effective capabilities visualisation is computed on demand (union across roles minus disabled caps).
- **Full Backup Format:** JSON with `backup_type: "full"`, `version`, `timestamp`, `roles`, `capabilities`, `role_capabilities`

### Admin Pages
- Top-level menu: **WP Easy → Role Manager**
  - Tabs: **Roles**, **Capabilities**, **Users**, **Import/Export**, **Settings**, **Logs**
- **Roles Tab**
  - Create role (slug/name, copy from role optional).
  - Disable/Enable role (custom roles only).
  - Delete role (custom only) with user migration wizard.
- **Capabilities Tab**
  - Matrix: Roles × Capabilities with search, filter, bulk add/remove (non-core only).
  - Radio filters: All, Core, External, Custom capability types.
  - Disable capability per-role without deleting.
- **Users Tab**
  - Assign/remove multiple roles per user.
  - View effective capabilities (computed list).
  - Test capability feature: check if a user has a capability and generate code snippets (Shortcode, PHP, Fetch, REST URL).
  - Bulk operations with progress UI.
- **Import/Export**
  - Export modes: Roles Only or Full Backup
  - Roles Only: select specific custom roles or export all
  - Full Backup: all custom roles, capabilities, and role assignments with metadata
  - Import: smart detection of backup type with appropriate confirmations
  - File upload or paste JSON methods
- **Settings**
  - Security: Toggle dangerous capability protection (disabled by default)
  - Performance: Autosave debounce interval (100-5000ms)
  - Access Control: Information about administrator-only access
- **Logs**
  - Activity logging for all role/capability/user changes.
  - Stores up to 500 log entries with action, details, user, and timestamp.
  - Filter logs by Action (dropdown) and Details (search input).

### REST Endpoints (Admin-only)
- Namespace: `wpe-rm/v1`
- Endpoints (nonce + capability check required):
  - `GET /roles`, `POST /roles`, `PATCH /roles/{role}`, `DELETE /roles/{role}`
  - `POST /roles/{role}/caps`, `DELETE /roles/{role}/caps/{cap}`, `PATCH /roles/{role}/caps/{cap}` (toggle)
  - `GET /users`, `PATCH /users/{id}/roles`
  - `GET /users/{id}/effective-caps`, `GET /users/{id}/can/{capability}` (test capability)
  - `POST /import`, `GET /export?type=full` (supports full backup export)
  - `GET /settings`, `POST /settings` (plugin configuration)
  - `GET /logs`, `DELETE /logs`, `GET /logs/actions`
- Same-origin + nonce validation enforced; no public endpoints.

### Capability Rules & Core Protection
- Core roles/caps: **read-only** (cannot disable/delete).
- **Dangerous capability blacklist:** 26+ capabilities blocked by default (unfiltered_html, edit_plugins, edit_themes, etc.)
- Blacklist can be overridden via Settings → "Allow assigning dangerous capabilities to roles" (disabled by default)
- When blacklist is active, attempts to assign dangerous capabilities return 403 error with guidance to enable setting
- Non-core capabilities can be added/removed; disabling a cap on a role hides its effect without losing config.
- Deleting a role requires handling users: reassign roles or confirm forced removal.

### Multisite Behavior
- Site Admin context: operates on the current site’s roles.
- Network Admin “Tools” screen: optional bulk apply to selected sites with preview & dry-run.

### Constants (examples)
- `WPE_RM_PLUGIN_PATH` → absolute path to plugin dir.
- `WPE_RM_PLUGIN_URL` → plugin URL.
- `WPE_RM_VERSION` → semantic plugin version.
- `WPE_RM_TEXTDOMAIN` → `wp-easy-role-manager`.

---

## Example Class Skeletons

```php
<?php
// plugin.php
defined('ABSPATH') || exit;

final class Plugin {
    public static function init(): void {
        add_action('init', [self::class, 'i18n']);
        add_action('admin_menu', [Admin\Menu::class, 'register']);
        add_action('rest_api_init', [REST\Routes::class, 'register']);
    }
    public static function i18n(): void {
        load_plugin_textdomain('wp-easy-role-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}
WP_Easy\RoleManager\Plugin::init();
