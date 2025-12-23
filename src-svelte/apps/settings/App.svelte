<script lang="ts">
/**
 * Settings App Component - WP Easy Role Manager
 *
 * Contains: General Settings, Tools, Import/Export tabs
 *
 * @package WP_Easy\RoleManager
 */

// Framework components
import { Tabs, Toast } from '../../lib/index.ts';

// Tab components
import SettingsTab from '../../components/tabs/SettingsTab.svelte';
import ToolsTab from '../../components/tabs/ToolsTab.svelte';
import ImportExportTab from '../../components/tabs/ImportExportTab.svelte';

// Store
import { createAppStore } from '../../stores/app.svelte.js';

// Get shared module
const { WPE_RM } = window;
const i18n = WPE_RM?.i18n || {};

// Create app store
const store = createAppStore({
  restUrl: WPE_RM?.api?.getBaseUrl() || '',
  nonce: WPE_RM?.api?.getNonce() || '',
  i18n,
});

// Toast notifications
let toasts = $state<Array<{ id: string; message: string; variant: string; duration: number }>>([]);

// Active tab state
let activeTab = $state('general');

// Initialize on mount
$effect(() => {
  store.fetchSettings();
  store.fetchRoles();
});

// Show toast based on store status
let lastStatus = '';
$effect(() => {
  const currentStatus = store.status;
  if (currentStatus === lastStatus) return;
  lastStatus = currentStatus;

  if (currentStatus === 'saving') {
    toasts = [{ id: 'status-saving', message: i18n.saving || 'Saving...', variant: 'neutral', duration: 0 }];
  } else if (currentStatus === 'saved') {
    toasts = [{ id: 'status-saved', message: i18n.saved || 'Saved', variant: 'success', duration: 3000 }];
  } else if (currentStatus === 'error') {
    toasts = [{ id: 'status-error', message: i18n.error || 'Error', variant: 'danger', duration: 5000 }];
  } else if (currentStatus === '') {
    toasts = [];
  }
});
</script>

{#snippet generalContent()}
  <SettingsTab {store} />
{/snippet}

{#snippet toolsContent()}
  <ToolsTab {store} />
{/snippet}

{#snippet importExportContent()}
  <ImportExportTab {store} />
{/snippet}

<div class="wpea-stack" style="max-width: 100%;">
  <Tabs
    bind:activeTab
    variant="primary"
    tabs={[
      { id: 'general', label: 'General', content: generalContent },
      { id: 'tools', label: 'Tools', content: toolsContent },
      { id: 'import-export', label: 'Import/Export', content: importExportContent },
    ]}
  />
</div>

<!-- Toast Notifications -->
<Toast bind:toasts position="top-right" />
