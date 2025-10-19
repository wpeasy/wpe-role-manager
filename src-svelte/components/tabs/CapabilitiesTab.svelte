<script>
/**
 * Capabilities Tab Component
 *
 * Matrix view of roles × capabilities
 * Add, remove, disable capabilities per role
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

// Local state
let searchQuery = $state('');
let selectedRole = $state('all');
let capabilityTypeFilter = $state('all'); // all, core, external, custom
let showGranted = $state(true);
let showNotGranted = $state(true);
let showAddCapModal = $state(false);
let newCapability = $state({
  role: '',
  capability: '',
  autoAddToAdmin: true,
});

// Filtered capabilities based on search query, type filter, and granted/denied toggles
let filteredCapabilities = $derived(
  store.capabilityMatrix.filter(cap => {
    // Check if capability matches search query
    if (!cap.capability?.toLowerCase().includes(searchQuery.toLowerCase())) {
      return false;
    }

    // Filter by capability type
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

    // If both toggles are on or both off, show all capabilities
    if ((showGranted && showNotGranted) || (!showGranted && !showNotGranted)) {
      return true;
    }

    // Check if capability is granted or not granted in any of the filtered roles
    const relevantRoles = selectedRole === 'all'
      ? store.roles
      : store.roles.filter(role => role.slug === selectedRole);

    const hasGranted = relevantRoles.some(role => cap.roles?.[role.slug]?.granted === true);
    const hasNotGranted = relevantRoles.some(role => cap.roles?.[role.slug]?.granted !== true);

    if (showGranted && hasGranted) return true;
    if (showNotGranted && hasNotGranted) return true;

    return false;
  })
);

// Filtered roles based on selected role
let filteredRoles = $derived(
  selectedRole === 'all'
    ? store.roles
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

    // Add capability to selected role
    await store.apiRequest(`/roles/${newCapability.role}/caps`, {
      method: 'POST',
      body: JSON.stringify({
        capability: newCapability.capability,
      }),
    });

    // If auto-add to admin is enabled and selected role is not administrator, add to administrator too
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
  <div class="wpea-stack wpea-stack--sm">
    <div class="wpea-cluster wpea-cluster--md" style="justify-content: space-between; flex-wrap: wrap;">
      <div class="wpea-cluster wpea-cluster--sm">
        <div style="flex: 1; max-width: 300px;">
          <input
            type="search"
            bind:value={searchQuery}
            placeholder="Search capabilities..."
            class="wpea-input"
          />
        </div>

        <select bind:value={selectedRole} class="wpea-select" style="min-width: 200px;">
          <option value="all">All Roles</option>
          {#each store.roles as role}
            <option value={role.slug}>{role.name}</option>
          {/each}
        </select>

        <!-- Filter Toggles -->
        <div class="wpea-cluster wpea-cluster--xs" style="border-left: 1px solid var(--wpea-surface--border); padding-left: var(--wpea-space--sm);">
          <label class="wpea-control" style="margin: 0;">
            <input
              type="checkbox"
              bind:checked={showGranted}
            />
            <span style="font-size: var(--wpea-text--sm);">Show Granted</span>
          </label>

          <label class="wpea-control" style="margin: 0;">
            <input
              type="checkbox"
              bind:checked={showNotGranted}
            />
            <span style="font-size: var(--wpea-text--sm);">Show not granted</span>
          </label>
        </div>
      </div>

      <button
        type="button"
        class="wpea-btn wpea-btn--primary"
        onclick={() => showAddCapModal = true}
      >
        + Add Capability
      </button>
    </div>

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

  <!-- Info Alert -->
  <div class="wpea-alert wpea-alert--success">
    <p>
      <strong>How it works:</strong> Capabilities you manage show colored buttons - click to toggle between
      <strong style="color: var(--wpea-color--success);">Granted</strong> (green) →
      <strong style="color: var(--wpea-color--danger);">Denied</strong> (red) →
      <strong>Unset</strong> (grey) → back to Granted.
      Capabilities from core/external code show ✓ or ✗ (read-only). Click <strong>+</strong> on empty cells to grant new capabilities.
    </p>
  </div>

  <!-- Capabilities Matrix -->
  <div class="wpea-card">
    {#if filteredCapabilities.length === 0}
      <div style="padding: var(--wpea-space--lg); text-align: center;">
        <p class="wpea-text-muted">No capabilities found.</p>
      </div>
    {:else}
      <div style="overflow: auto; max-height: 70vh;">
        <table class="wpea-table" style="min-width: 800px; overflow: visible !important; border-radius: 0;">
          <thead>
            <tr>
              <th style="position: sticky; top: 0; left: 0; background: var(--wpea-surface--muted); z-index: 4; width: 250px; min-width: 250px; max-width: 250px;">Capability</th>
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
                    <span class="badge" style="background: var(--wpea-color--neutral-l-8); color: var(--wpea-surface--text); font-size: var(--wpea-text--2xs);">Core</span>
                  {:else if cap.isExternal}
                    <span class="badge" style="background: var(--wpea-color--warning-l-9); color: var(--wpea-color--warning); font-size: var(--wpea-text--2xs);">External</span>
                  {:else}
                    <span class="badge" style="background: var(--wpea-color--primary-l-9); color: var(--wpea-color--primary); font-size: var(--wpea-text--2xs);">Custom</span>
                  {/if}
                </td>
                {#each filteredRoles as role}
                  {@const roleCapState = cap.roles?.[role.slug]}
                  {@const isGranted = roleCapState?.granted === true}
                  {@const isDenied = roleCapState?.denied === true}
                  {@const isUnset = !isGranted && !isDenied}
                  {@const isManaged = roleCapState?.managed === true}

                  {@const bgColor = isGranted ? 'var(--wpea-color--success-l-9)' : isDenied ? 'var(--wpea-color--danger-l-9)' : 'transparent'}
                  {@const currentState = isGranted ? 'granted' : isDenied ? 'denied' : 'unset'}

                  <td style="text-align: center; background: {bgColor};">
                    {#if isManaged}
                      <!-- Managed by plugin - show toggle button and delete button -->
                      <div style="display: flex; gap: var(--wpea-space--xs); justify-content: center; align-items: center;">
                        {#if isGranted}
                          <button
                            type="button"
                            class="wpea-btn wpea-btn--sm"
                            style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; background: var(--wpea-color--success); color: white;"
                            onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                            title="Click to deny"
                          >
                            Granted
                          </button>
                        {:else if isDenied}
                          <button
                            type="button"
                            class="wpea-btn wpea-btn--sm"
                            style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; background: var(--wpea-color--danger); color: white;"
                            onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                            title="Click to unset"
                          >
                            Denied
                          </button>
                        {:else}
                          <button
                            type="button"
                            class="wpea-btn wpea-btn--sm"
                            style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; background: var(--wpea-color--neutral-l-6); color: white;"
                            onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                            title="Click to grant"
                          >
                            Unset
                          </button>
                        {/if}
                        <button
                          type="button"
                          class="wpea-btn wpea-btn--sm"
                          style="padding: var(--wpea-space--xs); font-size: var(--wpea-text--xs); background: var(--wpea-color--danger); color: white; min-width: auto;"
                          onclick={() => deleteCapability(role.slug, cap.capability)}
                          title="Delete capability from this role"
                        >
                          ×
                        </button>
                      </div>
                    {:else}
                      <!-- Not managed - read-only or add new -->
                      {#if isGranted}
                        <span style="color: var(--wpea-color--success); font-size: var(--wpea-text--sm); font-weight: 600;" title="Granted by core/external code">✓</span>
                      {:else if isDenied}
                        <span style="color: var(--wpea-color--danger); font-size: var(--wpea-text--sm); font-weight: 600;" title="Denied by core/external code">✗</span>
                      {:else}
                        <button
                          type="button"
                          class="wpea-btn wpea-btn--ghost wpea-btn--sm"
                          style="padding: var(--wpea-space--xs) var(--wpea-space--sm); font-size: var(--wpea-text--xs); min-width: 65px; opacity: 0.4;"
                          onclick={() => toggleCapability(role.slug, cap.capability, currentState)}
                          title="Click to grant"
                        >
                          +
                        </button>
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
  </div>

  <!-- Add Capability Modal -->
  {#if showAddCapModal}
    <div class="modal-overlay" role="dialog" aria-modal="true" onclick={() => showAddCapModal = false} onkeydown={(e) => e.key === 'Escape' && (showAddCapModal = false)}>
      <div class="wpea-card" style="max-width: 500px; max-height: 90vh; overflow: auto;" onclick={(e) => e.stopPropagation()} role="document">
        <div class="wpea-card__header">
          <h3 class="wpea-card__title">Add Capability to Role</h3>
          <button
            type="button"
            style="background: none; border: none; padding: 0; min-width: 2rem; font-size: var(--wpea-text--2xl); cursor: pointer; color: var(--wpea-surface--text); line-height: 1;"
            onclick={() => showAddCapModal = false}
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div class="wpea-stack">
          <div class="wpea-field">
            <label for="cap-role" class="wpea-label">
              Select Role <span style="color: #d63638;">*</span>
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
              Capability Name <span style="color: #d63638;">*</span>
            </label>
            <input
              type="text"
              id="cap-name"
              bind:value={newCapability.capability}
              placeholder="e.g., manage_custom_posts"
              class="wpea-input"
            />
            <p class="wpea-help">Enter the capability slug (lowercase, underscores only).</p>
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

          <div class="wpea-alert wpea-alert--warning">
            <p>
              <strong>Note:</strong> You can add capabilities to any role. However, you can only remove
              capabilities that were created by this plugin. Core and external capabilities cannot be removed.
            </p>
          </div>
        </div>

        <div class="wpea-cluster wpea-cluster--md" style="justify-content: flex-end; padding-top: var(--wpea-space--md); border-top: 1px solid var(--wpea-surface--divider);">
          <button
            type="button"
            class="wpea-btn"
            onclick={() => showAddCapModal = false}
          >
            Cancel
          </button>
          <button
            type="button"
            class="wpea-btn wpea-btn--primary"
            onclick={addCapability}
          >
            Add Capability
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
