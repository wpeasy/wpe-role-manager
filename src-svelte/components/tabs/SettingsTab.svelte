<script>
/**
 * Settings Tab Component
 *
 * Configure plugin settings
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { Card, Button, Alert, Input, Switch, Badge, VerticalTabs } from '../../lib/index.ts';

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
  enableBlockConditions: true, // Enable block visibility conditions
  enableElementorConditions: true, // Enable Elementor visibility conditions
  enableBricksConditions: true, // Enable Bricks Builder conditions
});

let pluginStatus = $state({
  elementorActive: false,
  bricksActive: false,
});

// Load settings on mount
onMount(() => {
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
      // Feature toggles - default to true if not set
      settings.enableBlockConditions = response.settings.enable_block_conditions ?? true;
      settings.enableElementorConditions = response.settings.enable_elementor_conditions ?? true;
      settings.enableBricksConditions = response.settings.enable_bricks_conditions ?? true;

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
    // Plugin status
    if (response.plugin_status) {
      pluginStatus.elementorActive = response.plugin_status.elementor_active || false;
      pluginStatus.bricksActive = response.plugin_status.bricks_active || false;
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
        enable_block_conditions: settings.enableBlockConditions,
        enable_elementor_conditions: settings.enableElementorConditions,
        enable_bricks_conditions: settings.enableBricksConditions,
      }),
    });

    // Apply appearance settings immediately
    applyColorScheme(settings.colorScheme);
    applyCompactMode(settings.compactMode);

    store.showSaved();
  } catch (error) {
    console.error('Error saving settings:', error);
    store.showError();
  }
}

// Apply color scheme to document using CSS color-scheme property
function applyColorScheme(scheme) {
  const root = document.documentElement;
  if (scheme === 'light') {
    root.style.setProperty('color-scheme', 'light only');
  } else if (scheme === 'dark') {
    root.style.setProperty('color-scheme', 'dark only');
  } else {
    // Auto - respect OS setting
    root.style.setProperty('color-scheme', 'light dark');
  }
}

// Apply compact mode to document
function applyCompactMode(compact) {
  const root = document.documentElement;
  if (compact) {
    root.setAttribute('data-compact-mode', 'true');
  } else {
    root.removeAttribute('data-compact-mode');
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
  <VerticalTabs
    bind:activeTab={activeSubTab}
    tabs={[
      { id: 'appearance', label: 'Appearance', content: appearanceContent },
      { id: 'security', label: 'Security', content: securityContent },
      { id: 'features', label: 'Features', content: featuresContent },
      { id: 'performance', label: 'Performance', content: performanceContent },
    ]}
  />

</div>

{#snippet appearanceContent()}
  <div class="wpea-stack">
    <Card>
      {#snippet children()}
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
        <Switch
          size="sm"
          bind:checked={settings.compactMode}
          onchange={saveSettings}
          label="Compact Mode"
        />
        <p class="wpea-help">Reduces font sizes, spacing, and padding throughout the interface, especially in tables. Useful for viewing more data on screen.</p>
      </div>
      {/snippet}
    </Card>
  </div>
{/snippet}

{#snippet securityContent()}
  <div class="wpea-stack">
    <Card>
      {#snippet children()}
      <h3 class="wpea-heading wpea-heading--sm">Security Settings</h3>

      <div class="wpea-stack wpea-stack--sm">
        <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
          <span>Allow assigning dangerous capabilities to roles</span>
          <Switch
            bind:checked={settings.allowCoreCapAssignment}
            onchange={saveSettings}
          />
        </div>

        <Alert variant="warning">
          {#snippet children()}
          <p><strong>Security Warning:</strong> Enabling this option allows you to assign dangerous WordPress capabilities to roles. This includes capabilities that can lead to code execution, privilege escalation, or complete site takeover:</p>
          <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
            <li><code>unfiltered_html</code>, <code>unfiltered_upload</code> - Can execute arbitrary code</li>
            <li><code>edit_plugins</code>, <code>edit_themes</code> - Can modify site code</li>
            <li><code>manage_options</code>, <code>promote_users</code> - Can elevate privileges</li>
            <li><code>install_plugins</code>, <code>update_core</code> - Can install malicious code</li>
          </ul>
          <p style="margin-top: var(--wpea-space--xs);"><strong>Recommended:</strong> Only enable this if you absolutely need to assign these capabilities and fully understand the security implications. These capabilities should normally only be available to the Administrator role.</p>
          {/snippet}
        </Alert>

        {#if settings.allowCoreCapAssignment}
          <Alert variant="danger">
            {#snippet children()}
            <p><strong>DANGER: Dangerous Capability Protection Disabled</strong></p>
            <p>The security protection preventing dangerous capabilities from being assigned to roles is now disabled. You can assign any capability to any role, including those that can compromise your entire site.</p>
            <p style="margin-top: var(--wpea-space--xs);"><strong>Use with extreme caution!</strong></p>
            {/snippet}
          </Alert>
        {/if}
      </div>

      <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md); padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
        <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
          <span>Allow deletion of external roles and capabilities</span>
          <Switch
            bind:checked={settings.allowExternalDeletion}
            onchange={saveSettings}
          />
        </div>

        <Alert variant="info">
          {#snippet children()}
          <p><strong>What are external roles and capabilities?</strong></p>
          <p>External roles and capabilities are created by other plugins or themes (not by WordPress core or this plugin).</p>
          <p style="margin-top: var(--wpea-space--xs);"><strong>Default behavior:</strong> External roles and capabilities are read-only and cannot be deleted. This prevents accidental removal of functionality added by other plugins.</p>
          <p style="margin-top: var(--wpea-space--xs);"><strong>When enabled:</strong> You can delete external roles and capabilities. Use this when cleaning up after uninstalled plugins or removing unwanted roles/capabilities from your system.</p>
          {/snippet}
        </Alert>

        {#if settings.allowExternalDeletion}
          <Alert variant="warning">
            {#snippet children()}
            <p><strong>External Deletion Enabled</strong></p>
            <p>You can now delete roles and capabilities created by other plugins. Be careful not to remove functionality that other plugins depend on. Make sure you know what you're deleting before proceeding.</p>
            {/snippet}
          </Alert>
        {/if}
      </div>
      {/snippet}
    </Card>
  </div>
{/snippet}

{#snippet featuresContent()}
  <div class="wpea-stack">
    <Card>
      {#snippet children()}
      <h3 class="wpea-heading wpea-heading--sm">Content Restrictions</h3>
      <p class="wpea-help">Enable the Content Restrictions metabox for post types. See <strong>Instructions</strong> tab for details.</p>

      <div class="wpea-stack wpea-stack--xs" style="margin-top: var(--wpea-space--sm);">
        {#each postTypes as postType}
          <Switch
            size="sm"
            checked={isPostTypeEnabled(postType.name)}
            onchange={() => togglePostTypeRestriction(postType.name)}
            label={postType.label}
          />
        {/each}

        {#if postTypes.length === 0}
          <p class="wpea-text-muted">Loading post types...</p>
        {/if}
      </div>
      {/snippet}
    </Card>

    <!-- Block Capability Conditions -->
    <Card>
      {#snippet children()}
      <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
        <h3 class="wpea-heading wpea-heading--sm" style="margin: 0;">Block Editor (Gutenberg) Integration</h3>
        <Switch
          bind:checked={settings.enableBlockConditions}
          onchange={saveSettings}
        />
      </div>
      <p class="wpea-help" style="margin-top: var(--wpea-space--xs);">Add capability conditions to Gutenberg blocks. See <strong>Instructions</strong> tab for details.</p>
      {/snippet}
    </Card>

    <!-- Elementor Integration -->
    <Card>
      {#snippet children()}
      <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
        <h3 class="wpea-heading wpea-heading--sm" style="margin: 0;">Elementor Integration</h3>
        {#if pluginStatus.elementorActive}
          <Switch
            bind:checked={settings.enableElementorConditions}
            onchange={saveSettings}
          />
        {:else}
          <Badge variant="muted">Not Installed</Badge>
        {/if}
      </div>
      {#if pluginStatus.elementorActive}
        <p class="wpea-help" style="margin-top: var(--wpea-space--xs);">Add capability conditions to Elementor elements. See <strong>Instructions</strong> tab for details.</p>
        <Alert variant="warning" style="margin-top: var(--wpea-space--sm);">
          {#snippet children()}
          <p><strong>Editor V4 Support (Beta)</strong></p>
          <p>Supports both <strong>Classic Editor (V3)</strong> and <strong>Editor V4 (Alpha)</strong>. V4 support is experimental.</p>
          {/snippet}
        </Alert>
      {:else}
        <p class="wpea-help wpea-help--muted" style="margin-top: var(--wpea-space--xs);">Elementor is not active. Install and activate Elementor to use this feature.</p>
      {/if}
      {/snippet}
    </Card>

    <!-- Bricks Builder Integration -->
    <Card>
      {#snippet children()}
      <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
        <h3 class="wpea-heading wpea-heading--sm" style="margin: 0;">Bricks Builder Integration</h3>
        {#if pluginStatus.bricksActive}
          <Switch
            bind:checked={settings.enableBricksConditions}
            onchange={saveSettings}
          />
        {:else}
          <Badge variant="muted">Not Installed</Badge>
        {/if}
      </div>
      {#if pluginStatus.bricksActive}
        <p class="wpea-help" style="margin-top: var(--wpea-space--xs);">Add capability conditions to Bricks Builder elements. See <strong>Instructions</strong> tab for details.</p>
      {:else}
        <p class="wpea-help wpea-help--muted" style="margin-top: var(--wpea-space--xs);">Bricks Builder is not active. Install and activate Bricks to use this feature.</p>
      {/if}
      {/snippet}
    </Card>
  </div>
{/snippet}

{#snippet performanceContent()}
  <div class="wpea-stack">
    <Card>
      {#snippet children()}
      <h3 class="wpea-heading wpea-heading--sm">Performance Settings</h3>

      <div class="wpea-field">
        <label for="autosave-debounce" class="wpea-label">Autosave Delay (milliseconds):</label>
        <Input
          type="number"
          id="autosave-debounce"
          bind:value={settings.autosaveDebounce}
          min={100}
          max={5000}
          step={100}
          onchange={saveSettings}
          style="max-width: 300px;"
        />
        <p class="wpea-help">Delay before automatically saving changes (100-5000ms). Higher values reduce server load but delay saves.</p>
      </div>

      <div class="wpea-field">
        <label for="log-retention" class="wpea-label">Log Retention (number of entries):</label>
        <Input
          type="number"
          id="log-retention"
          bind:value={settings.logRetention}
          min={100}
          max={10000}
          step={100}
          onchange={saveSettings}
          style="max-width: 300px;"
        />
        <p class="wpea-help">Maximum number of activity log entries to retain (100-10000). Oldest entries are automatically removed when this limit is reached.</p>
      </div>

      <div class="wpea-field">
        <label for="revision-retention" class="wpea-label">Revision Retention (number of entries):</label>
        <Input
          type="number"
          id="revision-retention"
          bind:value={settings.revisionRetention}
          min={50}
          max={1000}
          step={10}
          onchange={saveSettings}
          style="max-width: 300px;"
        />
        <p class="wpea-help">Maximum number of revisions to retain (50-1000). Oldest revisions are automatically removed when this limit is reached. Revisions allow you to restore previous states of roles and capabilities.</p>
      </div>
      {/snippet}
    </Card>
  </div>
{/snippet}
