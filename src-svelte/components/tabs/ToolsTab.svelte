<script>
/**
 * Tools Tab Component
 *
 * Administrative tools and utilities
 *
 * @package WP_Easy\RoleManager
 */

import { Card, Button, Alert } from '../../lib/index.ts';

let { store } = $props();

// Double opt-in state for reset core roles
let pendingReset = $state(false);
let resetTimeout = $state(null);

function initiateResetCoreRoles() {
  // Clear any existing pending action
  clearPendingReset();

  // Set pending action
  pendingReset = true;
  resetTimeout = setTimeout(() => {
    pendingReset = false;
  }, 4000);
}

function clearPendingReset() {
  if (resetTimeout) {
    clearTimeout(resetTimeout);
  }
  pendingReset = false;
  resetTimeout = null;
}

async function confirmResetCoreRoles() {
  if (!pendingReset) {
    return;
  }

  clearPendingReset();

  try {
    store.showSaving();
    await store.apiRequest('/tools/reset-core-roles', {
      method: 'POST',
    });
    store.showSaved();

    // Refresh roles data
    await store.fetchRoles();
    await store.fetchCapabilityMatrix();

    alert('Core roles have been reset to WordPress defaults. Any custom capabilities will need to be reassigned.');
  } catch (error) {
    console.error('Error resetting core roles:', error);
    store.showError();
    alert('Failed to reset core roles: ' + error.message);
  }
}
</script>

<div class="wpea-stack" style="max-width: 800px;">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Tools</h2>
    <p class="wpea-text-muted">Administrative tools and utilities for managing roles and capabilities.</p>
  </div>

  <!-- Reset Core Roles Tool -->
  <Card>
    {#snippet children()}
    <div class="wpea-stack">
      <div>
        <h3 class="wpea-heading wpea-heading--sm">Reset Core Roles</h3>
        <p class="wpea-text-muted">Reset all WordPress core roles (Administrator, Editor, Author, Contributor, Subscriber) to their default capabilities.</p>
      </div>

      <Alert variant="warning">
        {#snippet children()}
        <p><strong>Warning:</strong> This action will reset all core roles to their WordPress default capabilities.</p>
        <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
          <li>All core roles will be restored to their default state</li>
          <li>Any custom capabilities added to core roles will be removed</li>
          <li>Custom roles will not be affected</li>
          <li>A revision will be saved before resetting, allowing you to restore if needed</li>
        </ul>
        <p style="margin-top: var(--wpea-space--xs);"><strong>Note:</strong> After resetting, you will need to reassign any custom capabilities to core roles that you had previously added.</p>
        {/snippet}
      </Alert>

      <div>
        {#if pendingReset}
          <Button variant="danger" onclick={confirmResetCoreRoles}>
            Confirm Reset Core Roles
          </Button>
        {:else}
          <Button variant="danger-outline" onclick={initiateResetCoreRoles}>
            Reset Core Roles to Defaults
          </Button>
        {/if}

        {#if pendingReset}
          <p class="wpea-text-muted" style="font-size: var(--wpea-text--sm); margin-top: var(--wpea-space--xs);">
            Click "Confirm Reset Core Roles" again within 4 seconds to proceed with the reset.
          </p>
        {/if}
      </div>
    </div>
    {/snippet}
  </Card>

  <!-- Future tools can be added here -->
  <Card style="background: var(--wpea-surface--muted); border-style: dashed;">
    {#snippet children()}
    <div style="text-align: center; padding: var(--wpea-space--md);">
      <p class="wpea-text-muted">More tools coming soon...</p>
    </div>
    {/snippet}
  </Card>
</div>
