<script>
/**
 * Main App Component - WP Easy Role Manager
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { createAppStore } from './stores/app.svelte.js';

// Tab components
import RolesTab from './components/tabs/RolesTab.svelte';
import CapabilitiesTab from './components/tabs/CapabilitiesTab.svelte';
import UsersTab from './components/tabs/UsersTab.svelte';
import ImportExportTab from './components/tabs/ImportExportTab.svelte';
import RevisionsTab from './components/tabs/RevisionsTab.svelte';
import ToolsTab from './components/tabs/ToolsTab.svelte';
import SettingsTab from './components/tabs/SettingsTab.svelte';
import LogsTab from './components/tabs/LogsTab.svelte';

// Props
let { wpData = {} } = $props();

// Create app store
const store = createAppStore(wpData);

// Color scheme and compact mode state
let colorScheme = $state('auto');
let compactMode = $state(false);

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

// Apply color scheme to document
function applyColorScheme(scheme) {
  const root = document.documentElement;

  if (scheme === 'light') {
    root.setAttribute('data-color-scheme', 'light');
  } else if (scheme === 'dark') {
    root.setAttribute('data-color-scheme', 'dark');
  } else {
    // Auto - respect OS setting
    root.removeAttribute('data-color-scheme');
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

// Watch for settings changes from settings tab
$effect(() => {
  // This will be triggered when settings are saved
  const checkSettings = async () => {
    try {
      const response = await store.apiRequest('/settings');
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
    } catch (error) {
      // Silently fail - settings might not be available yet
    }
  };

  // Check every 2 seconds when settings tab is active
  if (store.currentTab === 'settings') {
    const interval = setInterval(checkSettings, 2000);
    return () => clearInterval(interval);
  }
});

// Tab configuration
const tabs = [
  { id: 'roles', label: wpData.i18n?.rolesTab || 'Roles', component: RolesTab },
  { id: 'capabilities', label: wpData.i18n?.capabilitiesTab || 'Capabilities', component: CapabilitiesTab },
  { id: 'users', label: wpData.i18n?.usersTab || 'Users', component: UsersTab },
  { id: 'import-export', label: wpData.i18n?.importExportTab || 'Import/Export', component: ImportExportTab },
  { id: 'settings', label: wpData.i18n?.settingsTab || 'Settings', component: SettingsTab },
  { id: 'tools', label: wpData.i18n?.toolsTab || 'Tools', component: ToolsTab },
  { id: 'revisions', label: wpData.i18n?.revisionsTab || 'Revisions', component: RevisionsTab },
  { id: 'logs', label: wpData.i18n?.logsTab || 'Logs', component: LogsTab },
];

// Refresh data when switching tabs
let previousTab = $state('');
$effect(() => {
  const currentTabId = store.currentTab;

  // Skip refresh on initial load
  if (previousTab === '') {
    previousTab = currentTabId;
    return;
  }

  // Only refresh if tab actually changed
  if (previousTab !== currentTabId) {
    console.log('Tab changed from', previousTab, 'to', currentTabId, '- refreshing data');

    // Refresh data based on the new tab
    switch (currentTabId) {
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
      // Logs and Revisions tabs handle their own data fetching via $effect in their components
      default:
        // For other tabs, refresh all core data
        store.fetchRoles();
        store.fetchCapabilityMatrix();
        store.fetchUsers();
        break;
    }

    previousTab = currentTabId;
  }
});
</script>

<div class="wpea-stack" style="max-width: 100%;">
  <!-- Tab Navigation -->
  <nav class="wpea-cluster wpea-cluster--sm" role="tablist" style="border-bottom: 1px solid var(--wpea-surface--border); padding-bottom: var(--wpea-space--sm);">
    <div class="wpea-cluster wpea-cluster--xs" style="flex-wrap: wrap;">
      {#each tabs as tab}
        <button
          type="button"
          class="wpea-btn wpea-btn--sm"
          class:wpea-btn--primary={store.currentTab === tab.id}
          onclick={() => store.currentTab = tab.id}
          role="tab"
          aria-selected={store.currentTab === tab.id}
        >
          {tab.label}
        </button>
      {/each}
    </div>
  </nav>

  <!-- Tab Content -->
  <div style="padding-top: var(--wpea-space--md);">
    {#each tabs as tab}
      {#if store.currentTab === tab.id}
        <div role="tabpanel">
          <svelte:component this={tab.component} {store} />
        </div>
      {/if}
    {/each}
  </div>
</div>

<!-- Toast Notification -->
{#if store.status}
  <div class="toast-notification" class:toast-saving={store.status === 'saving'} class:toast-saved={store.status === 'saved'} class:toast-error={store.status === 'error'}>
    {#if store.status === 'saving'}
      <span>{wpData.i18n?.saving || 'Saving...'}</span>
    {:else if store.status === 'saved'}
      <span>{wpData.i18n?.saved || 'Saved'}</span>
    {:else if store.status === 'error'}
      <span>{wpData.i18n?.error || 'Error'}</span>
    {/if}
  </div>
{/if}

<style>
.toast-notification {
  position: fixed;
  top: 32px;
  right: 20px;
  z-index: 100001;
  padding: var(--wpea-space--sm) var(--wpea-space--md);
  border-radius: var(--wpea-radius--md);
  font-size: var(--wpea-text--sm);
  font-weight: 500;
  box-shadow: var(--wpea-shadow--l);
  animation: slideIn 0.2s ease-out;
  pointer-events: none;
}

.toast-saving {
  background: var(--wpea-color--neutral-l-9);
  color: var(--wpea-color--neutral);
}

.toast-saved {
  background: var(--wpea-color--success-l-9);
  color: var(--wpea-color--success);
}

.toast-error {
  background: var(--wpea-color--danger-l-9);
  color: var(--wpea-color--danger);
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
