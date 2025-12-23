<script>
/**
 * Outgoing Webhooks List Component
 *
 * Lists and manages outgoing webhook configurations.
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { Card, Button, Alert, Badge, Switch, Input, Modal } from '../../lib/index.ts';
import OutgoingForm from './OutgoingForm.svelte';

let { store } = $props();

// State
let webhooks = $state([]);
let events = $state({});
let loading = $state(true);
let showForm = $state(false);
let editingWebhook = $state(null);
let testingId = $state(null);
let testResult = $state(null);

// Fetch webhooks
async function fetchWebhooks() {
  try {
    loading = true;
    const response = await store.apiRequest('/webhooks');
    webhooks = response.webhooks || [];
  } catch (error) {
    console.error('Error fetching webhooks:', error);
    store.showError();
  } finally {
    loading = false;
  }
}

// Fetch available events
async function fetchEvents() {
  try {
    const response = await store.apiRequest('/webhooks/events');
    events = response.events || {};
  } catch (error) {
    console.error('Error fetching events:', error);
  }
}

// Toggle webhook enabled status
async function toggleEnabled(webhook) {
  try {
    store.showSaving();
    await store.apiRequest(`/webhooks/${webhook.id}`, {
      method: 'PATCH',
      body: JSON.stringify({ enabled: !webhook.enabled }),
    });
    await fetchWebhooks();
    store.showSaved();
  } catch (error) {
    console.error('Error toggling webhook:', error);
    store.showError();
  }
}

// Delete webhook
async function deleteWebhook(webhook) {
  if (!confirm(`Are you sure you want to delete the webhook "${webhook.name}"?`)) {
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest(`/webhooks/${webhook.id}`, {
      method: 'DELETE',
    });
    await fetchWebhooks();
    store.showSaved();
  } catch (error) {
    console.error('Error deleting webhook:', error);
    store.showError();
  }
}

// Test webhook
async function testWebhook(webhook) {
  testingId = webhook.id;
  testResult = null;

  try {
    const response = await store.apiRequest(`/webhooks/${webhook.id}/test`, {
      method: 'POST',
    });
    testResult = {
      webhookId: webhook.id,
      success: response.success,
      message: response.message,
      code: response.response_code,
      duration: response.duration_ms,
    };
  } catch (error) {
    testResult = {
      webhookId: webhook.id,
      success: false,
      message: error.message || 'Test failed',
      code: 0,
      duration: 0,
    };
  } finally {
    testingId = null;
  }
}

// Open form for creating
function openCreateForm() {
  editingWebhook = null;
  showForm = true;
}

// Open form for editing
function openEditForm(webhook) {
  editingWebhook = webhook;
  showForm = true;
}

// Handle form save
async function handleFormSave() {
  showForm = false;
  editingWebhook = null;
  await fetchWebhooks();
}

// Handle form cancel
function handleFormCancel() {
  showForm = false;
  editingWebhook = null;
}

// Format date
function formatDate(dateStr) {
  if (!dateStr) return 'Never';
  return new Date(dateStr).toLocaleString();
}

// Initialize
onMount(() => {
  fetchWebhooks();
  fetchEvents();
});
</script>

<div class="outgoing-list">
  <div class="outgoing-list__header">
    <h3>Outgoing Webhooks</h3>
    <Button variant="primary" onclick={openCreateForm}>
      Add Webhook
    </Button>
  </div>

  {#if loading}
    <div class="outgoing-list__loading">
      <p>Loading webhooks...</p>
    </div>
  {:else if webhooks.length === 0}
    <Card>
      <Alert variant="info">
        No webhooks configured. Click "Add Webhook" to create your first outgoing webhook.
      </Alert>
    </Card>
  {:else}
    <div class="outgoing-list__items">
      {#each webhooks as webhook (webhook.id)}
        <Card>
          <div class="webhook-item">
            <div class="webhook-item__header">
              <div class="webhook-item__title">
                <h4>{webhook.name}</h4>
                <Badge variant={webhook.enabled ? 'success' : 'secondary'}>
                  {webhook.enabled ? 'Enabled' : 'Disabled'}
                </Badge>
              </div>
              <div class="webhook-item__toggle">
                <Switch
                  checked={webhook.enabled}
                  onchange={() => toggleEnabled(webhook)}
                  label={webhook.enabled ? 'Enabled' : 'Disabled'}
                  labelPosition="left"
                />
              </div>
            </div>

            <div class="webhook-item__details">
              <div class="webhook-item__url">
                <span class="webhook-item__label">URL:</span>
                <code>{webhook.url}</code>
              </div>

              <div class="webhook-item__events">
                <span class="webhook-item__label">Events:</span>
                <div class="webhook-item__event-badges">
                  {#each webhook.events as event}
                    <Badge variant="secondary" size="sm">{events[event] || event}</Badge>
                  {/each}
                </div>
              </div>

              <div class="webhook-item__meta">
                <span>Method: <strong>{webhook.method}</strong></span>
                <span>Retries: <strong>{webhook.retries}</strong></span>
                <span>Last triggered: <strong>{formatDate(webhook.last_triggered)}</strong></span>
              </div>
            </div>

            {#if testResult && testResult.webhookId === webhook.id}
              <div class="webhook-item__test-result">
                <Alert variant={testResult.success ? 'success' : 'danger'}>
                  {testResult.message}
                  {#if testResult.code}
                    (HTTP {testResult.code}, {testResult.duration}ms)
                  {/if}
                </Alert>
              </div>
            {/if}

            <div class="webhook-item__actions">
              <Button
                variant="secondary"
                size="sm"
                onclick={() => testWebhook(webhook)}
                disabled={testingId === webhook.id}
              >
                {testingId === webhook.id ? 'Testing...' : 'Test'}
              </Button>
              <Button
                variant="secondary"
                size="sm"
                onclick={() => openEditForm(webhook)}
              >
                Edit
              </Button>
              <Button
                variant="danger"
                size="sm"
                onclick={() => deleteWebhook(webhook)}
              >
                Delete
              </Button>
            </div>
          </div>
        </Card>
      {/each}
    </div>
  {/if}
</div>

{#if showForm}
  <Modal
    open={true}
    title={editingWebhook ? 'Edit Webhook' : 'Create Webhook'}
    onClose={handleFormCancel}
    size="large"
  >
    <OutgoingForm
      {store}
      webhook={editingWebhook}
      {events}
      onsave={handleFormSave}
      oncancel={handleFormCancel}
    />
  </Modal>
{/if}

<style>
  .outgoing-list {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .outgoing-list__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .outgoing-list__header h3 {
    margin: 0;
    font-size: var(--wpea-text--lg);
    font-weight: 600;
  }

  .outgoing-list__loading {
    text-align: center;
    padding: var(--wpea-space--xl);
    color: var(--wpea-surface--text-muted);
  }

  .outgoing-list__items {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--md);
  }

  .webhook-item {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--md);
  }

  .webhook-item__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .webhook-item__title {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
  }

  .webhook-item__title h4 {
    margin: 0;
    font-size: var(--wpea-text--md);
    font-weight: 600;
  }

  .webhook-item__details {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .webhook-item__url {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
  }

  .webhook-item__url code {
    font-size: var(--wpea-text--sm);
    padding: var(--wpea-space--xs) var(--wpea-space--sm);
    background: var(--wpea-color--neutral-l-9);
    border-radius: var(--wpea-radius--sm);
    word-break: break-all;
  }

  .webhook-item__label {
    font-weight: 500;
    color: var(--wpea-surface--text-muted);
    min-width: 60px;
  }

  .webhook-item__events {
    display: flex;
    align-items: flex-start;
    gap: var(--wpea-space--sm);
  }

  .webhook-item__event-badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--wpea-space--xs);
  }

  .webhook-item__meta {
    display: flex;
    gap: var(--wpea-space--lg);
    font-size: var(--wpea-text--sm);
    color: var(--wpea-surface--text-muted);
  }

  .webhook-item__test-result {
    margin-top: var(--wpea-space--sm);
  }

  .webhook-item__actions {
    display: flex;
    gap: var(--wpea-space--sm);
    padding-top: var(--wpea-space--sm);
    border-top: 1px solid var(--wpea-surface--border);
  }
</style>
