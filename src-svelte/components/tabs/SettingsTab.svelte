<script>
/**
 * Settings Tab Component
 *
 * Configure plugin settings
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

let settings = $state({
  allowCoreCapAssignment: false,
  allowExternalDeletion: false,
  autosaveDebounce: 500,
  logRetention: 500,
  revisionRetention: 300,
  colorScheme: 'auto', // 'light', 'dark', 'auto'
  compactMode: false,
  enableRestrictionsMetabox: false,
});

// Load settings on mount
$effect(() => {
  fetchSettings();
});

async function fetchSettings() {
  try {
    const response = await store.apiRequest('/settings');
    if (response.settings) {
      settings.allowCoreCapAssignment = response.settings.allow_core_cap_assignment || false;
      settings.allowExternalDeletion = response.settings.allow_external_deletion || false;
      settings.autosaveDebounce = response.settings.autosave_debounce || 500;
      settings.logRetention = response.settings.log_retention || 500;
      settings.revisionRetention = response.settings.revision_retention || 300;
      settings.colorScheme = response.settings.color_scheme || 'auto';
      settings.compactMode = response.settings.compact_mode || false;
      settings.enableRestrictionsMetabox = response.settings.enable_restrictions_metabox || false;
    }
  } catch (error) {
    console.error('Error fetching settings:', error);
  }
}

async function saveSettings() {
  try {
    store.showSaving();
    await store.apiRequest('/settings', {
      method: 'POST',
      body: JSON.stringify({
        allow_core_cap_assignment: settings.allowCoreCapAssignment,
        allow_external_deletion: settings.allowExternalDeletion,
        autosave_debounce: settings.autosaveDebounce,
        log_retention: settings.logRetention,
        revision_retention: settings.revisionRetention,
        color_scheme: settings.colorScheme,
        compact_mode: settings.compactMode,
        enable_restrictions_metabox: settings.enableRestrictionsMetabox,
      }),
    });
    store.showSaved();
  } catch (error) {
    console.error('Error saving settings:', error);
    store.showError();
  }
}
</script>

<div class="wpea-stack" style="max-width: 800px;">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Settings</h2>
    <p class="wpea-text-muted">Configure plugin settings and preferences.</p>
  </div>

  <div class="wpea-stack">
    <!-- Appearance Settings -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Appearance</h3>

      <div class="wpea-field">
        <label for="color-scheme" class="wpea-label">Color Scheme:</label>
        <select
          id="color-scheme"
          bind:value={settings.colorScheme}
          onchange={saveSettings}
          class="wpea-input"
          style="max-width: 300px;"
        >
          <option value="light">Light</option>
          <option value="dark">Dark</option>
          <option value="auto">Respect OS Setting</option>
        </select>
        <p class="wpea-help">Choose your preferred color scheme. "Respect OS Setting" will automatically match your system preference.</p>
      </div>

      <div class="wpea-field">
        <label class="wpea-control">
          <input
            type="checkbox"
            bind:checked={settings.compactMode}
            onchange={saveSettings}
          />
          <span>Compact Mode</span>
        </label>
        <p class="wpea-help">Reduces font sizes, spacing, and padding throughout the interface, especially in tables. Useful for viewing more data on screen.</p>
      </div>
    </div>

    <!-- Security Settings -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Security Settings</h3>

      <div class="wpea-stack wpea-stack--sm">
        <label class="wpea-control">
          <input
            type="checkbox"
            bind:checked={settings.allowCoreCapAssignment}
            onchange={saveSettings}
          />
          <span>Allow assigning dangerous capabilities to roles</span>
        </label>

        <div class="wpea-alert wpea-alert--warning">
          <p><strong>Security Warning:</strong> Enabling this option allows you to assign dangerous WordPress capabilities to roles. This includes capabilities that can lead to code execution, privilege escalation, or complete site takeover:</p>
          <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
            <li><code>unfiltered_html</code>, <code>unfiltered_upload</code> - Can execute arbitrary code</li>
            <li><code>edit_plugins</code>, <code>edit_themes</code> - Can modify site code</li>
            <li><code>manage_options</code>, <code>promote_users</code> - Can elevate privileges</li>
            <li><code>install_plugins</code>, <code>update_core</code> - Can install malicious code</li>
          </ul>
          <p style="margin-top: var(--wpea-space--xs);"><strong>Recommended:</strong> Only enable this if you absolutely need to assign these capabilities and fully understand the security implications. These capabilities should normally only be available to the Administrator role.</p>
        </div>

        {#if settings.allowCoreCapAssignment}
          <div class="wpea-alert wpea-alert--danger">
            <p><strong>⚠️ DANGER: Dangerous Capability Protection Disabled</strong></p>
            <p>The security protection preventing dangerous capabilities from being assigned to roles is now disabled. You can assign any capability to any role, including those that can compromise your entire site.</p>
            <p style="margin-top: var(--wpea-space--xs);"><strong>Use with extreme caution!</strong></p>
          </div>
        {/if}
      </div>

      <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md); padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
        <label class="wpea-control">
          <input
            type="checkbox"
            bind:checked={settings.allowExternalDeletion}
            onchange={saveSettings}
          />
          <span>Allow deletion of external roles and capabilities</span>
        </label>

        <div class="wpea-alert wpea-alert--info">
          <p><strong>What are external roles and capabilities?</strong></p>
          <p>External roles and capabilities are created by other plugins or themes (not by WordPress core or this plugin).</p>
          <p style="margin-top: var(--wpea-space--xs);"><strong>Default behavior:</strong> External roles and capabilities are read-only and cannot be deleted. This prevents accidental removal of functionality added by other plugins.</p>
          <p style="margin-top: var(--wpea-space--xs);"><strong>When enabled:</strong> You can delete external roles and capabilities. Use this when cleaning up after uninstalled plugins or removing unwanted roles/capabilities from your system.</p>
        </div>

        {#if settings.allowExternalDeletion}
          <div class="wpea-alert wpea-alert--warning">
            <p><strong>⚠️ External Deletion Enabled</strong></p>
            <p>You can now delete roles and capabilities created by other plugins. Be careful not to remove functionality that other plugins depend on. Make sure you know what you're deleting before proceeding.</p>
          </div>
        {/if}
      </div>
    </div>

    <!-- Content Restrictions -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Content Restrictions</h3>

      <div class="wpea-stack wpea-stack--sm">
        <label class="wpea-control">
          <input
            type="checkbox"
            bind:checked={settings.enableRestrictionsMetabox}
            onchange={saveSettings}
          />
          <span>Enable restrictions metabox on edit screens</span>
        </label>

        <div class="wpea-alert wpea-alert--info">
          <p><strong>What does this do?</strong></p>
          <p>When enabled, a "Content Restrictions" metabox will be added to all Pages, Posts, and Custom Post Types. This allows you to restrict access to individual content items based on user capabilities.</p>
          <p style="margin-top: var(--wpea-space--xs);"><strong>Features:</strong></p>
          <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
            <li>Enable/disable restrictions per post</li>
            <li>Include child pages in restrictions</li>
            <li>Select which capabilities are required to view the content</li>
            <li>Choose to show an access denied message or redirect to another URL</li>
          </ul>
        </div>

        {#if settings.enableRestrictionsMetabox}
          <div class="wpea-alert wpea-alert--success">
            <p><strong>✓ Restrictions Metabox Enabled</strong></p>
            <p>The "Content Restrictions" metabox is now available on all post edit screens. You can configure per-post restrictions directly from the edit page.</p>
          </div>
        {/if}
      </div>
    </div>

    <!-- Performance Settings -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Performance Settings</h3>

      <div class="wpea-field">
        <label for="autosave-debounce" class="wpea-label">Autosave Delay (milliseconds):</label>
        <input
          type="number"
          id="autosave-debounce"
          bind:value={settings.autosaveDebounce}
          min="100"
          max="5000"
          step="100"
          onchange={saveSettings}
          class="wpea-input"
          style="max-width: 300px;"
        />
        <p class="wpea-help">Delay before automatically saving changes (100-5000ms). Higher values reduce server load but delay saves.</p>
      </div>

      <div class="wpea-field">
        <label for="log-retention" class="wpea-label">Log Retention (number of entries):</label>
        <input
          type="number"
          id="log-retention"
          bind:value={settings.logRetention}
          min="100"
          max="10000"
          step="100"
          onchange={saveSettings}
          class="wpea-input"
          style="max-width: 300px;"
        />
        <p class="wpea-help">Maximum number of activity log entries to retain (100-10000). Oldest entries are automatically removed when this limit is reached.</p>
      </div>

      <div class="wpea-field">
        <label for="revision-retention" class="wpea-label">Revision Retention (number of entries):</label>
        <input
          type="number"
          id="revision-retention"
          bind:value={settings.revisionRetention}
          min="50"
          max="1000"
          step="10"
          onchange={saveSettings}
          class="wpea-input"
          style="max-width: 300px;"
        />
        <p class="wpea-help">Maximum number of revisions to retain (50-1000). Oldest revisions are automatically removed when this limit is reached. Revisions allow you to restore previous states of roles and capabilities.</p>
      </div>
    </div>

  </div>
</div>
