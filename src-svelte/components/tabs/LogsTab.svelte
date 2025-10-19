<script>
/**
 * Logs Tab Component
 *
 * View activity logs and history
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

// State
let logs = $state([]);
let actionTypes = $state([]);
let actionFilter = $state('');
let detailsFilter = $state('');
let loading = $state(true);

// Fetch logs from API
async function fetchLogs() {
  try {
    loading = true;
    const params = new URLSearchParams();
    if (actionFilter) {
      params.append('action', actionFilter);
    }
    if (detailsFilter) {
      params.append('details', detailsFilter);
    }

    const queryString = params.toString();
    const endpoint = `/logs${queryString ? '?' + queryString : ''}`;

    const response = await store.apiRequest(endpoint);
    logs = response.logs || [];
  } catch (error) {
    console.error('Error fetching logs:', error);
    store.showError();
  } finally {
    loading = false;
  }
}

// Fetch action types for filter dropdown
async function fetchActionTypes() {
  try {
    const response = await store.apiRequest('/logs/actions');
    actionTypes = response.actions || [];
  } catch (error) {
    console.error('Error fetching action types:', error);
  }
}

// Clear all logs
async function clearLogs() {
  if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest('/logs', {
      method: 'DELETE',
    });

    await fetchLogs();
    await fetchActionTypes();
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

// Apply filters with debounce
let filterTimeout;
function applyFilters() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    fetchLogs();
  }, 300);
}

// Initialize
$effect(() => {
  fetchLogs();
  fetchActionTypes();
});

// Watch filters
$effect(() => {
  actionFilter;
  detailsFilter;
  applyFilters();
});
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Activity Logs</h2>
    <p class="wpea-text-muted">View recent changes and activity history.</p>
  </div>

  <!-- Filters and Actions -->
  <div class="wpea-cluster wpea-cluster--md" style="justify-content: space-between; align-items: flex-end;">
    <div class="wpea-cluster wpea-cluster--md" style="flex: 1;">
      <div class="wpea-field" style="flex: 1; max-width: 250px;">
        <label for="action-filter" class="wpea-label">Filter by Action</label>
        <select
          id="action-filter"
          bind:value={actionFilter}
          class="wpea-select"
        >
          <option value="">All Actions</option>
          {#each actionTypes as action}
            <option value={action}>{action}</option>
          {/each}
        </select>
      </div>

      <div class="wpea-field" style="flex: 1; max-width: 300px;">
        <label for="details-filter" class="wpea-label">Filter by Details</label>
        <input
          id="details-filter"
          type="search"
          bind:value={detailsFilter}
          placeholder="Search details, user, or action..."
          class="wpea-input"
        />
      </div>
    </div>

    <button
      type="button"
      class="wpea-btn"
      onclick={clearLogs}
      disabled={logs.length === 0}
    >
      Clear All Logs
    </button>
  </div>

  <div class="wpea-card">
    {#if loading}
      <div style="padding: var(--wpea-space--lg); text-align: center;">
        <p class="wpea-text-muted">Loading logs...</p>
      </div>
    {:else if logs.length === 0}
      <div style="padding: var(--wpea-space--lg); text-align: center;">
        <p class="wpea-text-muted">
          {#if actionFilter || detailsFilter}
            No logs match your filters. Try adjusting your search criteria.
          {:else}
            No activity logs yet. Actions will be logged as you make changes.
          {/if}
        </p>
      </div>
    {:else}
      <table class="wpea-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Action</th>
            <th>Details</th>
            <th>User</th>
          </tr>
        </thead>
        <tbody>
          {#each logs as log}
            <tr>
              <td>
                {formatDate(log.timestamp)}
              </td>
              <td>
                <strong>{log.action}</strong>
              </td>
              <td>
                {log.details}
              </td>
              <td>
                {log.user}
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    {/if}
  </div>

  <div class="wpea-alert wpea-alert--info">
    <p>
      <strong>Note:</strong> Activity logs track all changes made to roles, capabilities, and user assignments.
      Up to 500 log entries are stored. Logs are cleared when the plugin is uninstalled.
    </p>
  </div>
</div>
