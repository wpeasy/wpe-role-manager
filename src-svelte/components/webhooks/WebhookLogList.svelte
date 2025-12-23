<script>
/**
 * Webhook Activity Log Component
 *
 * Displays the webhook activity log with filtering.
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { doubleScrollbar } from '../../lib/doubleScrollbar.js';
import { Card, Button, Alert, Badge, Input } from '../../lib/index.ts';

let { store } = $props();

// State
let logs = $state([]);
let stats = $state({ total: 0, outgoing: 0, incoming: 0, success: 0, failed: 0, retrying: 0 });
let loading = $state(true);
let directionFilter = $state('');
let statusFilter = $state('');
let searchFilter = $state('');
let expandedLog = $state(null);

// Fetch logs
async function fetchLogs() {
  try {
    loading = true;
    const params = new URLSearchParams();
    if (directionFilter) params.append('direction', directionFilter);
    if (statusFilter) params.append('status', statusFilter);
    if (searchFilter) params.append('search', searchFilter);

    const queryString = params.toString();
    const endpoint = `/webhooks/log${queryString ? '?' + queryString : ''}`;

    const response = await store.apiRequest(endpoint);
    logs = response.logs || [];
    stats = response.stats || stats;
  } catch (error) {
    console.error('Error fetching webhook logs:', error);
    store.showError();
  } finally {
    loading = false;
  }
}

// Clear logs
async function clearLogs() {
  if (!confirm('Are you sure you want to clear all webhook logs? This action cannot be undone.')) {
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest('/webhooks/log', {
      method: 'DELETE',
    });
    await fetchLogs();
    store.showSaved();
  } catch (error) {
    console.error('Error clearing logs:', error);
    store.showError();
  }
}

// Format timestamp
function formatDate(timestamp) {
  return new Date(timestamp).toLocaleString();
}

// Toggle expanded log
function toggleExpanded(logId) {
  expandedLog = expandedLog === logId ? null : logId;
}

// Get status badge variant
function getStatusVariant(status) {
  switch (status) {
    case 'success': return 'success';
    case 'failed': return 'danger';
    case 'retrying': return 'warning';
    default: return 'secondary';
  }
}

// Apply filters with debounce
let filterTimeout;
function applyFilters() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    fetchLogs();
  }, 300);
}

// Watch for filter changes
$effect(() => {
  directionFilter;
  statusFilter;
  applyFilters();
});

// Initialize
onMount(() => {
  fetchLogs();
});
</script>

<div class="webhook-log">
  <div class="webhook-log__header">
    <h3>Webhook Activity Log</h3>
    <Button variant="danger" size="sm" onclick={clearLogs} disabled={logs.length === 0}>
      Clear All
    </Button>
  </div>

  <Card>
    <div class="webhook-log__stats">
      <div class="webhook-log__stat">
        <span class="webhook-log__stat-value">{stats.total}</span>
        <span class="webhook-log__stat-label">Total</span>
      </div>
      <div class="webhook-log__stat">
        <span class="webhook-log__stat-value webhook-log__stat--outgoing">{stats.outgoing}</span>
        <span class="webhook-log__stat-label">Outgoing</span>
      </div>
      <div class="webhook-log__stat">
        <span class="webhook-log__stat-value webhook-log__stat--incoming">{stats.incoming}</span>
        <span class="webhook-log__stat-label">Incoming</span>
      </div>
      <div class="webhook-log__stat">
        <span class="webhook-log__stat-value webhook-log__stat--success">{stats.success}</span>
        <span class="webhook-log__stat-label">Success</span>
      </div>
      <div class="webhook-log__stat">
        <span class="webhook-log__stat-value webhook-log__stat--failed">{stats.failed}</span>
        <span class="webhook-log__stat-label">Failed</span>
      </div>
      <div class="webhook-log__stat">
        <span class="webhook-log__stat-value webhook-log__stat--retrying">{stats.retrying}</span>
        <span class="webhook-log__stat-label">Retrying</span>
      </div>
    </div>
  </Card>

  <div class="webhook-log__filters">
    <select bind:value={directionFilter}>
      <option value="">All Directions</option>
      <option value="outgoing">Outgoing</option>
      <option value="incoming">Incoming</option>
    </select>

    <select bind:value={statusFilter}>
      <option value="">All Statuses</option>
      <option value="success">Success</option>
      <option value="failed">Failed</option>
      <option value="retrying">Retrying</option>
    </select>

    <Input
      placeholder="Search..."
      bind:value={searchFilter}
      oninput={applyFilters}
    />
  </div>

  {#if loading}
    <div class="webhook-log__loading">
      <p>Loading logs...</p>
    </div>
  {:else if logs.length === 0}
    <Card>
      <Alert variant="info">
        No webhook activity logged yet. Logs will appear here when webhooks are triggered or received.
      </Alert>
    </Card>
  {:else}
    <Card>
      <div class="webhook-log__table-wrapper" use:doubleScrollbar>
        <table class="webhook-log__table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Direction</th>
              <th>Event</th>
              <th>Webhook</th>
              <th>Status</th>
              <th>Duration</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {#each logs as log (log.id)}
              <tr class:expanded={expandedLog === log.id}>
                <td>{formatDate(log.timestamp)}</td>
                <td>
                  <Badge variant={log.direction === 'outgoing' ? 'primary' : 'secondary'} size="sm">
                    {log.direction}
                  </Badge>
                </td>
                <td><code>{log.event}</code></td>
                <td>{log.webhook_name || '-'}</td>
                <td>
                  <Badge variant={getStatusVariant(log.status)} size="sm">
                    {log.status}
                    {#if log.attempt > 1}
                      ({log.attempt}/{log.max_attempts})
                    {/if}
                  </Badge>
                </td>
                <td>{log.duration_ms}ms</td>
                <td>
                  <Button
                    variant="secondary"
                    size="sm"
                    onclick={() => toggleExpanded(log.id)}
                  >
                    {expandedLog === log.id ? 'Hide' : 'Details'}
                  </Button>
                </td>
              </tr>
              {#if expandedLog === log.id}
                <tr class="webhook-log__details-row">
                  <td colspan="7">
                    <div class="webhook-log__details">
                      <div class="webhook-log__detail">
                        <strong>URL:</strong>
                        <code>{log.url}</code>
                      </div>
                      <div class="webhook-log__detail">
                        <strong>Method:</strong>
                        <code>{log.method}</code>
                      </div>
                      {#if log.response_code}
                        <div class="webhook-log__detail">
                          <strong>Response Code:</strong>
                          <code>{log.response_code}</code>
                        </div>
                      {/if}
                      {#if log.error}
                        <div class="webhook-log__detail webhook-log__detail--error">
                          <strong>Error:</strong>
                          <span>{log.error}</span>
                        </div>
                      {/if}
                      {#if log.request_payload}
                        <div class="webhook-log__detail">
                          <strong>Request Payload:</strong>
                          <pre><code>{JSON.stringify(log.request_payload, null, 2)}</code></pre>
                        </div>
                      {/if}
                      {#if log.response_body}
                        <div class="webhook-log__detail">
                          <strong>Response Body:</strong>
                          <pre><code>{log.response_body}</code></pre>
                        </div>
                      {/if}
                    </div>
                  </td>
                </tr>
              {/if}
            {/each}
          </tbody>
        </table>
      </div>
    </Card>
  {/if}
</div>

<style>
  .webhook-log {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .webhook-log__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .webhook-log__header h3 {
    margin: 0;
    font-size: var(--wpea-text--lg);
    font-weight: 600;
  }

  .webhook-log__stats {
    display: flex;
    gap: var(--wpea-space--xl);
    flex-wrap: wrap;
  }

  .webhook-log__stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--wpea-space--xs);
  }

  .webhook-log__stat-value {
    font-size: var(--wpea-text--2xl);
    font-weight: 600;
  }

  .webhook-log__stat-label {
    font-size: var(--wpea-text--xs);
    color: var(--wpea-surface--text-muted);
    text-transform: uppercase;
  }

  .webhook-log__stat--success { color: var(--wpea-color--success); }
  .webhook-log__stat--failed { color: var(--wpea-color--danger); }
  .webhook-log__stat--retrying { color: var(--wpea-color--warning); }
  .webhook-log__stat--outgoing { color: var(--wpea-color--primary); }
  .webhook-log__stat--incoming { color: var(--wpea-color--secondary); }

  .webhook-log__filters {
    display: flex;
    gap: var(--wpea-space--md);
    flex-wrap: wrap;
  }

  .webhook-log__filters select {
    padding: var(--wpea-space--sm) var(--wpea-space--md);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--md);
    background: var(--wpea-surface--bg);
    font-size: var(--wpea-text--sm);
    min-width: 150px;
  }

  .webhook-log__loading {
    text-align: center;
    padding: var(--wpea-space--xl);
    color: var(--wpea-surface--text-muted);
  }

  .webhook-log__table-wrapper {
    overflow-x: auto;
  }

  .webhook-log__table {
    width: 100%;
    border-collapse: collapse;
  }

  .webhook-log__table th,
  .webhook-log__table td {
    padding: var(--wpea-space--sm) var(--wpea-space--md);
    text-align: left;
    border-bottom: 1px solid var(--wpea-surface--border);
    font-size: var(--wpea-text--sm);
  }

  .webhook-log__table th {
    font-weight: 600;
    background: var(--wpea-surface--muted);
  }

  .webhook-log__table code {
    font-size: var(--wpea-text--xs);
    padding: var(--wpea-space--xs);
    background: var(--wpea-color--neutral-l-9);
    border-radius: var(--wpea-radius--xs);
  }

  .webhook-log__details-row td {
    padding: 0;
    background: var(--wpea-surface--muted);
  }

  .webhook-log__details {
    padding: var(--wpea-space--md);
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .webhook-log__detail {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xs);
  }

  .webhook-log__detail strong {
    font-size: var(--wpea-text--xs);
    color: var(--wpea-surface--text-muted);
    text-transform: uppercase;
  }

  .webhook-log__detail--error span {
    color: var(--wpea-color--danger);
  }

  .webhook-log__details pre {
    margin: 0;
    padding: var(--wpea-space--sm);
    background: var(--wpea-color--neutral-l-9);
    border-radius: var(--wpea-radius--sm);
    overflow-x: auto;
    font-size: var(--wpea-text--xs);
    line-height: 1.4;
  }

  .webhook-log__details code {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
  }
</style>
