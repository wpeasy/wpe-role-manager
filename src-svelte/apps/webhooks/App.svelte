<script lang="ts">
/**
 * Webhooks App Component - WP Easy Role Manager
 *
 * Standalone webhooks management page.
 *
 * @package WP_Easy\RoleManager
 */

// Framework components
import { Tabs, Toast } from '../../lib/index.ts';

// Webhook components
import OutgoingList from '../../components/webhooks/OutgoingList.svelte';
import IncomingDocs from '../../components/webhooks/IncomingDocs.svelte';
import WebhookLogList from '../../components/webhooks/WebhookLogList.svelte';

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
let activeTab = $state('outgoing');

// Initialize on mount
$effect(() => {
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

{#snippet outgoingContent()}
  <OutgoingList {store} />
{/snippet}

{#snippet incomingContent()}
  <IncomingDocs {store} />
{/snippet}

{#snippet logContent()}
  <WebhookLogList {store} />
{/snippet}

<div class="webhooks-app">
  <div class="webhooks-app__header">
    <p class="webhooks-app__description">
      Configure webhooks to integrate with automation platforms like N8N, Zapier, and Make.
      Outgoing webhooks fire when events occur. Incoming webhooks allow external systems to trigger actions.
    </p>
  </div>

  <Tabs
    tabs={[
      { id: 'outgoing', label: 'Outgoing', content: outgoingContent },
      { id: 'incoming', label: 'Incoming', content: incomingContent },
      { id: 'log', label: 'Activity Log', content: logContent },
    ]}
    bind:activeTab
    variant="primary"
  />
</div>

<!-- Toast Notifications -->
<Toast bind:toasts position="top-right" />

<style>
  .webhooks-app {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
    max-width: 100%;
  }

  .webhooks-app__header {
    margin-bottom: var(--wpea-space--sm);
  }

  .webhooks-app__description {
    margin: 0;
    color: var(--wpea-surface--text-muted);
    font-size: var(--wpea-text--sm);
    line-height: 1.5;
  }
</style>
