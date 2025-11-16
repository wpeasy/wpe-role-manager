<script>
/**
 * Roles Tab Component
 *
 * Manage WordPress roles: create, disable, delete
 *
 * @package WP_Easy\RoleManager
 */

import { doubleScrollbar } from '../../lib/doubleScrollbar.js';
import { sanitizeSlug, generateCapabilityName } from '../../lib/utils.js';

let { store } = $props();

// Local state
let showCreateModal = $state(false);
let showDeleteModal = $state(false);
let showStandardCapsModal = $state(false);
let roleToDelete = $state(null);
let deleteConfirmation = $state('');
let removeFromUsers = $state(false);
let newRole = $state({
  slug: '',
  name: '',
  copyFrom: '',
});
let searchQuery = $state('');
let sortColumn = $state('name'); // Default sort by name
let sortDirection = $state('asc'); // 'asc' or 'desc'

// Standard capabilities template
let standardCapabilities = $state({
  read: true,
  read_private: true,
  edit: true,
  edit_others: true,
  edit_published: true,
  edit_private: true,
  publish: true,
  delete: true,
  delete_others: true,
  delete_published: true,
  delete_private: true,
});

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
// Open standard capabilities modal
function openStandardCapsModal() {
  if (!newRole.slug) {
    alert('Please provide a role slug first');
    return;
  }
  showStandardCapsModal = true;
}

