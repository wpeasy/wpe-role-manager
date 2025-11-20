<script>
/**
 * Settings Tab Component
 *
 * Configure plugin settings
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

let activeSubTab = $state('appearance');
let postTypes = $state([]);

let settings = $state({
  allowCoreCapAssignment: false,
  allowExternalDeletion: false,
  autosaveDebounce: 500,
  logRetention: 500,
  revisionRetention: 300,
  colorScheme: 'auto', // 'light', 'dark', 'auto'
  compactMode: false,
  restrictionsEnabledPostTypes: ['page'], // Array of post type slugs
});

// Load settings on mount
$effect(() => {
  fetchSettings();
  fetchPostTypes();
});

async function fetchPostTypes() {
  try {
    // Get all public post types
    const response = await store.apiRequest('/roles');
    // We'll fetch post types from a new endpoint
    // For now, let's use common post types
    postTypes = [
      { name: 'page', label: 'Pages' },
      { name: 'post', label: 'Posts' },
    ];

    // Try to get additional CPTs from WordPress
    if (window.wp && window.wp.data) {
      const types = window.wp.data.select('core').getPostTypes({ per_page: -1 });
      if (types) {
        postTypes = types
          .filter(type => type.viewable && !['attachment', 'wp_block'].includes(type.slug))
          .map(type => ({ name: type.slug, label: type.labels.name }));
      }
    }
  } catch (error) {
    console.error('Error fetching post types:', error);
  }
}

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

      // Handle both old and new format
      if (response.settings.restrictions_enabled_post_types) {
        settings.restrictionsEnabledPostTypes = response.settings.restrictions_enabled_post_types;
      } else if (response.settings.enable_restrictions_metabox) {
        // Migrate old setting - if it was enabled, enable for all post types
        settings.restrictionsEnabledPostTypes = ['page', 'post'];
      } else {
        settings.restrictionsEnabledPostTypes = ['page']; // Default to page only
      }
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
        restrictions_enabled_post_types: settings.restrictionsEnabledPostTypes,
      }),
    });
    store.showSaved();
  } catch (error) {
    console.error('Error saving settings:', error);
    store.showError();
  }
}

function togglePostTypeRestriction(postType) {
  const index = settings.restrictionsEnabledPostTypes.indexOf(postType);
  if (index > -1) {
    settings.restrictionsEnabledPostTypes = settings.restrictionsEnabledPostTypes.filter(pt => pt !== postType);
  } else {
    settings.restrictionsEnabledPostTypes = [...settings.restrictionsEnabledPostTypes, postType];
  }
  saveSettings();
}

function isPostTypeEnabled(postType) {
  return settings.restrictionsEnabledPostTypes.includes(postType);
}
</script>

<div class="wpea-stack" style="max-width: 1000px;">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Settings</h2>
    <p class="wpea-text-muted">Configure plugin settings and preferences.</p>
  </div>

  <!-- Sub-Tab Navigation -->
  <nav class="wpea-cluster wpea-cluster--sm" role="tablist" style="border-bottom: 1px solid var(--wpea-surface--border); padding-bottom: var(--wpea-space--sm);">
    <div class="wpea-cluster wpea-cluster--xs" style="flex-wrap: wrap;">
      <button
        type="button"
        class="wpea-btn wpea-btn--sm"
        class:wpea-btn--primary={activeSubTab === 'appearance'}
        onclick={() => activeSubTab = 'appearance'}
        role="tab"
        aria-selected={activeSubTab === 'appearance'}
      >
        Appearance
      </button>
      <button
        type="button"
        class="wpea-btn wpea-btn--sm"
        class:wpea-btn--primary={activeSubTab === 'security'}
        onclick={() => activeSubTab = 'security'}
        role="tab"
        aria-selected={activeSubTab === 'security'}
      >
        Security
      </button>
      <button
        type="button"
        class="wpea-btn wpea-btn--sm"
        class:wpea-btn--primary={activeSubTab === 'features'}
        onclick={() => activeSubTab = 'features'}
        role="tab"
        aria-selected={activeSubTab === 'features'}
      >
        Features
      </button>
      <button
        type="button"
        class="wpea-btn wpea-btn--sm"
        class:wpea-btn--primary={activeSubTab === 'performance'}
        onclick={() => activeSubTab = 'performance'}
        role="tab"
        aria-selected={activeSubTab === 'performance'}
      >
        Performance
      </button>
    </div>
  </nav>

  <!-- Appearance Sub-Tab -->
  {#if activeSubTab === 'appearance'}
    <div class="wpea-stack">
      <div class="wpea-card">
        <h3 class="wpea-heading wpea-heading--sm">Appearance Settings</h3>

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
    </div>
  {/if}

  <!-- Security Sub-Tab -->
  {#if activeSubTab === 'security'}
    <div class="wpea-stack">
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
    </div>
  {/if}

  <!-- Features Sub-Tab -->
  {#if activeSubTab === 'features'}
    <div class="wpea-stack">
      <div class="wpea-card">
        <h3 class="wpea-heading wpea-heading--sm">Content Restrictions</h3>

        <div class="wpea-stack wpea-stack--sm">
          <div class="wpea-alert wpea-alert--info">
            <p><strong>What does this do?</strong></p>
            <p>Enable the "Content Restrictions" metabox for specific post types. This allows you to restrict access to individual content items based on user capabilities or roles.</p>
            <p style="margin-top: var(--wpea-space--xs);"><strong>Features:</strong></p>
            <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
              <li>Enable/disable restrictions per post</li>
              <li>Filter by capabilities or roles</li>
              <li>Include child pages in restrictions</li>
              <li>Choose to show an access denied message or redirect to another URL</li>
            </ul>
          </div>

          <div style="margin-top: var(--wpea-space--md);">
            <h4 class="wpea-heading wpea-heading--sm">Enable Restrictions for Post Types:</h4>
            <p class="wpea-help" style="margin-bottom: var(--wpea-space--sm);">Toggle which post types should have the Content Restrictions metabox available on their edit screens.</p>

            <div class="wpea-stack wpea-stack--xs">
              {#each postTypes as postType}
                <label class="wpea-control">
                  <input
                    type="checkbox"
                    checked={isPostTypeEnabled(postType.name)}
                    onchange={() => togglePostTypeRestriction(postType.name)}
                  />
                  <span>{postType.label}</span>
                </label>
              {/each}

              {#if postTypes.length === 0}
                <p class="wpea-text-muted">Loading post types...</p>
              {/if}
            </div>
          </div>

          {#if settings.restrictionsEnabledPostTypes.length > 0}
            <div class="wpea-alert wpea-alert--success" style="margin-top: var(--wpea-space--md);">
              <p><strong>✓ Restrictions Metabox Enabled</strong></p>
              <p>The "Content Restrictions" metabox is now available for: <strong>{settings.restrictionsEnabledPostTypes.join(', ')}</strong></p>
              <p style="margin-top: var(--wpea-space--xs);">You can configure per-post restrictions directly from the edit page.</p>
            </div>
          {/if}
        </div>
      </div>
    </div>
  {/if}

  <!-- Performance Sub-Tab -->
  {#if activeSubTab === 'performance'}
    <div class="wpea-stack">
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
  {/if}
</div>
