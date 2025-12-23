<script>
/**
 * Capabilities Tab Component
 *
 * Matrix view of roles × capabilities
 * Add, remove, disable capabilities per role
 *
 * @package WP_Easy\RoleManager
 */

import { doubleScrollbar } from '../../lib/doubleScrollbar.js';
import { sanitizeSlug, validateSlug } from '../../lib/utils.js';
import { Modal, Button, Card, Input, Select, Alert, Badge } from '../../lib/index.ts';
import { emit } from '../../shared/events';

let { store } = $props();

// Local state
let searchQuery = $state('');
let selectedRole = $state('all');
let capabilityTypeFilter = $state('all');
let showAddCapModal = $state(false);
let newCapability = $state({
  role: '',
  capability: '',
  autoAddToAdmin: true,
});
let capValidation = $state({ valid: true, error: null });
let sortColumn = $state('capability');
let sortDirection = $state('asc');

// Function to toggle sort
function toggleSort(column) {
  if (sortColumn === column) {
    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn = column;
    sortDirection = 'asc';
  }
}

// Filtered and sorted capabilities based on search query, type filter
let filteredCapabilities = $derived.by(() => {
  const filtered = store.capabilityMatrix.filter(cap => {
    if (!cap.capability?.toLowerCase().includes(searchQuery.toLowerCase())) {
      return false;
    }

    if (capabilityTypeFilter !== 'all') {
      if (capabilityTypeFilter === 'core' && !cap.isCore) {
        return false;
      }
      if (capabilityTypeFilter === 'external' && !cap.isExternal) {
        return false;
      }
      if (capabilityTypeFilter === 'custom' && (cap.isCore || cap.isExternal)) {
        return false;
      }
    }

    return true;
  });

  return filtered.sort((a, b) => {
    let aVal, bVal;

    if (sortColumn === 'capability') {
      aVal = a.capability?.toLowerCase() || '';
      bVal = b.capability?.toLowerCase() || '';
    }

    const comparison = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
    return sortDirection === 'asc' ? comparison : -comparison;
  });
});

// Filtered roles based on selected role, sorted alphabetically
let filteredRoles = $derived(
  selectedRole === 'all'
    ? [...store.roles].sort((a, b) => a.name.localeCompare(b.name))
    : store.roles.filter(role => role.slug === selectedRole)
);

// Handle adding capability to a role from the modal
async function addCapability() {
  if (!newCapability.role || !newCapability.capability) {
    alert('Please select a role and enter a capability name');
    return;
  }

  try {
    store.showSaving();

    await store.apiRequest(`/roles/${newCapability.role}/caps`, {
      method: 'POST',
      body: JSON.stringify({
        capability: newCapability.capability,
      }),
    });

    if (newCapability.autoAddToAdmin && newCapability.role !== 'administrator') {
      await store.apiRequest(`/roles/administrator/caps`, {
        method: 'POST',
        body: JSON.stringify({
          capability: newCapability.capability,
        }),
      });
    }

    await store.fetchCapabilityMatrix();
    store.showSaved();

    // Emit event for external scripts
    emit('capability:added', { role: newCapability.role, capability: newCapability.capability, granted: true });
    emit('capabilities:updated');

    newCapability = { role: '', capability: '', autoAddToAdmin: true };
    showAddCapModal = false;
  } catch (error) {
    console.error('Error adding capability:', error);
    store.showError();
  }
}

// Handle toggling capability state (grant -> deny -> unset -> grant)
async function toggleCapability(roleSlug, capability, currentState) {
  let nextAction;

  if (currentState === 'granted') {
    nextAction = 'deny';
  } else if (currentState === 'denied') {
    nextAction = 'unset';
  } else {
    nextAction = 'grant';
  }

  try {
    store.showSaving();
    await store.apiRequest(`/roles/${roleSlug}/caps/${capability}`, {
      method: 'PATCH',
      body: JSON.stringify({
        action: nextAction,
      }),
    });

    await store.fetchCapabilityMatrix();
    store.showSaved();

    // Emit event for external scripts
    emit('capability:toggled', { role: roleSlug, capability, action: nextAction });
    emit('capabilities:updated');
  } catch (error) {
    console.error('Error toggling capability:', error);
    alert(error.message || 'Failed to toggle capability. It may not have been added by this plugin.');
    store.showError();
  }
}

