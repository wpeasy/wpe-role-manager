<script lang="ts">
/**
 * Main Admin App Component - WP Easy Role Manager
 *
 * Contains: Roles, Capabilities, Users tabs
 *
 * @package WP_Easy\RoleManager
 */

// Framework components (from existing lib/)
import { Tabs, Toast } from '../../lib/index.ts';

// Tab components (from existing components/tabs/)
import RolesTab from '../../components/tabs/RolesTab.svelte';
import CapabilitiesTab from '../../components/tabs/CapabilitiesTab.svelte';
import UsersTab from '../../components/tabs/UsersTab.svelte';

// Store
import { createAppStore } from '../../stores/app.svelte.js';

// Get shared module
const { WPE_RM } = window;
const i18n = WPE_RM?.i18n || {};

// Create app store (pass data from shared module)
const store = createAppStore({
  restUrl: WPE_RM?.api?.getBaseUrl() || '',
  nonce: WPE_RM?.api?.getNonce() || '',
  i18n,
});

// Toast notifications
let toasts = $state<Array<{ id: string; message: string; variant: string; duration: number }>>([]);

// Active tab state
let activeTab = $state('roles');

// Sync activeTab with store
$effect(() => {
  store.currentTab = activeTab;
});

// Initialize on mount
$effect(() => {
  // Only run once on mount
  store.init();

  // Subscribe to status events from shared module
  const statusSavingSub = WPE_RM?.on('status:saving', () => {
    toasts = [{ id: 'status-saving', message: i18n.saving || 'Saving...', variant: 'neutral', duration: 0 }];
  });

  const statusSavedSub = WPE_RM?.on('status:saved', () => {
    toasts = [{ id: 'status-saved', message: i18n.saved || 'Saved', variant: 'success', duration: 3000 }];
  });

  const statusErrorSub = WPE_RM?.on('status:error', (detail) => {
    toasts = [{ id: 'status-error', message: detail?.message || i18n.error || 'Error', variant: 'danger', duration: 5000 }];
  });

  // Cleanup subscriptions
  return () => {
    statusSavingSub?.unsubscribe();
    statusSavedSub?.unsubscribe();
    statusErrorSub?.unsubscribe();
  };
});

// Show toast based on store status (backwards compatibility)
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

// Handle tab change - refresh data
function handleTabChange(tabId: string) {
  console.log('[WPE_RM] Tab changed to', tabId);

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
    default:
      break;
  }
}
</script>

{#snippet rolesContent()}
  <RolesTab {store} />
{/snippet}

{#snippet capabilitiesContent()}
  <CapabilitiesTab {store} />
{/snippet}

{#snippet usersContent()}
  <UsersTab {store} />
{/snippet}

<div class="wpea-stack" style="max-width: 100%;">
  <Tabs
    bind:activeTab
    onTabChange={handleTabChange}
    variant="primary"
    tabs={[
      { id: 'roles', label: i18n.rolesTab || 'Roles', content: rolesContent },
      { id: 'capabilities', label: i18n.capabilitiesTab || 'Capabilities', content: capabilitiesContent },
      { id: 'users', label: i18n.usersTab || 'Users', content: usersContent },
    ]}
  />
</div>

<!-- Toast Notifications -->
<Toast bind:toasts position="top-right" />
