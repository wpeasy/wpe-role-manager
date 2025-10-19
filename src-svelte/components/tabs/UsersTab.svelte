<script>
/**
 * Users Tab Component
 *
 * Assign multiple roles to users
 * View effective capabilities per user
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

// Local state
let searchQuery = $state('');
let selectedUser = $state(null);
let showRolesModal = $state(false);
let showCapabilityTestModal = $state(false);
let capabilitySearchQuery = $state('');
let selectedCapability = $state('');
let testResult = $state(null);

// Filtered users
let filteredUsers = $derived(
  store.users.filter(user =>
    user.username?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    user.email?.toLowerCase().includes(searchQuery.toLowerCase())
  )
);

// Edit user roles
function editUserRoles(user) {
  selectedUser = user;
  showRolesModal = true;
}

// Update user roles (auto-save on change)
async function updateUserRoles() {
  if (!selectedUser) return;

  try {
    store.showSaving();
    await store.apiRequest(`/users/${selectedUser.id}/roles`, {
      method: 'PATCH',
      body: JSON.stringify({
        roles: selectedUser.roles,
      }),
    });

    await store.fetchUsers();
    store.showSaved();
  } catch (error) {
    console.error('Error updating user roles:', error);
    store.showError();
  }
}

// Test capability modal
function testUserCapability(user) {
  selectedUser = user;
  showCapabilityTestModal = true;
  capabilitySearchQuery = '';
  selectedCapability = '';
  testResult = null;
}

// Filtered capabilities for the test modal
let filteredCapabilities = $derived(
  store.capabilityMatrix
    .filter(cap => cap.capability?.toLowerCase().includes(capabilitySearchQuery.toLowerCase()))
    .map(cap => cap.capability)
);

// Test if user has capability
async function testUserCan() {
  if (!selectedUser || !selectedCapability) return;

  try {
    store.showSaving();
    const response = await store.apiRequest(`/users/${selectedUser.id}/can/${selectedCapability}`);
    testResult = response.result; // "granted", "denied", or "not_set"
    store.showSaved();
  } catch (error) {
    console.error('Error testing capability:', error);
    store.showError();
  }
}

// Copied state for buttons
let copiedButton = $state(null);

// Generate shortcode and copy to clipboard
async function generateShortcode() {
  if (!selectedCapability) return;

  // Determine granted parameter based on test result
  // If capability is granted, show content when granted="true"
  // If capability is denied or not_set, show content when granted="false"
  const grantedParam = testResult === 'granted' ? 'true' : 'false';

  const shortcode = `[wpe_cap capability="${selectedCapability}" granted="${grantedParam}"]Content only visible to users with this capability[/wpe_cap]`;

  try {
    await navigator.clipboard.writeText(shortcode);
    copiedButton = 'shortcode';
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}

// Generate PHP code and copy to clipboard
async function generatePHP() {
  if (!selectedCapability) return;

  const phpCode = `<?php
if ( current_user_can( '${selectedCapability}' ) ) {
    // User has the capability
    echo 'User has permission';
} else {
    // User does not have the capability
    echo 'User does not have permission';
}`;

  try {
    await navigator.clipboard.writeText(phpCode);
    copiedButton = 'php';
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}

// Generate REST URL and copy to clipboard
async function generateREST() {
  if (!selectedUser || !selectedCapability) return;

  const restUrl = `${window.location.origin}/wp-json/wpe-rm/v1/users/${selectedUser.id}/can/${selectedCapability}`;

  try {
    await navigator.clipboard.writeText(restUrl);
    copiedButton = 'rest';
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}

// Generate Fetch code and copy to clipboard
async function generateFetch() {
  if (!selectedUser || !selectedCapability) return;

  const fetchCode = `// Test if user has capability using WordPress REST API
// Note: Must be logged in with manage_options capability
fetch('/wp-json/wpe-rm/v1/users/${selectedUser.id}/can/${selectedCapability}', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce // WordPress REST nonce
  },
  credentials: 'same-origin'
})
  .then(response => response.json())
  .then(data => {
    console.log('Result:', data.result); // 'granted', 'denied', or 'not_set'
    if (data.result === 'granted') {
      console.log('User has the capability');
    } else if (data.result === 'denied') {
      console.log('User does not have the capability');
    } else {
      console.log('Capability is not set for this user');
    }
  })
  .catch(error => console.error('Error:', error));`;

  try {
    await navigator.clipboard.writeText(fetchCode);
    copiedButton = 'fetch';
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">User Management</h2>
    <p class="wpea-text-muted">Assign multiple roles to users and view their effective capabilities.</p>
  </div>

  <!-- Search Bar -->
  <div style="max-width: 300px;">
    <input
      type="search"
      bind:value={searchQuery}
      placeholder="Search users..."
      class="wpea-input"
    />
  </div>

  <!-- Users Table -->
  <div class="wpea-card">
    {#if filteredUsers.length === 0}
      <div style="padding: var(--wpea-space--lg); text-align: center;">
        <p class="wpea-text-muted">No users found.</p>
      </div>
    {:else}
      <table class="wpea-table">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each filteredUsers as user}
            <tr>
              <td>
                <strong>{user.username || 'Unknown'}</strong>
              </td>
              <td>
                {user.email || 'N/A'}
              </td>
              <td>
                {#if user.roles && user.roles.length > 0}
                  <div class="wpea-cluster wpea-cluster--sm">
                    {#each user.roles as role}
                      <span class="badge">{role}</span>
                    {/each}
                  </div>
                {:else}
                  <span class="wpea-text-muted wpea-text-sm" style="font-style: italic;">No roles</span>
                {/if}
              </td>
              <td>
                <div class="wpea-cluster wpea-cluster--xs">
                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm"
                    onclick={() => editUserRoles(user)}
                  >
                    Edit Roles
                  </button>
                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm wpea-btn--ghost"
                    onclick={() => testUserCapability(user)}
                  >
                    Test Capability
                  </button>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    {/if}
  </div>

  <!-- Edit Roles Modal -->
  {#if showRolesModal && selectedUser}
    <div class="modal-overlay" role="dialog" aria-modal="true" onclick={() => showRolesModal = false} onkeydown={(e) => e.key === 'Escape' && (showRolesModal = false)}>
      <div class="wpea-card" style="max-width: 500px; max-height: 90vh; overflow: auto;" onclick={(e) => e.stopPropagation()} role="document">
        <div class="wpea-card__header">
          <h3 class="wpea-card__title">Edit Roles: {selectedUser.username}</h3>
          <button
            type="button"
            style="background: none; border: none; padding: 0; min-width: 2rem; font-size: var(--wpea-text--2xl); cursor: pointer; color: var(--wpea-surface--text); line-height: 1;"
            onclick={() => showRolesModal = false}
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div class="wpea-stack">
          <p class="wpea-help">Select one or more roles for this user. Changes are saved automatically.</p>

          <div class="wpea-stack wpea-stack--sm">
            {#each store.roles as role}
              <label class="wpea-control" style="padding: var(--wpea-space--sm); border: 1px solid var(--wpea-surface--border); border-radius: var(--wpea-radius--sm); cursor: pointer; transition: background var(--wpea-anim-duration--fast);" onmouseover={(e) => e.currentTarget.style.background = 'var(--wpea-surface--muted)'} onmouseout={(e) => e.currentTarget.style.background = 'transparent'}>
                <input
                  type="checkbox"
                  value={role.slug}
                  checked={selectedUser.roles?.includes(role.slug)}
                  onchange={async (e) => {
                    if (e.target.checked) {
                      selectedUser.roles = [...(selectedUser.roles || []), role.slug];
                    } else {
                      selectedUser.roles = selectedUser.roles.filter(r => r !== role.slug);
                    }
                    await updateUserRoles();
                  }}
                />
                <span>{role.name}</span>
                {#if role.isCore}
                  <span class="badge" style="margin-left: auto; background: var(--wpea-color--neutral-l-8); color: var(--wpea-surface--text);">Core</span>
                {/if}
              </label>
            {/each}
          </div>
        </div>
      </div>
    </div>
  {/if}

  <!-- Test Capability Modal -->
  {#if showCapabilityTestModal && selectedUser}
    <div class="modal-overlay" role="dialog" aria-modal="true" onclick={() => showCapabilityTestModal = false} onkeydown={(e) => e.key === 'Escape' && (showCapabilityTestModal = false)}>
      <div class="wpea-card" style="max-width: 600px; max-height: 90vh; overflow: auto;" onclick={(e) => e.stopPropagation()} role="document">
        <div class="wpea-card__header">
          <h3 class="wpea-card__title">Test Capability: {selectedUser.username}</h3>
          <button
            type="button"
            style="background: none; border: none; padding: 0; min-width: 2rem; font-size: var(--wpea-text--2xl); cursor: pointer; color: var(--wpea-surface--text); line-height: 1;"
            onclick={() => showCapabilityTestModal = false}
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div class="wpea-stack">
          <!-- Filter capabilities -->
          <div class="wpea-field">
            <label for="cap-filter" class="wpea-label">Filter Capabilities</label>
            <input
              id="cap-filter"
              type="search"
              bind:value={capabilitySearchQuery}
              placeholder="Type to filter capabilities..."
              class="wpea-input"
            />
          </div>

          <!-- Capability list -->
          <div class="wpea-field">
            <label class="wpea-label">Select Capability</label>
            <div style="border: 1px solid var(--wpea-surface--border); border-radius: var(--wpea-radius--sm); max-height: 300px; overflow-y: auto;">
              {#if filteredCapabilities.length === 0}
                <div style="padding: var(--wpea-space--md); text-align: center;">
                  <p class="wpea-text-muted">No capabilities found</p>
                </div>
              {:else}
                {#each filteredCapabilities as capability}
                  <button
                    type="button"
                    class="wpea-btn wpea-btn--ghost"
                    style="width: 100%; text-align: left; border-radius: 0; border-bottom: 1px solid var(--wpea-surface--divider); justify-content: flex-start; {selectedCapability === capability ? 'background: var(--wpea-color--primary-l-9); color: var(--wpea-color--primary);' : ''}"
                    onclick={() => {
                      selectedCapability = capability;
                      testResult = null;
                    }}
                  >
                    <code style="font-size: var(--wpea-text--sm);">{capability}</code>
                  </button>
                {/each}
              {/if}
            </div>
          </div>

          {#if selectedCapability}
            <div class="wpea-alert wpea-alert--info">
              <p><strong>Selected:</strong> <code>{selectedCapability}</code></p>
            </div>
            <!-- Test Result -->
            {#if testResult}
              <div class="wpea-alert" class:wpea-alert--success={testResult === 'granted'} class:wpea-alert--danger={testResult === 'denied'} class:wpea-alert--warning={testResult === 'not_set'}>
                <p>
                  <strong>Result:</strong>
                  {#if testResult === 'granted'}
                    ✓ Granted - User has this capability
                  {:else if testResult === 'denied'}
                    ✗ Denied - User does not have this capability
                  {:else}
                    − Not Set - Capability is not assigned to this user
                  {/if}
                </p>
              </div>
            {/if}

            <!-- Action Buttons -->
            <div class="wpea-stack wpea-stack--sm">
              <button
                type="button"
                class="wpea-btn wpea-btn--primary"
                onclick={testUserCan}
              >
                Test user_can()
              </button>

              <div style="border-top: 1px solid var(--wpea-surface--divider); padding-top: var(--wpea-space--sm);">
                <p class="wpea-help" style="margin-bottom: var(--wpea-space--sm);">Generate code snippets (copied to clipboard):</p>

                <div class="wpea-cluster wpea-cluster--sm" style="flex-wrap: wrap;">
                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm"
                    class:wpea-btn--success={copiedButton === 'shortcode'}
                    onclick={generateShortcode}
                  >
                    {copiedButton === 'shortcode' ? 'Copied!' : 'Shortcode'}
                  </button>

                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm"
                    class:wpea-btn--success={copiedButton === 'php'}
                    onclick={generatePHP}
                  >
                    {copiedButton === 'php' ? 'Copied!' : 'PHP'}
                  </button>

                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm"
                    class:wpea-btn--success={copiedButton === 'fetch'}
                    onclick={generateFetch}
                  >
                    {copiedButton === 'fetch' ? 'Copied!' : 'Fetch'}
                  </button>

                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm"
                    class:wpea-btn--success={copiedButton === 'rest'}
                    onclick={generateREST}
                  >
                    {copiedButton === 'rest' ? 'Copied!' : 'REST URL'}
                  </button>
                </div>
              </div>
            </div>
          {/if}
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

/* Success button style for "Copied" state */
.wpea-btn--success {
  background-color: var(--wpea-color--success) !important;
  border-color: var(--wpea-color--success) !important;
  color: white !important;
}

.wpea-btn--success:hover {
  background-color: var(--wpea-color--success-d-2) !important;
  border-color: var(--wpea-color--success-d-2) !important;
}
</style>