// Handle deleting a capability from a role
async function deleteCapability(roleSlug, capability) {
  if (!confirm(`Are you sure you want to remove "${capability}" from the "${roleSlug}" role? This action cannot be undone.`)) {
    return;
  }

  try {
    store.showSaving();
    await store.apiRequest(`/roles/${roleSlug}/caps/${capability}`, {
      method: 'DELETE',
    });

    await store.fetchCapabilityMatrix();
    store.showSaved();

    // Emit event for external scripts
    emit('capability:removed', { role: roleSlug, capability });
    emit('capabilities:updated');
  } catch (error) {
    console.error('Error deleting capability:', error);
    alert(error.message || 'Failed to delete capability. It may not have been added by this plugin.');
    store.showError();
  }
}
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Capability Management</h2>
    <p class="wpea-text-muted">View and manage capabilities for each role. Add, remove, or disable capabilities.</p>
  </div>

  <!-- Actions Bar -->
  <div class="wpea-cluster wpea-cluster--sm" style="justify-content: space-between; flex-wrap: wrap; align-items: center;">
    <div class="wpea-cluster wpea-cluster--sm" style="align-items: center;">
      <Input
        type="search"
        bind:value={searchQuery}
        placeholder="Search capabilities..."
        style="width: 250px;"
      />

      <select bind:value={selectedRole} class="wpea-select" style="width: 180px;">
        <option value="all">All Roles</option>
        {#each store.roles as role}
          <option value={role.slug}>{role.name}</option>
        {/each}
      </select>

      <!-- Capability Type Filter (Radio Buttons) -->
      <div class="wpea-cluster wpea-cluster--xs" style="padding: var(--wpea-space--sm); background: var(--wpea-surface--muted); border-radius: var(--wpea-radius--sm);">
        <span style="font-size: var(--wpea-text--sm); font-weight: 500; color: var(--wpea-surface--text-muted);">Type:</span>
        <label class="wpea-control" style="margin: 0;">
          <input
            type="radio"
            name="capability-type"
            value="all"
            bind:group={capabilityTypeFilter}
          />
          <span style="font-size: var(--wpea-text--sm);">All</span>
        </label>
        <label class="wpea-control" style="margin: 0;">
          <input
            type="radio"
            name="capability-type"
            value="core"
            bind:group={capabilityTypeFilter}
          />
          <span style="font-size: var(--wpea-text--sm);">Core</span>
        </label>
        <label class="wpea-control" style="margin: 0;">
          <input
            type="radio"
            name="capability-type"
            value="external"
            bind:group={capabilityTypeFilter}
          />
          <span style="font-size: var(--wpea-text--sm);">External</span>
        </label>
        <label class="wpea-control" style="margin: 0;">
          <input
            type="radio"
            name="capability-type"
            value="custom"
            bind:group={capabilityTypeFilter}
          />
          <span style="font-size: var(--wpea-text--sm);">Custom</span>
        </label>
      </div>
    </div>

    <Button variant="primary" onclick={() => showAddCapModal = true}>
      + Add Capability
    </Button>
  </div>

  <!-- Info Alert -->
  <Alert variant="success">
    <p>
      <strong>How it works:</strong> Capabilities you manage show colored buttons - click to toggle between
      <strong style="color: var(--wpea-color--success);">Granted</strong> (green) →
      <strong style="color: var(--wpea-color--danger);">Denied</strong> (red) →
      <strong>Unset</strong> (grey) → back to Granted.
      Capabilities from core/external code show ✓ or ✗ (read-only). Click <strong>+</strong> on empty cells to grant new capabilities.
    </p>
  </Alert>

  <!-- Capabilities Matrix -->
  <Card>
    {#if store.loadingCapabilities}
      <div style="padding: var(--wpea-space--xl); text-align: center;">
        <div class="wpea-spinner"></div>
      </div>
    {:else if filteredCapabilities.length === 0}
      <div style="padding: var(--wpea-space--lg); text-align: center;">
        <p class="wpea-text-muted">No capabilities found.</p>
      </div>
    {:else}
      <div class="wpea-table-wrapper" use:doubleScrollbar style="max-height: 70vh;">
        <table class="wpea-table" style="min-width: 800px; overflow: visible !important; border-radius: 0;">
          <thead>
            <tr>
              <th class="sortable" onclick={() => toggleSort('capability')} style="cursor: pointer; user-select: none; position: sticky; top: 0; left: 0; background: var(--wpea-surface--muted); z-index: 4; width: 250px; min-width: 250px; max-width: 250px;">
                Capability
                {#if sortColumn === 'capability'}
                  <span style="margin-left: 4px;">{sortDirection === 'asc' ? '↑' : '↓'}</span>
                {/if}
              </th>
              <th style="position: sticky; top: 0; left: 250px; background: var(--wpea-surface--muted); z-index: 4; width: 100px; min-width: 100px; max-width: 100px;">Type</th>
              {#each filteredRoles as role}
                <th style="position: sticky; top: 0; background: var(--wpea-surface--muted); z-index: 3; text-align: center; min-width: 80px;">
                  <div style="writing-mode: vertical-rl; transform: rotate(180deg); margin: auto;">
                    {role.name}
                  </div>
                </th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each filteredCapabilities as cap}
              <tr>
                <td style="position: sticky; left: 0; background: var(--wpea-surface--panel); z-index: 1; width: 250px; min-width: 250px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  <code style="font-size: var(--wpea-text--sm);" title={cap.capability}>{cap.capability}</code>
                </td>
                <td style="position: sticky; left: 250px; background: var(--wpea-surface--panel); z-index: 1; width: 100px; min-width: 100px; max-width: 100px;">
                  {#if cap.isCore}
                    <Badge class="core" style="font-size: var(--wpea-text--2xs);">Core</Badge>
                  {:else if cap.isExternal}
                    <Badge class="external" style="font-size: var(--wpea-text--2xs);">External</Badge>
                  {:else}
                    <Badge variant="primary" style="font-size: var(--wpea-text--2xs);">Custom</Badge>
                  {/if}
                </td>
                {#each filteredRoles as role}
                  {@const roleCapState = cap.roles?.[role.slug]}
                  {@const isGranted = roleCapState?.granted === true}
                  {@const isDenied = roleCapState?.denied === true}
                  {@const isUnset = !isGranted && !isDenied}
                  {@const isManaged = roleCapState?.managed === true}

                  {@const bgColor = isGranted ? 'var(--wpea-cap-granted-bg)' : isDenied ? 'var(--wpea-cap-denied-bg)' : 'transparent'}
                  {@const currentState = isGranted ? 'granted' : isDenied ? 'denied' : 'unset'}
                  {@const canDeleteExternal = !isManaged && cap.isExternal && store.settings?.allow_external_deletion}

                  <td style="text-align: center; background: {bgColor};">

                    {#if isManaged}
                      <!-- Managed by plugin - show toggle button and delete button -->
                      <div style="display: flex; gap: var(--wpea-space--xs); justify-content: center; align-items: center;">
                        {#if isGranted}
                          <Button
                            size="sm"
                            style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; background: var(--wpea-color--success); color: white;"
                            onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                          >
                            Granted
                          </Button>
                        {:else if isDenied}
                          <Button
                            size="sm"
                            style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; background: var(--wpea-color--danger); color: white;"
                            onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                          >
                            Denied
                          </Button>
                        {:else}
                          <Button
                            size="sm"
                            style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; background: var(--wpea-color--neutral-l-6); color: white;"
                            onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                          >
                            Unset
                          </Button>
                        {/if}
                        <Button
                          size="sm"
                          variant="danger"
                          style="padding: var(--wpea-space--xs); font-size: var(--wpea-text--xs); min-width: auto;"
                          onclick={() => deleteCapability(role.slug, cap.capability)}
                        >
                          ×
                        </Button>
                      </div>
                    {:else if canDeleteExternal && (isGranted || isDenied)}
                      <!-- External capability with deletion allowed - show delete button -->
                      <div style="display: flex; gap: var(--wpea-space--xs); justify-content: center; align-items: center;">
                        {#if isGranted}
                          <span style="color: var(--wpea-color--success); font-size: var(--wpea-text--sm); font-weight: 600;" title="Granted by core/external code">✓</span>
                        {:else}
                          <span style="color: var(--wpea-color--danger); font-size: var(--wpea-text--sm); font-weight: 600;" title="Denied by core/external code">✗</span>
                        {/if}
                        <Button
                          size="sm"
                          variant="danger"
                          style="padding: var(--wpea-space--xs); font-size: var(--wpea-text--xs); min-width: auto;"
                          onclick={() => deleteCapability(role.slug, cap.capability)}
                        >
                          ×
                        </Button>
                      </div>
                    {:else}
                      <!-- Not managed - read-only or add new -->
                      {#if isGranted}
                        <span style="color: var(--wpea-color--success); font-size: var(--wpea-text--sm); font-weight: 600;" title="Granted by core/external code">✓</span>
                      {:else if isDenied}
                        <span style="color: var(--wpea-color--danger); font-size: var(--wpea-text--sm); font-weight: 600;" title="Denied by core/external code">✗</span>
                      {:else}
                        <Button
                          size="sm"
                          variant="ghost"
                          style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; opacity: 0.4;"
                          onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                        >
                          +
                        </Button>
                      {/if}
                    {/if}
                  </td>
                {/each}
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    {/if}
  </Card>
</div>

<!-- Add Capability Modal -->
<Modal bind:open={showAddCapModal} title="Add Capability to Role" onClose={() => showAddCapModal = false}>
  {#snippet children()}
    <div class="wpea-stack">
      <div class="wpea-field">
        <label for="cap-role" class="wpea-label">
          Select Role <span style="color: var(--wpea-color--danger);">*</span>
        </label>
        <select id="cap-role" bind:value={newCapability.role} class="wpea-select">
          <option value="">Choose a role...</option>
          {#each store.roles as role}
            <option value={role.slug}>{role.name}</option>
          {/each}
        </select>
        <p class="wpea-help">Select any role to add the capability to.</p>
      </div>

      <div class="wpea-field">
        <label for="cap-name" class="wpea-label">
          Capability Name <span style="color: var(--wpea-color--danger);">*</span>
        </label>
        <input
          type="text"
          id="cap-name"
          bind:value={newCapability.capability}
          oninput={(e) => {
            newCapability.capability = sanitizeSlug(e.target.value, 'capability');
            capValidation = validateSlug(newCapability.capability, 'capability');
          }}
          placeholder="e.g., manage_custom_posts"
          class="wpea-input"
          class:wpea-input--error={!capValidation.valid}
          maxlength="191"
        />
        {#if !capValidation.valid && newCapability.capability}
          <p class="wpea-help wpea-help--error">{capValidation.error}</p>
        {:else}
          <p class="wpea-help">Enter the capability slug (lowercase, underscores only). Maximum 191 characters.</p>
        {/if}
      </div>

      <div class="wpea-field">
        <label class="wpea-control" style="margin: 0;">
          <input
            type="checkbox"
            bind:checked={newCapability.autoAddToAdmin}
          />
          <span>Also add to Administrator role</span>
        </label>
        <p class="wpea-help">Automatically grant this capability to the Administrator role as well.</p>
      </div>

      <Alert variant="warning">
        <p>
          <strong>Note:</strong> You can add capabilities to any role. However, you can only remove
          capabilities that were created by this plugin. Core and external capabilities cannot be removed.
        </p>
      </Alert>
    </div>
  {/snippet}

  {#snippet footer()}
    <div class="wpea-cluster wpea-cluster--md" style="justify-content: flex-end;">
      <Button onclick={() => showAddCapModal = false}>Cancel</Button>
      <Button variant="primary" onclick={addCapability} disabled={!newCapability.role || !newCapability.capability || !capValidation.valid}>
        Add Capability
      </Button>
    </div>
  {/snippet}
</Modal>

<style>
/* Validation states */
.wpea-input--error {
  border-color: var(--wpea-color--danger);
}

.wpea-input--error:focus {
  border-color: var(--wpea-color--danger);
  box-shadow: 0 0 0 1px var(--wpea-color--danger);
}

.wpea-help--error {
  color: var(--wpea-color--danger);
}
</style>
