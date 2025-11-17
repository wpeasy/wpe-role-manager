<script>
/**
 * Users Tab Component
 *
 * Assign multiple roles to users
 * View effective capabilities per user
 *
 * @package WP_Easy\RoleManager
 */

import { doubleScrollbar } from '../../lib/doubleScrollbar.js';

let { store } = $props();

// Local state
let searchQuery = $state('');
let selectedUser = $state(null);
let showRolesModal = $state(false);
let showCapabilityTestModal = $state(false);
let capabilitySearchQuery = $state('');
let selectedCapability = $state('');
let testResult = $state(null);
let showPhpMenu = $state(false);
let phpMenuOption = $state({
  filterType: 'capability', // 'capability' or 'role'
  restrictChildren: false,
  restrictionType: 'message', // 'message' or 'redirect'
  redirectUrl: ''
});

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
    testResult = response; // Now stores full response including disabled_roles if applicable
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
  // If capability is denied, not_set, or role_disabled, show content when granted="false"
  const grantedParam = testResult?.result === 'granted' ? 'true' : 'false';

  const shortcode = `[wpe_rm_cap capability="${selectedCapability}" granted="${grantedParam}"]Content only visible to users with this capability[/wpe_rm_cap]`;

  try {
    await navigator.clipboard.writeText(shortcode);
    copiedButton = 'shortcode';
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}

// Toggle PHP menu
function togglePhpMenu() {
  showPhpMenu = !showPhpMenu;
}

