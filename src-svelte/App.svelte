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
import SettingsTab from './components/tabs/SettingsTab.svelte';
import LogsTab from './components/tabs/LogsTab.svelte';

// Props
let { wpData = {} } = $props();

// Create app store
const store = createAppStore(wpData);

// Color scheme state
let colorScheme = $state('auto');

// Initialize on mount
onMount(async () => {
  store.init();

  // Fetch and apply color scheme
  try {
    const response = await store.apiRequest('/settings');
    if (response.settings?.color_scheme) {
      colorScheme = response.settings.color_scheme;
      applyColorScheme(colorScheme);
    }
  } catch (error) {
    console.error('Error fetching color scheme:', error);
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

// Watch for color scheme changes from settings
$effect(() => {
  // This will be triggered when settings are saved
  const checkColorScheme = async () => {
    try {
      const response = await store.apiRequest('/settings');
      if (response.settings?.color_scheme && response.settings.color_scheme !== colorScheme) {
        colorScheme = response.settings.color_scheme;
        applyColorScheme(colorScheme);
      }
    } catch (error) {
      // Silently fail - settings might not be available yet
    }
  };

  // Check every 2 seconds when settings tab is active
  if (store.currentTab === 'settings') {
    const interval = setInterval(checkColorScheme, 2000);
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
  { id: 'logs', label: wpData.i18n?.logsTab || 'Logs', component: LogsTab },
];

// Get current tab component
$effect(() => {
  console.log('Current tab:', store.currentTab);
});
</script>

<div class="wpea-stack" style="max-width: 100%;">
  <!-- Tab Navigation -->
  <nav class="wpea-cluster wpea-cluster--sm" role="tablist" style="border-bottom: 1px solid var(--wpea-surface--border); padding-bottom: var(--wpea-space--sm); justify-content: space-between; align-items: center;">
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

    <!-- Status Indicator -->
    {#if store.status}
      <div style="
        padding: var(--wpea-space--xs) var(--wpea-space--sm);
        border-radius: var(--wpea-radius--sm);
        font-size: var(--wpea-text--sm);
        font-weight: 500;
        background: {store.status === 'saving' ? 'var(--wpea-color--neutral-l-9)' : store.status === 'saved' ? 'var(--wpea-color--success-l-9)' : 'var(--wpea-color--danger-l-9)'};
        color: {store.status === 'saving' ? 'var(--wpea-color--neutral)' : store.status === 'saved' ? 'var(--wpea-color--success)' : 'var(--wpea-color--danger)'};
      ">
        {#if store.status === 'saving'}
          <span>{wpData.i18n?.saving || 'Saving...'}</span>
        {:else if store.status === 'saved'}
          <span>{wpData.i18n?.saved || 'Saved'}</span>
        {:else if store.status === 'error'}
          <span>{wpData.i18n?.error || 'Error'}</span>
        {/if}
      </div>
    {/if}
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
