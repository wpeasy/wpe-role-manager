<script lang="ts">
/**
 * Settings Tab Component
 *
 * Configure plugin settings
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { Card, Button, Alert, Input, Switch, Badge, VerticalTabs, FrameworkSettings, type FrameworkDisplaySettings, defaultFrameworkSettings } from '../../lib/index.ts';

let { store } = $props();

let activeSubTab = $state('security');
let postTypes = $state([]);

// Get shared displaySettings module for live preview and localStorage sync
const sharedDisplaySettings = window.WPE_RM?.displaySettings;

// Framework display settings (for FrameworkSettings component)
// Initialize from shared module which reads from localStorage/server
let frameworkSettings = $state<FrameworkDisplaySettings>(
  sharedDisplaySettings?.get() ?? { ...defaultFrameworkSettings }
);

let settings = $state({
  allowCoreCapAssignment: false,
  allowExternalDeletion: false,
  autosaveDebounce: 500,
  logRetention: 500,
  revisionRetention: 300,
  webhookRateLimit: 100, // Incoming webhook rate limit per minute
  colorScheme: 'auto', // 'light', 'dark', 'auto'
  compactMode: false,
  restrictionsEnabledPostTypes: ['page'], // Array of post type slugs
  enableBlockConditions: true, // Enable block visibility conditions
  enableElementorConditions: true, // Enable Elementor visibility conditions
  enableBricksConditions: true, // Enable Bricks Builder conditions
  // Experimental features
  enableWebhooks: false, // Enable webhooks (experimental)
  // Clean uninstall settings
  enableCleanUninstall: false,
  uninstallRemoveRoles: false,
  uninstallRemoveCapabilities: false,
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
      settings.webhookRateLimit = response.settings.webhook_rate_limit || 100;
      settings.colorScheme = response.settings.color_scheme || 'auto';
      settings.compactMode = response.settings.compact_mode || false;
      // Feature toggles - default to true if not set
      settings.enableBlockConditions = response.settings.enable_block_conditions ?? true;
      settings.enableElementorConditions = response.settings.enable_elementor_conditions ?? true;
      settings.enableBricksConditions = response.settings.enable_bricks_conditions ?? true;
      // Experimental features
      settings.enableWebhooks = response.settings.enable_webhooks || false;
      // Clean uninstall settings
      settings.enableCleanUninstall = response.settings.enable_clean_uninstall || false;
      settings.uninstallRemoveRoles = response.settings.uninstall_remove_roles || false;
      settings.uninstallRemoveCapabilities = response.settings.uninstall_remove_capabilities || false;

      // Handle both old and new format
      if (response.settings.restrictions_enabled_post_types) {
        settings.restrictionsEnabledPostTypes = response.settings.restrictions_enabled_post_types;
      } else if (response.settings.enable_restrictions_metabox) {
        // Migrate old setting - if it was enabled, enable for all post types
        settings.restrictionsEnabledPostTypes = ['page', 'post'];
      } else {
        settings.restrictionsEnabledPostTypes = ['page']; // Default to page only
      }

      // Load framework display settings from shared module (has localStorage)
      // The shared module already synced with server settings on init
      if (sharedDisplaySettings) {
        frameworkSettings = sharedDisplaySettings.get();
      } else if (response.settings.framework_settings) {
        frameworkSettings = { ...defaultFrameworkSettings, ...response.settings.framework_settings };
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
        webhook_rate_limit: settings.webhookRateLimit,
        color_scheme: settings.colorScheme,
        compact_mode: settings.compactMode,
        restrictions_enabled_post_types: settings.restrictionsEnabledPostTypes,
        enable_block_conditions: settings.enableBlockConditions,
        enable_elementor_conditions: settings.enableElementorConditions,
        enable_bricks_conditions: settings.enableBricksConditions,
        enable_webhooks: settings.enableWebhooks,
        enable_clean_uninstall: settings.enableCleanUninstall,
        uninstall_remove_roles: settings.uninstallRemoveRoles,
        uninstall_remove_capabilities: settings.uninstallRemoveCapabilities,
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

// Apply compact mode to document (uses body class per WPEA framework)
function applyCompactMode(compact) {
  if (compact) {
    document.body.classList.add('wpea-compact');
  } else {
    document.body.classList.remove('wpea-compact');
  }
}

// Handle FrameworkSettings changes
async function handleFrameworkSettingsChange(newSettings: FrameworkDisplaySettings) {
  // Use shared module if available - it handles localStorage, DOM, and server sync
  if (sharedDisplaySettings) {
    store.showSaving();
    // The set() method saves to localStorage, applies to DOM, and syncs to server
    sharedDisplaySettings.set(newSettings);
    store.showSaved();
  } else {
    // Fallback: direct API call
    try {
      store.showSaving();
      await store.apiRequest('/settings', {
        method: 'POST',
        body: JSON.stringify({
          framework_settings: newSettings,
        }),
      });
      store.showSaved();
    } catch (error) {
      console.error('Error saving framework settings:', error);
      store.showError();
    }
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

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Settings</h2>
    <p class="wpea-text-muted">Configure plugin settings and preferences.</p>
  </div>

  <!-- Sub-Tab Navigation -->
  <VerticalTabs
    bind:activeTab={activeSubTab}
    tabs={[
      { id: 'security', label: 'Security', content: securityContent },
      { id: 'features', label: 'Features', content: featuresContent },
      { id: 'appearance', label: 'Appearance', content: appearanceContent },
      { id: 'thresholds', label: 'Thresholds', content: thresholdsContent },
    ]}
  />

</div>

{#snippet appearanceContent()}
  <FrameworkSettings
    bind:settings={frameworkSettings}
    onchange={handleFrameworkSettingsChange}
  />
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

      <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md); padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
        <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
          <span>Enable clean uninstall</span>
          <Switch
            bind:checked={settings.enableCleanUninstall}
            onchange={saveSettings}
          />
        </div>

        <Alert variant="warning">
          {#snippet children()}
          <p><strong>What is clean uninstall?</strong></p>
          <p>When enabled, <strong>deleting</strong> this plugin from the Plugins page will permanently remove all plugin data including:</p>
          <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
            <li>Activity logs and revision history</li>
            <li>Webhook configurations and activity logs</li>
            <li>Plugin settings and preferences</li>
            <li>Disabled roles/capabilities tracking</li>
          </ul>
          <p style="margin-top: var(--wpea-space--xs);"><strong>Note:</strong> Deactivating the plugin does NOT delete any data. Only deleting the plugin triggers cleanup.</p>
          {/snippet}
        </Alert>

        {#if settings.enableCleanUninstall}
          <Alert variant="danger">
            {#snippet children()}
            <p><strong>CLEAN UNINSTALL ENABLED</strong></p>
            <p>Deleting this plugin will permanently erase all plugin data. This action cannot be undone.</p>
            {/snippet}
          </Alert>

          <div class="wpea-stack wpea-stack--xs" style="margin-top: var(--wpea-space--sm); padding: var(--wpea-space--md); background: var(--wpea-surface--muted); border-radius: var(--wpea-radius--md);">
            <p style="margin: 0 0 var(--wpea-space--sm) 0; font-weight: 500;">Additional cleanup options:</p>
            <Switch
              size="sm"
              bind:checked={settings.uninstallRemoveRoles}
              onchange={saveSettings}
              label="Also remove roles created by this plugin"
            />
            <Switch
              size="sm"
              bind:checked={settings.uninstallRemoveCapabilities}
              onchange={saveSettings}
              label="Also remove capabilities created by this plugin"
            />
            {#if settings.uninstallRemoveRoles || settings.uninstallRemoveCapabilities}
              <Alert variant="danger" style="margin-top: var(--wpea-space--sm);">
                {#snippet children()}
                <p><strong>Warning:</strong> Removing roles or capabilities may affect users who have those roles assigned or plugins that depend on those capabilities.</p>
                {/snippet}
              </Alert>
            {/if}
          </div>
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

    <!-- Experimental Features -->
    <Card>
      {#snippet children()}
      <h3 class="wpea-heading wpea-heading--sm">Experimental</h3>
      <p class="wpea-help">These features are experimental and may change in future versions.</p>

      <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
        <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
          <div>
            <span style="font-weight: 500;">Webhooks</span>
            <Badge variant="warning" size="sm" style="margin-left: var(--wpea-space--xs);">Experimental</Badge>
          </div>
          <Switch
            bind:checked={settings.enableWebhooks}
            onchange={saveSettings}
          />
        </div>
        <p class="wpea-help" style="margin-top: 0;">
          Enable outgoing and incoming webhooks for integration with automation platforms like N8N, Zapier, and Make.
          When enabled, a <strong>Webhooks</strong> submenu will appear.
        </p>

        {#if settings.enableWebhooks}
          <Alert variant="info" style="margin-top: var(--wpea-space--sm);">
            {#snippet children()}
            <p><strong>Webhooks Enabled</strong></p>
            <p>The Webhooks submenu is now available. Refresh the page to see the menu item.</p>
            {/snippet}
          </Alert>
        {/if}
      </div>
      {/snippet}
    </Card>
  </div>
{/snippet}

{#snippet thresholdsContent()}
  <div class="wpea-stack">
    <Card>
      {#snippet children()}
      <h3 class="wpea-heading wpea-heading--sm">Thresholds</h3>

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

      {#if settings.enableWebhooks}
        <div class="wpea-field">
          <label for="webhook-rate-limit" class="wpea-label">Incoming Webhook Rate Limit (requests per minute):</label>
          <Input
            type="number"
            id="webhook-rate-limit"
            bind:value={settings.webhookRateLimit}
            min={10}
            max={1000}
            step={10}
            onchange={saveSettings}
            style="max-width: 300px;"
          />
          <p class="wpea-help">Maximum number of incoming webhook requests allowed per minute per IP address (10-1000). Requests exceeding this limit receive a 429 Too Many Requests response.</p>
        </div>
      {/if}
      {/snippet}
    </Card>
  </div>
{/snippet}
