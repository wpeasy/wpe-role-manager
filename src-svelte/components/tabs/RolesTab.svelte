<script>
/**
 * Roles Tab Component
 *
 * Manage WordPress roles: create, disable, delete
 *
 * @package WP_Easy\RoleManager
 */

import { doubleScrollbar } from '../../lib/doubleScrollbar.js';

let { store } = $props();

// Local state
let showCreateModal = $state(false);
let showDeleteModal = $state(false);
let roleToDelete = $state(null);
let deleteConfirmation = $state('');
let newRole = $state({
  slug: '',
  name: '',
  copyFrom: '',
});
let searchQuery = $state('');
let sortColumn = $state('name'); // Default sort by name
let sortDirection = $state('asc'); // 'asc' or 'desc'

// Function to toggle sort
function toggleSort(column) {
  if (sortColumn === column) {
    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn = column;
    sortDirection = 'asc';
  }
}

// Filtered and sorted roles
let filteredRoles = $derived.by(() => {
  const filtered = store.roles.filter(role =>
    role.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    role.slug?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  // Sort the filtered results
  return filtered.sort((a, b) => {
    let aVal, bVal;

    if (sortColumn === 'name') {
      aVal = a.name?.toLowerCase() || '';
      bVal = b.name?.toLowerCase() || '';
    }

    const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
    return sortDirection === 'asc' ? comparison : -comparison;
  });
});

// Handle role creation
async function createRole() {
  if (!newRole.slug || !newRole.name) {
    alert('Please provide both slug and name');
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest('/roles', {
      method: 'POST',
      body: JSON.stringify(newRole),
    });

    await store.fetchRoles();
    await store.fetchCapabilityMatrix();
    store.showSaved();

    // Reset form
    newRole = { slug: '', name: '', copyFrom: '' };
    showCreateModal = false;
  } catch (error) {
    console.error('Error creating role:', error);
    store.showError();
  }
}

// Handle role disable/enable
async function toggleRoleStatus(role) {
  try {
    store.showSaving();
    await store.apiRequest(`/roles/${role.slug}`, {
      method: 'PATCH',
      body: JSON.stringify({
        disabled: !role.disabled,
      }),
    });

    await store.fetchRoles();
    await store.fetchCapabilityMatrix();
    store.showSaved();
  } catch (error) {
    console.error('Error toggling role:', error);
    store.showError();
  }
}

// Show delete confirmation modal
function confirmDeleteRole(role) {
  roleToDelete = role;
  deleteConfirmation = '';
  showDeleteModal = true;
}

// Handle role deletion
async function deleteRole() {
  if (!roleToDelete) return;

  // Verify confirmation text
  if (deleteConfirmation.toLowerCase() !== 'delete') {
    alert('Please type "delete" to confirm');
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest(`/roles/${roleToDelete.slug}`, {
      method: 'DELETE',
    });

    await store.fetchRoles();
    await store.fetchCapabilityMatrix();
    store.showSaved();
    showDeleteModal = false;
    roleToDelete = null;
    deleteConfirmation = '';
  } catch (error) {
    console.error('Error deleting role:', error);
    store.showError();
  }
}
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Role Management</h2>
    <p class="wpea-text-muted">Manage WordPress roles. Create, disable, or delete custom roles.</p>
  </div>

  <!-- Actions Bar -->
  <div class="wpea-cluster wpea-cluster--md" style="justify-content: space-between;">
    <div style="flex: 1; max-width: 300px;">
      <input
        type="search"
        bind:value={searchQuery}
        placeholder="Search roles..."
        class="wpea-input"
      />
    </div>

    <button
      type="button"
      class="wpea-btn wpea-btn--primary"
      onclick={() => showCreateModal = true}
    >
      + Create New Role
    </button>
  </div>

  <!-- Roles Table -->
  <div class="wpea-card">
    {#if filteredRoles.length === 0}
      <div style="padding: var(--wpea-space--lg); text-align: center;">
        <p class="wpea-text-muted">No roles found. {searchQuery ? 'Try a different search term.' : 'Create your first role!'}</p>
      </div>
    {:else}
      <div class="wpea-table-wrapper" use:doubleScrollbar>
        <table class="wpea-table">
        <thead>
          <tr>
            <th class="sortable" onclick={() => toggleSort('name')} style="cursor: pointer; user-select: none;">
              Role Name
              {#if sortColumn === 'name'}
                <span style="margin-left: 4px;">{sortDirection === 'asc' ? '↑' : '↓'}</span>
              {/if}
            </th>
            <th>Slug</th>
            <th>Type</th>
            <th>Status</th>
            <th>Users</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each filteredRoles as role}
            <tr style:opacity={role.disabled ? '0.6' : '1'}>
              <td>
                <strong>{role.name || 'Unnamed Role'}</strong>
              </td>
              <td>
                <code style="font-size: var(--wpea-text--sm); color: var(--wpea-color--secondary);">{role.slug}</code>
              </td>
              <td>
                {#if role.isCore}
                  <span class="badge core">Core</span>
                {:else if role.isExternal}
                  <span class="badge external">External</span>
                {:else}
                  <span class="badge badge--primary">Custom</span>
                {/if}
              </td>
              <td>
                {#if role.disabled}
                  <span class="badge badge--warning">Disabled</span>
                {:else}
                  <span class="badge badge--success">Active</span>
                {/if}
              </td>
              <td>
                {role.userCount || 0}
              </td>
              <td>
                <div class="wpea-cluster wpea-cluster--sm">
                  {#if role.isCore}
                    <span class="wpea-text-muted wpea-text-sm" style="font-style: italic;">Read-only</span>
                  {:else if !role.isExternal}
                    <!-- Plugin-created custom role -->
                    <button
                      type="button"
                      class="wpea-btn wpea-btn--sm"
                      onclick={() => toggleRoleStatus(role)}
                    >
                      {role.disabled ? 'Enable' : 'Disable'}
                    </button>
                    <button
                      type="button"
                      class="wpea-btn wpea-btn--sm"
                      style="--_bg: var(--wpea-color--neutral-l-7); --_fg: #d63638; --_bg-hover: color-mix(in oklab, #ef4444, transparent 85%);"
                      onclick={() => confirmDeleteRole(role)}
                    >
                      Delete
                    </button>
                  {:else if role.isExternal && store.settings?.allow_external_deletion}
                    <!-- External role with deletion allowed -->
                    <button
                      type="button"
                      class="wpea-btn wpea-btn--sm"
                      style="--_bg: var(--wpea-color--neutral-l-7); --_fg: #d63638; --_bg-hover: color-mix(in oklab, #ef4444, transparent 85%);"
                      onclick={() => confirmDeleteRole(role)}
                    >
                      Delete
                    </button>
                  {:else}
                    <!-- External role without deletion allowed -->
                    <span class="wpea-text-muted wpea-text-sm" style="font-style: italic;">Managed externally</span>
                  {/if}
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
      </div>
    {/if}
  </div>

  <!-- Create Role Modal -->
  {#if showCreateModal}
    <div class="modal-overlay" role="dialog" aria-modal="true" onclick={() => showCreateModal = false} onkeydown={(e) => e.key === 'Escape' && (showCreateModal = false)}>
      <div class="wpea-card" style="max-width: 500px; max-height: 90vh; overflow: auto;" onclick={(e) => e.stopPropagation()} role="document">
        <div class="wpea-card__header">
          <h3 class="wpea-card__title">Create New Role</h3>
          <button
            type="button"
            class="wpea-btn wpea-btn--ghost wpea-btn--sm"
            style="padding: 0; min-width: 2rem; font-size: var(--wpea-text--2xl);"
            onclick={() => showCreateModal = false}
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div class="wpea-stack">
          <div class="wpea-field">
            <label for="role-slug" class="wpea-label">
              Role Slug <span style="color: #d63638;">*</span>
            </label>
            <input
              type="text"
              id="role-slug"
              bind:value={newRole.slug}
              placeholder="e.g., custom_editor"
              pattern="[a-z0-9_-]+"
              class="wpea-input"
            />
            <p class="wpea-help">Lowercase letters, numbers, underscores, and hyphens only.</p>
          </div>

          <div class="wpea-field">
            <label for="role-name" class="wpea-label">
              Role Name <span style="color: #d63638;">*</span>
            </label>
            <input
              type="text"
              id="role-name"
              bind:value={newRole.name}
              placeholder="e.g., Custom Editor"
              class="wpea-input"
            />
          </div>

          <div class="wpea-field">
            <label for="role-copy-from" class="wpea-label">
              Copy Capabilities From (Optional)
            </label>
            <select id="role-copy-from" bind:value={newRole.copyFrom} class="wpea-select">
              <option value="">None (empty role)</option>
              {#each store.roles as role}
                <option value={role.slug}>{role.name}</option>
              {/each}
            </select>
            <p class="wpea-help">Start with capabilities from an existing role.</p>
          </div>
        </div>

        <div class="wpea-cluster wpea-cluster--md" style="justify-content: flex-end; padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
          <button
            type="button"
            class="wpea-btn"
            onclick={() => showCreateModal = false}
          >
            Cancel
          </button>
          <button
            type="button"
            class="wpea-btn wpea-btn--primary"
            onclick={createRole}
          >
            Create Role
          </button>
        </div>
      </div>
    </div>
  {/if}

  <!-- Delete Role Confirmation Modal -->
  {#if showDeleteModal && roleToDelete}
    <div class="modal-overlay" role="dialog" aria-modal="true" onclick={() => showDeleteModal = false} onkeydown={(e) => e.key === 'Escape' && (showDeleteModal = false)}>
      <div class="wpea-card" style="max-width: 500px; max-height: 90vh; overflow: auto;" onclick={(e) => e.stopPropagation()} role="document">
        <div class="wpea-card__header">
          <h3 class="wpea-card__title">Delete Role: {roleToDelete.name}</h3>
          <button
            type="button"
            style="background: none; border: none; padding: 0; min-width: 2rem; font-size: var(--wpea-text--2xl); cursor: pointer; color: var(--wpea-surface--text); line-height: 1;"
            onclick={() => showDeleteModal = false}
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div class="wpea-stack">
          <div class="wpea-alert wpea-alert--danger">
            <p>
              <strong>Warning:</strong> This action cannot be undone. Deleting this role will permanently remove it from your system.
            </p>
          </div>

          <p>
            You are about to delete the role <strong>{roleToDelete.name}</strong> (slug: <code>{roleToDelete.slug}</code>).
          </p>

          <div class="wpea-field">
            <label for="delete-confirm" class="wpea-label">
              Type <strong>delete</strong> to confirm <span style="color: #d63638;">*</span>
            </label>
            <input
              id="delete-confirm"
              type="text"
              bind:value={deleteConfirmation}
              placeholder="Type 'delete' to confirm"
              class="wpea-input"
              autocomplete="off"
            />
            <p class="wpea-help">This confirmation is case-insensitive.</p>
          </div>
        </div>

        <div class="wpea-cluster wpea-cluster--md" style="justify-content: flex-end; padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
          <button
            type="button"
            class="wpea-btn"
            onclick={() => showDeleteModal = false}
          >
            Cancel
          </button>
          <button
            type="button"
            class="wpea-btn"
            style="background: var(--wpea-color--danger); color: white;"
            onclick={deleteRole}
            disabled={deleteConfirmation.toLowerCase() !== 'delete'}
          >
            Delete Role
          </button>
        </div>
      </div>
    </div>
  {/if}
</div>

<style>
/* Modal overlay */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: color-mix(in oklab, var(--wpea-color--black), transparent 40%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100000;
  padding: var(--wpea-space--md);
}
</style>
