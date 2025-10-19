<script>
/**
 * Settings Tab Component
 *
 * Configure plugin settings
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

let settings = $state({
  rateLimitEnabled: true,
  rateLimitRequests: 30,
  rateLimitWindow: 60,
  autosaveDebounce: 500,
  requiredCapability: 'manage_options',
});

async function saveSettings() {
  try {
    store.showSaving();
    // TODO: Implement settings save API
    await new Promise(resolve => setTimeout(resolve, 500));
    store.showSaved();
  } catch (error) {
    console.error('Error saving settings:', error);
    store.showError();
  }
}
</script>

<div class="wpea-stack" style="max-width: 800px;">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Settings</h2>
    <p class="wpea-text-muted">Configure plugin settings and preferences.</p>
  </div>

  <div class="wpea-stack">
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Rate Limiting</h3>

      <label class="wpea-control">
        <input
          type="checkbox"
          bind:checked={settings.rateLimitEnabled}
          onchange={saveSettings}
        />
        <span>Enable rate limiting for bulk operations</span>
      </label>

      {#if settings.rateLimitEnabled}
        <div class="wpea-field">
          <label for="rate-limit-requests" class="wpea-label">Max Requests:</label>
          <input
            type="number"
            id="rate-limit-requests"
            bind:value={settings.rateLimitRequests}
            min="1"
            max="100"
            onchange={saveSettings}
            class="wpea-input"
            style="max-width: 300px;"
          />
          <p class="wpea-help">Maximum requests allowed per time window.</p>
        </div>

        <div class="wpea-field">
          <label for="rate-limit-window" class="wpea-label">Time Window (seconds):</label>
          <input
            type="number"
            id="rate-limit-window"
            bind:value={settings.rateLimitWindow}
            min="10"
            max="300"
            onchange={saveSettings}
            class="wpea-input"
            style="max-width: 300px;"
          />
          <p class="wpea-help">Time window for rate limiting.</p>
        </div>
      {/if}
    </div>

    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Autosave</h3>

      <div class="wpea-field">
        <label for="autosave-debounce" class="wpea-label">Autosave Delay (ms):</label>
        <input
          type="number"
          id="autosave-debounce"
          bind:value={settings.autosaveDebounce}
          min="100"
          max="5000"
          step="100"
          onchange={saveSettings}
          class="wpea-input"
          style="max-width: 300px;"
        />
        <p class="wpea-help">Delay before auto-saving changes.</p>
      </div>
    </div>

    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Access Control</h3>

      <div class="wpea-field">
        <label for="required-capability" class="wpea-label">Required Capability:</label>
        <select
          id="required-capability"
          bind:value={settings.requiredCapability}
          onchange={saveSettings}
          class="wpea-select"
          style="max-width: 300px;"
        >
          <option value="manage_options">manage_options</option>
          <option value="edit_users">edit_users</option>
          <option value="administrator">administrator</option>
        </select>
        <p class="wpea-help">Minimum capability required to access this plugin.</p>
      </div>
    </div>
  </div>
</div>
