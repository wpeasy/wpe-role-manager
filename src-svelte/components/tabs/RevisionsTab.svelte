<script>
/**
 * Revisions Tab Component
 *
 * View and restore revisions
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

let revisions = $state([]);
let revisionTypes = $state([]);
let revisionActions = $state([]);
let filterType = $state('');
let filterAction = $state('');
let searchQuery = $state('');

// Double opt-in state
let pendingAction = $state(null); // { id, type: 'delete'|'restore', timeout }

// Load revisions on mount
$effect(() => {
  fetchRevisions();
  fetchFilterOptions();
});

async function fetchRevisions() {
  try {
    store.showSaving();
    const params = new URLSearchParams();
    if (filterType) params.append('revision_type', filterType);
    if (filterAction) params.append('action', filterAction);

    const response = await store.apiRequest(`/revisions?${params.toString()}`);
    revisions = response.revisions || [];
    store.showSaved();
  } catch (error) {
    console.error('Error fetching revisions:', error);
    store.showError();
  }
}

async function fetchFilterOptions() {
  try {
    const response = await store.apiRequest('/revisions/types');
    revisionTypes = response.types || [];
    revisionActions = response.actions || [];
  } catch (error) {
    console.error('Error fetching filter options:', error);
  }
}

// Double opt-in pattern for delete
function initiateDelete(revisionId) {
  // Clear any existing pending action
  clearPendingAction();

  // Set pending action
  pendingAction = {
    id: revisionId,
    type: 'delete',
    timeout: setTimeout(() => {
      pendingAction = null;
    }, 4000)
  };
}

// Double opt-in pattern for restore
function initiateRestore(revisionId) {
  // Clear any existing pending action
  clearPendingAction();

  // Set pending action
  pendingAction = {
    id: revisionId,
    type: 'restore',
    timeout: setTimeout(() => {
      pendingAction = null;
    }, 4000)
  };
}

function clearPendingAction() {
  if (pendingAction?.timeout) {
    clearTimeout(pendingAction.timeout);
  }
  pendingAction = null;
}

async function confirmDelete(revisionId) {
  if (pendingAction?.id !== revisionId || pendingAction?.type !== 'delete') {
    return;
  }

  clearPendingAction();

  try {
    store.showSaving();
    await store.apiRequest(`/revisions/${revisionId}`, {
      method: 'DELETE',
    });
    store.showSaved();
    await fetchRevisions();
  } catch (error) {
    console.error('Error deleting revision:', error);
    store.showError();
    alert('Failed to delete revision: ' + error.message);
  }
}

async function confirmRestore(revisionId) {
  if (pendingAction?.id !== revisionId || pendingAction?.type !== 'restore') {
    return;
  }

  clearPendingAction();

  try {
    store.showSaving();
    await store.apiRequest(`/revisions/${revisionId}/restore`, {
      method: 'POST',
    });
    store.showSaved();
    await fetchRevisions();
    // Refresh other data
    await store.fetchRoles();
    await store.fetchCapabilityMatrix();
  } catch (error) {
    console.error('Error restoring revision:', error);
    store.showError();
    alert('Failed to restore revision: ' + error.message);
  }
}

async function deleteAllRevisions() {
  if (!confirm('Are you sure you want to delete ALL revisions? This action cannot be undone.')) {
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest('/revisions', {
      method: 'DELETE',
    });
    store.showSaved();
    await fetchRevisions();
  } catch (error) {
    console.error('Error deleting all revisions:', error);
    store.showError();
    alert('Failed to delete all revisions: ' + error.message);
  }
}

// Filtered revisions
const filteredRevisions = $derived.by(() => {
  return revisions.filter(revision => {
    const matchesSearch = searchQuery === '' ||
      revision.note.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesSearch;
  });
});

// Format date/time
function formatDateTime(datetime) {
  const date = new Date(datetime);
  return date.toLocaleString();
}

// Get badge color for revision type
function getTypeBadgeColor(type) {
  const colors = {
    'role': 'blue',
    'capability': 'green',
    'user_roles': 'purple',
  };
  return colors[type] || 'gray';
}

// Get badge color for action
function getActionBadgeColor(action) {
  const colors = {
    'created': 'green',
    'deleted': 'red',
    'modified': 'yellow',
    'assigned': 'blue',
    'unassigned': 'orange',
    'removed': 'red',
  };
  return colors[action] || 'gray';
}
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <div class="wpea-cluster wpea-cluster--sm" style="justify-content: space-between; align-items: center;">
      <div>
        <h2 class="wpea-heading wpea-heading--md">Revisions</h2>
        <p class="wpea-text-muted">View and restore previous states of roles, capabilities, and user assignments.</p>
      </div>
      <button
        type="button"
        class="wpea-btn wpea-btn--danger-outline"
        onclick={deleteAllRevisions}
        disabled={revisions.length === 0}
      >
        Clear All Revisions
      </button>
    </div>
  </div>

  <!-- Filters -->
  <div class="wpea-cluster wpea-cluster--sm" style="justify-content: space-between; flex-wrap: wrap; align-items: center;">
    <div class="wpea-cluster wpea-cluster--sm" style="align-items: center;">
      <input
        type="search"
        bind:value={searchQuery}
        placeholder="Search revisions..."
        class="wpea-input"
        style="width: 250px;"
      />

      <select bind:value={filterType} onchange={fetchRevisions} class="wpea-select" style="width: 150px;">
        <option value="">All Types</option>
        {#each revisionTypes as type}
          <option value={type}>{type}</option>
        {/each}
      </select>

      <select bind:value={filterAction} onchange={fetchRevisions} class="wpea-select" style="width: 150px;">
        <option value="">All Actions</option>
        {#each revisionActions as action}
          <option value={action}>{action}</option>
        {/each}
      </select>
    </div>

    <div class="wpea-text-muted" style="font-size: var(--wpea-text--sm);">
      {filteredRevisions.length} revision{filteredRevisions.length !== 1 ? 's' : ''}
    </div>
  </div>

  <!-- Revisions List -->
  {#if filteredRevisions.length === 0}
    <div class="wpea-card" style="text-align: center; padding: var(--wpea-space--xl);">
      <p class="wpea-text-muted">No revisions found.</p>
    </div>
  {:else}
    <div class="wpea-stack wpea-stack--sm">
      {#each filteredRevisions as revision}
        <div class="wpea-card">
          <div class="wpea-cluster wpea-cluster--sm" style="justify-content: space-between; align-items: start;">
            <div class="wpea-stack wpea-stack--xs" style="flex: 1;">
              <div class="wpea-cluster wpea-cluster--xs" style="align-items: center;">
                <span class="wpea-badge wpea-badge--{getTypeBadgeColor(revision.revision_type)}">{revision.revision_type}</span>
                <span class="wpea-badge wpea-badge--{getActionBadgeColor(revision.action)}">{revision.action}</span>
                <span class="wpea-text-muted" style="font-size: var(--wpea-text--sm);">
                  {formatDateTime(revision.created_at)}
                </span>
              </div>

              <p style="margin: 0; font-weight: 500;">{revision.note}</p>

              <div class="wpea-text-muted" style="font-size: var(--wpea-text--sm);">
                By: {revision.user_name}
              </div>
            </div>

            <div class="wpea-cluster wpea-cluster--xs">
              <!-- Restore Button -->
              {#if pendingAction?.id === revision.id && pendingAction?.type === 'restore'}
                <button
                  type="button"
                  class="wpea-btn wpea-btn--success"
                  onclick={() => confirmRestore(revision.id)}
                  title="Click again to confirm restore"
                >
                  ✓ Confirm Restore
                </button>
              {:else}
                <button
                  type="button"
                  class="wpea-btn wpea-btn--primary-outline"
                  onclick={() => initiateRestore(revision.id)}
                  title="Restore this revision"
                >
                  ↺ Restore
                </button>
              {/if}

              <!-- Delete Button -->
              {#if pendingAction?.id === revision.id && pendingAction?.type === 'delete'}
                <button
                  type="button"
                  class="wpea-btn wpea-btn--danger"
                  onclick={() => confirmDelete(revision.id)}
                  title="Click again to confirm deletion"
                >
                  ✓ Confirm Delete
                </button>
              {:else}
                <button
                  type="button"
                  class="wpea-btn wpea-btn--danger-outline"
                  onclick={() => initiateDelete(revision.id)}
                  title="Delete this revision"
                >
                  × Delete
                </button>
              {/if}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
