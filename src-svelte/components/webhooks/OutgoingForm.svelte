<script>
/**
 * Outgoing Webhook Form Component
 *
 * Form for creating and editing outgoing webhooks.
 *
 * @package WP_Easy\RoleManager
 */

import { Button, Input, Alert } from '../../lib/index.ts';

let { store, webhook = null, events = {}, onsave, oncancel } = $props();

// Form state
let name = $state(webhook?.name || '');
let url = $state(webhook?.url || '');
let secret = $state(webhook?.secret || '');
let method = $state(webhook?.method || 'POST');
let retries = $state(webhook?.retries || 3);
let enabled = $state(webhook?.enabled ?? true);
let selectedEvents = $state(webhook?.events || []);
let headers = $state(webhook?.headers || []);
let saving = $state(false);
let error = $state('');

// Add a new header
function addHeader() {
  headers = [...headers, { key: '', value: '' }];
}

// Remove a header
function removeHeader(index) {
  headers = headers.filter((_, i) => i !== index);
}

// Toggle event selection
function toggleEvent(eventKey) {
  if (selectedEvents.includes(eventKey)) {
    selectedEvents = selectedEvents.filter(e => e !== eventKey);
  } else {
    selectedEvents = [...selectedEvents, eventKey];
  }
}

// Generate new secret
async function regenerateSecret() {
  secret = Array.from(crypto.getRandomValues(new Uint8Array(16)))
    .map(b => b.toString(16).padStart(2, '0'))
    .join('');
}

// Copy secret to clipboard
async function copySecret() {
  try {
    await navigator.clipboard.writeText(secret);
  } catch (e) {
    console.error('Failed to copy:', e);
  }
}

// Validate form
function validate() {
  if (!name.trim()) {
    return 'Name is required.';
  }
  if (!url.trim()) {
    return 'URL is required.';
  }
  try {
    new URL(url);
  } catch {
    return 'Invalid URL format.';
  }
  if (selectedEvents.length === 0) {
    return 'Select at least one event.';
  }
  return null;
}

// Submit form
async function handleSubmit() {
  error = '';
  const validationError = validate();
  if (validationError) {
    error = validationError;
    return;
  }

  saving = true;

  const data = {
    name: name.trim(),
    url: url.trim(),
    secret,
    method,
    retries: parseInt(retries, 10),
    enabled,
    events: selectedEvents,
    headers: headers.filter(h => h.key.trim()),
  };

  try {
    if (webhook) {
      // Update existing
      await store.apiRequest(`/webhooks/${webhook.id}`, {
        method: 'PATCH',
        body: JSON.stringify(data),
      });
    } else {
      // Create new
      await store.apiRequest('/webhooks', {
        method: 'POST',
        body: JSON.stringify(data),
      });
    }
    onsave?.();
  } catch (err) {
    error = err.message || 'Failed to save webhook.';
  } finally {
    saving = false;
  }
}

// Initialize secret for new webhooks
if (!webhook && !secret) {
  regenerateSecret();
}
</script>

