<script>
/**
 * Main App Component - WP Easy Role Manager
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { createAppStore } from './stores/app.svelte.js';

// Framework components
import { Tabs, Toast } from './lib/index.ts';

// Tab components
import RolesTab from './components/tabs/RolesTab.svelte';
import CapabilitiesTab from './components/tabs/CapabilitiesTab.svelte';
import UsersTab from './components/tabs/UsersTab.svelte';
import ImportExportTab from './components/tabs/ImportExportTab.svelte';
import RevisionsTab from './components/tabs/RevisionsTab.svelte';
import ToolsTab from './components/tabs/ToolsTab.svelte';
import SettingsTab from './components/tabs/SettingsTab.svelte';
import LogsTab from './components/tabs/LogsTab.svelte';
import WebhooksTab from './components/tabs/WebhooksTab.svelte';

// Props
let { wpData = {} } = $props();

// Create app store
const store = createAppStore(wpData);

// Color scheme and compact mode state
let colorScheme = $state('auto');
let compactMode = $state(false);

// Toast notifications
let toasts = $state([]);

// Active tab state (bound to Tabs component)
let activeTab = $state('roles');

// Sync activeTab with store
$effect(() => {
  store.currentTab = activeTab;
});

// Initialize on mount
onMount(async () => {
  store.init();

  // Fetch and apply settings
  try {
    const response = await store.apiRequest('/settings');
    if (response.settings) {
      if (response.settings.color_scheme) {
        colorScheme = response.settings.color_scheme;
        applyColorScheme(colorScheme);
      }
      if (response.settings.compact_mode !== undefined) {
        compactMode = response.settings.compact_mode;
        applyCompactMode(compactMode);
      }
    }
  } catch (error) {
    console.error('Error fetching settings:', error);
  }
});

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

// Watch for settings changes from settings tab
// Use a simpler approach - just check settings when switching to settings tab
function checkAndApplySettings() {
  store.apiRequest('/settings').then(response => {
    if (response.settings) {
      if (response.settings.color_scheme && response.settings.color_scheme !== colorScheme) {
        colorScheme = response.settings.color_scheme;
        applyColorScheme(colorScheme);
      }
      if (response.settings.compact_mode !== undefined && response.settings.compact_mode !== compactMode) {
        compactMode = response.settings.compact_mode;
        applyCompactMode(compactMode);
      }
    }
  }).catch(() => {
    // Silently fail
  });
}

// Handle tab change - refresh data
function handleTabChange(tabId) {
  console.log('Tab changed to', tabId, '- refreshing data');

  // Check for settings changes when leaving settings tab
  if (activeTab === 'settings' && tabId !== 'settings') {
    checkAndApplySettings();
  }

  // Refresh data based on the new tab
  switch (tabId) {
    case 'roles':
      store.fetchRoles();
      break;
    case 'capabilities':
      store.fetchCapabilityMatrix();
      break;
    case 'users':
      store.fetchUsers();
      break;
    case 'settings':
      store.fetchSettings();
      break;
    // Logs and Revisions tabs handle their own data fetching via onMount in their components
    default:
      break;
  }
}

// Show toast based on store status
let lastStatus = '';
$effect(() => {
  const currentStatus = store.status;
  // Only react if status actually changed
  if (currentStatus === lastStatus) return;
  lastStatus = currentStatus;

  // Use untracked update to avoid re-triggering
  if (currentStatus === 'saving') {
    toasts = [{ id: 'status-saving', message: wpData.i18n?.saving || 'Saving...', variant: 'neutral', duration: 0 }];
  } else if (currentStatus === 'saved') {
    toasts = [{ id: 'status-saved', message: wpData.i18n?.saved || 'Saved', variant: 'success', duration: 3000 }];
  } else if (currentStatus === 'error') {
    toasts = [{ id: 'status-error', message: wpData.i18n?.error || 'Error', variant: 'danger', duration: 5000 }];
  } else if (currentStatus === '') {
    toasts = [];
  }
});
</script>

<div class="wpea-stack" style="max-width: 100%;">
  <Tabs
    bind:activeTab
    onTabChange={handleTabChange}
    variant="primary"
    tabs={[
      { id: 'roles', label: wpData.i18n?.rolesTab || 'Roles', content: rolesContent },
      { id: 'capabilities', label: wpData.i18n?.capabilitiesTab || 'Capabilities', content: capabilitiesContent },
      { id: 'users', label: wpData.i18n?.usersTab || 'Users', content: usersContent },
      { id: 'import-export', label: wpData.i18n?.importExportTab || 'Import/Export', content: importExportContent },
      { id: 'settings', label: wpData.i18n?.settingsTab || 'Settings', content: settingsContent },
      { id: 'tools', label: wpData.i18n?.toolsTab || 'Tools', content: toolsContent },
      { id: 'revisions', label: wpData.i18n?.revisionsTab || 'Revisions', content: revisionsContent },
      { id: 'logs', label: wpData.i18n?.logsTab || 'Logs', content: logsContent },
      { id: 'webhooks', label: wpData.i18n?.webhooksTab || 'Webhooks', content: webhooksContent },
    ]}
  />
</div>

{#snippet rolesContent()}
  <RolesTab {store} />
{/snippet}

{#snippet capabilitiesContent()}
  <CapabilitiesTab {store} />
{/snippet}

{#snippet usersContent()}
  <UsersTab {store} />
{/snippet}

{#snippet importExportContent()}
  <ImportExportTab {store} />
{/snippet}

{#snippet settingsContent()}
  <SettingsTab {store} />
{/snippet}

{#snippet toolsContent()}
  <ToolsTab {store} />
{/snippet}

{#snippet revisionsContent()}
  <RevisionsTab {store} />
{/snippet}

{#snippet logsContent()}
  <LogsTab {store} />
{/snippet}

{#snippet webhooksContent()}
  <WebhooksTab {store} />
{/snippet}

<!-- Toast Notifications -->
<Toast bind:toasts position="top-right" />