async function createRole() {
  if (!newRole.slug || !newRole.name) {
    alert('Please provide both slug and name');
    return;
  }

  try {
    store.showSaving();

    // Create the role first
    await store.apiRequest('/roles', {
      method: 'POST',
      body: JSON.stringify(newRole),
    });

    // Add selected standard capabilities if any are enabled
    const selectedCaps = Object.entries(standardCapabilities)
      .filter(([_, enabled]) => enabled)
      .map(([pattern, _]) => generateCapabilityName(pattern, newRole.slug))
      .filter(cap => cap !== '');

    if (selectedCaps.length > 0) {
      // Add each capability to the newly created role AND administrator
      for (const capability of selectedCaps) {
        try {
          // Add to the new role
          await store.apiRequest(`/roles/${newRole.slug}/caps`, {
            method: 'POST',
            body: JSON.stringify({ capability }),
          });

          // Also add to administrator role
          await store.apiRequest(`/roles/administrator/caps`, {
            method: 'POST',
            body: JSON.stringify({ capability }),
          });
        } catch (capError) {
          console.error(`Error adding capability ${capability}:`, capError);
        }
      }
    }

    await store.fetchRoles();
    await store.fetchCapabilityMatrix();
    store.showSaved();

    // Reset form
    newRole = { slug: '', name: '', copyFrom: '' };
    standardCapabilities = {
      read: true,
      read_private: true,
      edit: true,
      edit_others: true,
      edit_published: true,
      edit_private: true,
      publish: true,
      delete: true,
      delete_others: true,
      delete_published: true,
      delete_private: true,
    };
    showCreateModal = false;
    showStandardCapsModal = false;
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
  removeFromUsers = false;
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

  // If role has users and checkbox not checked, show error
  if (roleToDelete.userCount > 0 && !removeFromUsers) {
    alert('Please check the box to confirm removal from users');
    return;
  }

  try {
    store.showSaving();

    // Build query parameters
    const params = new URLSearchParams();
    if (removeFromUsers) {
      params.append('remove_from_users', 'true');
    }

    const url = `/roles/${roleToDelete.slug}${params.toString() ? '?' + params.toString() : ''}`;

    await store.apiRequest(url, {
      method: 'DELETE',
    });

    await store.fetchRoles();
    await store.fetchCapabilityMatrix();
    store.showSaved();
    showDeleteModal = false;
    roleToDelete = null;
    deleteConfirmation = '';
    removeFromUsers = false;
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
              oninput={(e) => newRole.slug = sanitizeSlug(e.target.value)}
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

          <div class="wpea-field">
            <button
              type="button"
              class="wpea-btn"
              onclick={openStandardCapsModal}
            >
              + Add Standard Capabilities
            </button>
            <p class="wpea-help">Generate standard WordPress-style capabilities for this role.</p>
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

          {#if roleToDelete.userCount > 0}
            <div class="wpea-alert wpea-alert--warning">
              <p>
                <strong>This role is assigned to {roleToDelete.userCount} user{roleToDelete.userCount === 1 ? '' : 's'}.</strong><br>
                Roles assigned to users cannot be deleted unless you remove the role from all users first.
              </p>
            </div>

            <div class="wpea-field">
              <label style="display: flex; align-items: center; gap: var(--wpea-space--sm); cursor: pointer;">
                <input
                  type="checkbox"
                  bind:checked={removeFromUsers}
                  style="margin: 0; cursor: pointer;"
                />
                <span>
                  I understand that this will remove the role from all {roleToDelete.userCount} user{roleToDelete.userCount === 1 ? '' : 's'} before deleting it
                  <span style="color: #d63638;">*</span>
                </span>
              </label>
            </div>
          {/if}

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
            disabled={deleteConfirmation.toLowerCase() !== 'delete' || (roleToDelete.userCount > 0 && !removeFromUsers)}
          >
            Delete Role
          </button>
        </div>
      </div>
    </div>
  {/if}

  <!-- Standard Capabilities Modal -->
  {#if showStandardCapsModal}
    <div class="modal-overlay" role="dialog" aria-modal="true" onclick={() => showStandardCapsModal = false} onkeydown={(e) => e.key === 'Escape' && (showStandardCapsModal = false)}>
      <div class="wpea-card" style="max-width: 500px; max-height: 90vh; overflow: auto;" onclick={(e) => e.stopPropagation()} role="document">
        <div class="wpea-card__header">
          <h3 class="wpea-card__title">Select Standard Capabilities</h3>
          <button
            type="button"
            style="background: none; border: none; padding: 0; min-width: 2rem; font-size: var(--wpea-text--2xl); cursor: pointer; color: var(--wpea-surface--text); line-height: 1;"
            onclick={() => showStandardCapsModal = false}
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div class="wpea-stack">
          <p class="wpea-help">
            Select which standard capabilities to generate for the role "<strong>{newRole.slug || 'your-role'}</strong>".
            All are selected by default.
          </p>

          <div class="wpea-stack wpea-stack--sm">
            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.read} />
              <div>
                <div><code>read_{newRole.slug || 'slug'}</code></div>
                <div class="wpea-help" style="margin: 0;">Read capability for this resource</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.read_private} />
              <div>
                <div><code>read_private_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Read private resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.edit} />
              <div>
                <div><code>edit_{newRole.slug || 'slug'}</code></div>
                <div class="wpea-help" style="margin: 0;">Edit own resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.edit_others} />
              <div>
                <div><code>edit_others_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Edit resources created by others</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.edit_published} />
              <div>
                <div><code>edit_published_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Edit published resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.edit_private} />
              <div>
                <div><code>edit_private_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Edit private resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.publish} />
              <div>
                <div><code>publish_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Publish resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.delete} />
              <div>
                <div><code>delete_{newRole.slug || 'slug'}</code></div>
                <div class="wpea-help" style="margin: 0;">Delete own resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.delete_others} />
              <div>
                <div><code>delete_others_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Delete resources created by others</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.delete_published} />
              <div>
                <div><code>delete_published_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Delete published resources</div>
              </div>
            </label>

            <label class="wpea-control" style="display: flex; align-items: center; gap: var(--wpea-space--sm);">
              <input type="checkbox" bind:checked={standardCapabilities.delete_private} />
              <div>
                <div><code>delete_private_{newRole.slug ? (newRole.slug.endsWith('s') ? newRole.slug : newRole.slug + 's') : 'slugs'}</code></div>
                <div class="wpea-help" style="margin: 0;">Delete private resources</div>
              </div>
            </label>
          </div>
        </div>

        <div class="wpea-cluster wpea-cluster--md" style="justify-content: flex-end; padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
          <button
            type="button"
            class="wpea-btn"
            onclick={() => showStandardCapsModal = false}
          >
            Close
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
