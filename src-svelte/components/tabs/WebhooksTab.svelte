<script>
/**
 * Webhooks Tab Component
 *
 * Manage outgoing and incoming webhooks for automation integration.
 *
 * @package WP_Easy\RoleManager
 */

import { Tabs } from '../../lib/index.ts';
import OutgoingList from '../webhooks/OutgoingList.svelte';
import IncomingDocs from '../webhooks/IncomingDocs.svelte';
import WebhookLogList from '../webhooks/WebhookLogList.svelte';

let { store } = $props();

let activeSubTab = $state('outgoing');
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

<div class="webhooks-tab">
  <div class="webhooks-tab__header">
    <p class="webhooks-tab__description">
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
    bind:activeTab={activeSubTab}
    variant="secondary"
  />
</div>

<style>
  .webhooks-tab {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .webhooks-tab__header {
    margin-bottom: var(--wpea-space--sm);
  }

  .webhooks-tab__description {
    margin: 0;
    color: var(--wpea-surface--text-muted);
    font-size: var(--wpea-text--sm);
    line-height: 1.5;
  }
</style>