// Generate "Has Capability" PHP code
async function generatePHPHasCapability() {
  if (!selectedCapability) return;

  const phpCode = `if ( current_user_can( '${selectedCapability}' ) ) {
    // User has the capability
    echo 'User has permission';
} else {
    // User does not have the capability
    echo 'User does not have permission';
}`;

  try {
    await navigator.clipboard.writeText(phpCode);
    copiedButton = 'php';
    showPhpMenu = false;
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}

// Generate "Redirect on Login" PHP code
async function generatePHPRedirectLogin() {
  if (!selectedCapability) return;

  const phpCode = `/**
 * Redirect users on login based on capability
 *
 * IMPORTANT: This code must run BEFORE the login_redirect hook is called.
 * - For WPCodeBox: Set to "Auto-Execute" or "init" hook
 * - For functions.php: Code runs automatically on file load
 * Administrators are excluded from redirection.
 */
add_filter( 'login_redirect', function( $redirect_to, $request, $user ) {
    // Validate $user is a WP_User instance
    if ( ! ( $user instanceof WP_User ) ) {
        return $redirect_to;
    }

    // Administrators always use default redirect - skip custom redirects
    if ( user_can( $user, 'manage_options' ) ) {
        return $redirect_to;
    }

    // Check if user has the capability
    if ( user_can( $user, '${selectedCapability}' ) ) {
        $target = home_url( '/custom-dashboard/' );

        // Only redirect if the page actually exists
        $page = url_to_postid( $target );
        if ( $page ) {
            return $target;
        }
    }

    // Return default redirect for all other users
    return $redirect_to;
}, 20, 3 );`;

  try {
    await navigator.clipboard.writeText(phpCode);
    copiedButton = 'php';
    showPhpMenu = false;
    setTimeout(() => { copiedButton = null; }, 2000);
  } catch (error) {
    console.error('Failed to copy:', error);
  }
}

// Generate "Restrict Page/Post" PHP code
async function generatePHPRestrictPage() {
  if (!selectedCapability) return;

  const restrictionAction = phpMenuOption.restrictionType === 'redirect'
    ? `
            // Redirect to another page
            wp_redirect( '${phpMenuOption.redirectUrl || home_url()}' );
            exit;`
    : `
            // Show restricted message
            wp_die(
                '<h1>Restricted Content</h1><p>You do not have permission to view this page.</p>',
                'Access Denied',
                array( 'response' => 403, 'back_link' => true )
            );`;

  const includeChildrenValue = phpMenuOption.restrictChildren ? 'true' : 'false';
  const filterType = phpMenuOption.filterType;
  const filterLabel = filterType === 'role' ? 'role' : 'capability';

  const accessCheckCode = filterType === 'role'
    ? `
            // Check if user has the required role
            $user = wp_get_current_user();
            if ( ! is_user_logged_in() || ! in_array( $restriction['${filterLabel}'], $user->roles, true ) ) {${restrictionAction}
            }`
    : `
            // Check if user has the required capability
            if ( ! is_user_logged_in() || ! current_user_can( $restriction['${filterLabel}'] ) ) {${restrictionAction}
            }`;

  const phpCode = `/**
 * Restrict pages/posts based on ${filterType === 'role' ? 'roles' : 'capabilities'}
 * Add this to your theme's functions.php or use WPCodeBox (Auto-Execute or "init" hook)
 *
 * IMPORTANT: Non-logged-in users will be restricted by default.
 * Only users with the required ${filterLabel} can access restricted pages.
 * Administrators are always excluded from restrictions.
 */
add_action( 'template_redirect', function() {
    // Administrators always have access - skip restrictions
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    // Define restriction rules: Each rule specifies pages, required ${filterLabel}, and whether to include children
    $restrictions = array(
        array(
            'pages' => array( 123 ), // Replace with your page ID(s)
            '${filterLabel}' => '${selectedCapability}',
            'include_children' => ${includeChildrenValue}
        ),
        // Add more restriction rules as needed:
        // array(
        //     'pages' => array( 456, 789 ),
        //     '${filterLabel}' => '${filterType === 'role' ? 'editor' : 'edit_posts'}',
        //     'include_children' => false
        // ),
    );

    $current_page_id = get_the_ID();

    // Check each restriction rule
    foreach ( $restrictions as $restriction ) {
        $is_restricted = false;

        // Check if current page is directly in the restricted pages array
        if ( in_array( $current_page_id, $restriction['pages'] ) ) {
            $is_restricted = true;
        }

        // Check if current page is a child of any restricted page (if include_children is true)
        if ( ! $is_restricted && $restriction['include_children'] ) {
            foreach ( $restriction['pages'] as $page_id ) {
                $children = get_page_children( $page_id, get_pages() );
                $child_ids = array_column( $children, 'ID' );

                if ( in_array( $current_page_id, $child_ids ) ) {
                    $is_restricted = true;
                    break;
                }
            }
        }

        // If page is restricted, check if user has access
        if ( $is_restricted ) {${accessCheckCode}

            // If user has access, stop checking (they passed this restriction)
            break;
        }
    }
} );`;

  try {
    await navigator.clipboard.writeText(phpCode);
    copiedButton = 'php';
    showPhpMenu = false;
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

// Generate Bricks Builder token and copy to clipboard
async function generateBricks() {
  if (!selectedUser || !selectedCapability) return;

  // Generate token with user ID: {wpe_rm_capability_status:cap_name:user_id}
  const bricksToken = `{wpe_rm_capability_status:${selectedCapability}:${selectedUser.id}}`;

  try {
    await navigator.clipboard.writeText(bricksToken);
    copiedButton = 'bricks';
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
      <div class="wpea-table-wrapper" use:doubleScrollbar>
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
      </div>
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
                  <span class="badge core" style="margin-left: auto;">Core</span>
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
      <div class="wpea-card capability-test-modal" onclick={(e) => e.stopPropagation()} role="document">
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

        <div class="modal-content-scroll">
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
              <div class="wpea-alert" class:wpea-alert--success={testResult.result === 'granted'} class:wpea-alert--danger={testResult.result === 'denied'} class:wpea-alert--warning={testResult.result === 'not_set' || testResult.result === 'role_disabled'}>
                <p>
                  <strong>Result:</strong>
                  {#if testResult.result === 'granted'}
                    ✓ Granted - User has this capability
                    {#if testResult.granting_roles && testResult.granting_roles.length > 0}
                      <br><small>Granted by: {testResult.granting_roles.join(', ')}</small>
                    {/if}
                  {:else if testResult.result === 'denied'}
                    ✗ Denied - User does not have this capability
                  {:else if testResult.result === 'role_disabled'}
                    🚫 Role Disabled - This capability comes from a disabled role
                    {#if testResult.disabled_roles && testResult.disabled_roles.length > 0}
                      <br><small>Disabled roles: {testResult.disabled_roles.join(', ')}</small>
                    {/if}
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

                  <div style="position: relative;">
                    <button
                      type="button"
                      class="wpea-btn wpea-btn--sm"
                      class:wpea-btn--success={copiedButton === 'php'}
                      onclick={togglePhpMenu}
                    >
                      {copiedButton === 'php' ? 'Copied!' : 'PHP'} ▾
                    </button>

                    {#if showPhpMenu}
                      <div class="php-menu">
                        <button
                          type="button"
                          class="php-menu-item"
                          onclick={generatePHPHasCapability}
                        >
                          Has Capability
                        </button>

                        <button
                          type="button"
                          class="php-menu-item"
                          onclick={generatePHPRedirectLogin}
                        >
                          Redirect on Login
                        </button>

                        <div class="php-menu-separator"></div>

                        <div class="php-menu-section">
                          <div class="php-menu-header">Restrict Page/Post</div>

                          <div class="php-menu-radio-group">
                            <label class="php-menu-radio">
                              <input
                                type="radio"
                                name="filterType"
                                value="capability"
                                bind:group={phpMenuOption.filterType}
                              />
                              <span>Filter by Capability</span>
                            </label>

                            <label class="php-menu-radio">
                              <input
                                type="radio"
                                name="filterType"
                                value="role"
                                bind:group={phpMenuOption.filterType}
                              />
                              <span>Filter by Role</span>
                            </label>
                          </div>

                          <label class="php-menu-checkbox">
                            <input
                              type="checkbox"
                              bind:checked={phpMenuOption.restrictChildren}
                            />
                            <span>Restrict all children</span>
                          </label>

                          <div class="php-menu-radio-group">
                            <label class="php-menu-radio">
                              <input
                                type="radio"
                                name="restrictionType"
                                value="message"
                                bind:group={phpMenuOption.restrictionType}
                              />
                              <span>Show restricted message</span>
                            </label>

                            <label class="php-menu-radio">
                              <input
                                type="radio"
                                name="restrictionType"
                                value="redirect"
                                bind:group={phpMenuOption.restrictionType}
                              />
                              <span>Redirect to URL</span>
                            </label>

                            {#if phpMenuOption.restrictionType === 'redirect'}
                              <input
                                type="text"
                                class="wpea-input php-menu-input"
                                placeholder="Enter redirect URL"
                                bind:value={phpMenuOption.redirectUrl}
                              />
                            {/if}
                          </div>

                          <button
                            type="button"
                            class="wpea-btn wpea-btn--sm wpea-btn--primary"
                            style="width: 100%; margin-top: var(--wpea-space--xs);"
                            onclick={generatePHPRestrictPage}
                          >
                            Generate Code
                          </button>
                        </div>
                      </div>
                    {/if}
                  </div>

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

                  <button
                    type="button"
                    class="wpea-btn wpea-btn--sm"
                    class:wpea-btn--success={copiedButton === 'bricks'}
                    onclick={generateBricks}
                  >
                    {copiedButton === 'bricks' ? 'Copied!' : 'Bricks Token'}
                  </button>
                </div>
              </div>
            </div>
          {/if}
          </div>
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

.capability-test-modal {
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: visible;
}

.modal-content-scroll {
  overflow-y: auto;
  overflow-x: visible;
  flex: 1;
  min-height: 0;
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

/* PHP Menu Dropdown */
.php-menu {
  position: absolute;
  bottom: calc(100% + 4px);
  left: 0;
  min-width: 280px;
  max-height: 400px;
  overflow-y: auto;
  background: var(--wpea-surface--panel);
  color: var(--wpea-surface--text);
  border: 1px solid var(--wpea-surface--border);
  border-radius: var(--wpea-radius--md);
  box-shadow: var(--wpea-shadow--l);
  z-index: 10001;
  padding: var(--wpea-space--xs);
}

.php-menu-item {
  width: 100%;
  padding: var(--wpea-space--sm);
  text-align: left;
  background: transparent;
  border: none;
  border-radius: var(--wpea-radius--sm);
  cursor: pointer;
  font-size: var(--wpea-text--sm);
  color: var(--wpea-surface--text);
  transition: background 0.2s;
}

.php-menu-item:hover {
  background: var(--wpea-surface--muted);
}

.php-menu-separator {
  height: 1px;
  background: var(--wpea-surface--border);
  margin: var(--wpea-space--xs) 0;
}

.php-menu-section {
  padding: var(--wpea-space--sm);
}

.php-menu-header {
  font-weight: 600;
  font-size: var(--wpea-text--sm);
  color: var(--wpea-surface--text);
  margin-bottom: var(--wpea-space--sm);
}

.php-menu-checkbox,
.php-menu-radio {
  display: flex;
  align-items: center;
  gap: var(--wpea-space--xs);
  padding: var(--wpea-space--xs) 0;
  cursor: pointer;
  font-size: var(--wpea-text--sm);
  color: var(--wpea-surface--text);
}

.php-menu-checkbox input,
.php-menu-radio input {
  margin: 0;
  cursor: pointer;
  accent-color: var(--wpea-color--primary);
}

.php-menu-radio-group {
  display: flex;
  flex-direction: column;
  gap: var(--wpea-space--xs);
  margin-top: var(--wpea-space--xs);
}

.php-menu-input {
  width: 100%;
  margin-top: var(--wpea-space--xs);
  font-size: var(--wpea-text--sm);
  background: var(--wpea-surface--panel);
  color: var(--wpea-surface--text);
  border: 1px solid var(--wpea-surface--border);
  border-radius: var(--wpea-radius--sm);
  padding: var(--wpea-space--xs);
}

.php-menu-input:focus {
  outline: 2px solid var(--wpea-color--primary);
  outline-offset: 1px;
}
</style>