<form class="webhook-form" onsubmit={(e) => { e.preventDefault(); handleSubmit(); }}>
  {#if error}
    <Alert variant="danger">{error}</Alert>
  {/if}

  <div class="webhook-form__field">
    <label for="webhook-name">Name</label>
    <Input
      id="webhook-name"
      bind:value={name}
      placeholder="My Webhook"
    />
  </div>

  <div class="webhook-form__field">
    <label for="webhook-url">URL</label>
    <Input
      id="webhook-url"
      type="url"
      bind:value={url}
      placeholder="https://example.com/webhook"
    />
  </div>

  <div class="webhook-form__field">
    <label for="webhook-secret">Secret (for HMAC signing)</label>
    <div class="webhook-form__secret-row">
      <Input
        id="webhook-secret"
        bind:value={secret}
        readonly
      />
      <Button variant="secondary" size="sm" onclick={copySecret}>Copy</Button>
      <Button variant="secondary" size="sm" onclick={regenerateSecret}>Regenerate</Button>
    </div>
    <small>This secret is used to sign webhook payloads. Keep it secure.</small>
  </div>

  <div class="webhook-form__row">
    <div class="webhook-form__field webhook-form__field--half">
      <label for="webhook-method">HTTP Method</label>
      <select id="webhook-method" bind:value={method}>
        <option value="POST">POST</option>
        <option value="GET">GET</option>
      </select>
    </div>

    <div class="webhook-form__field webhook-form__field--half">
      <label for="webhook-retries">Max Retries</label>
      <Input
        id="webhook-retries"
        type="number"
        min="1"
        max="5"
        bind:value={retries}
      />
    </div>
  </div>

  <div class="webhook-form__field">
    <label>Events</label>
    <div class="webhook-form__events">
      {#each Object.entries(events) as [key, label]}
        <label class="webhook-form__event-checkbox">
          <input
            type="checkbox"
            checked={selectedEvents.includes(key)}
            onchange={() => toggleEvent(key)}
          />
          <span>{label}</span>
        </label>
      {/each}
    </div>
  </div>

  <div class="webhook-form__field">
    <label>Custom Headers (optional)</label>
    <div class="webhook-form__headers">
      {#each headers as header, i}
        <div class="webhook-form__header-row">
          <Input
            placeholder="Header name"
            bind:value={header.key}
          />
          <Input
            placeholder="Header value"
            bind:value={header.value}
          />
          <Button variant="danger" size="sm" onclick={() => removeHeader(i)}>Remove</Button>
        </div>
      {/each}
      <Button variant="secondary" size="sm" onclick={addHeader}>Add Header</Button>
    </div>
  </div>

  <div class="webhook-form__field">
    <label class="webhook-form__enabled-checkbox">
      <input
        type="checkbox"
        bind:checked={enabled}
      />
      <span>Enable webhook immediately</span>
    </label>
  </div>

  <div class="webhook-form__actions">
    <Button variant="secondary" onclick={oncancel} disabled={saving}>
      Cancel
    </Button>
    <Button variant="primary" type="submit" disabled={saving}>
      {saving ? 'Saving...' : (webhook ? 'Update Webhook' : 'Create Webhook')}
    </Button>
  </div>
</form>

<style>
  .webhook-form {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .webhook-form__field {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xs);
  }

  .webhook-form__field label {
    font-weight: 500;
    font-size: var(--wpea-text--sm);
  }

  .webhook-form__field small {
    font-size: var(--wpea-text--xs);
    color: var(--wpea-surface--text-muted);
  }

  .webhook-form__field select {
    padding: var(--wpea-space--sm);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--md);
    background: var(--wpea-surface--bg);
    font-size: var(--wpea-text--sm);
  }

  .webhook-form__row {
    display: flex;
    gap: var(--wpea-space--lg);
  }

  .webhook-form__field--half {
    flex: 1;
  }

  .webhook-form__secret-row {
    display: flex;
    gap: var(--wpea-space--sm);
  }

  .webhook-form__secret-row :global(input) {
    flex: 1;
    font-family: monospace;
  }

  .webhook-form__events {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--wpea-space--sm);
    padding: var(--wpea-space--md);
    background: var(--wpea-surface--muted);
    border-radius: var(--wpea-radius--md);
  }

  .webhook-form__event-checkbox {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
    font-weight: normal;
    cursor: pointer;
  }

  .webhook-form__event-checkbox input {
    width: 16px;
    height: 16px;
  }

  .webhook-form__headers {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .webhook-form__header-row {
    display: flex;
    gap: var(--wpea-space--sm);
  }

  .webhook-form__header-row :global(input) {
    flex: 1;
  }

  .webhook-form__enabled-checkbox {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
    font-weight: normal;
    cursor: pointer;
  }

  .webhook-form__enabled-checkbox input {
    width: 18px;
    height: 18px;
  }

  .webhook-form__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--wpea-space--sm);
    padding-top: var(--wpea-space--md);
    border-top: 1px solid var(--wpea-surface--border);
  }
</style>
