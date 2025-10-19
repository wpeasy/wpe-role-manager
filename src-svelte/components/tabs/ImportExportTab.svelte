<script>
/**
 * Import/Export Tab Component
 *
 * Import and export custom roles as JSON
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

let importData = $state('');
let exportData = $state('');
let selectedRoles = $state([]);
let exportAllRoles = $state(true);

// Get custom roles (exclude core and external)
let customRoles = $derived(
  store.roles.filter(role => !role.isCore && !role.isExternal)
);

async function exportRoles() {
  try {
    store.showSaving();

    // Determine which roles to export
    let rolesToExport;
    if (exportAllRoles) {
      rolesToExport = customRoles.map(r => r.slug);
    } else {
      rolesToExport = selectedRoles;
    }

    const data = await store.apiRequest(`/export?roles=${rolesToExport.join(',')}`);
    exportData = JSON.stringify(data.export, null, 2);
    store.showSaved();
  } catch (error) {
    console.error('Error exporting roles:', error);
    store.showError();
  }
}

async function importRoles() {
  if (!importData.trim()) {
    alert('Please enter JSON data to import');
    return;
  }

  try {
    const jsonData = JSON.parse(importData);

    store.showSaving();
    await store.apiRequest('/import', {
      method: 'POST',
      body: JSON.stringify(jsonData),
    });

    await store.fetchRoles();
    store.showSaved();
    importData = '';
  } catch (error) {
    console.error('Error importing roles:', error);
    alert('Invalid JSON or import failed. Please check the format.');
    store.showError();
  }
}

function handleFileUpload(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
    alert('Please upload a JSON file');
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    importData = e.target?.result || '';
  };
  reader.onerror = () => {
    alert('Failed to read file');
  };
  reader.readAsText(file);
}

function downloadExport() {
  if (!exportData) {
    alert('Please export data first');
    return;
  }

  const blob = new Blob([exportData], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `wpe-roles-export-${Date.now()}.json`;
  a.click();
  URL.revokeObjectURL(url);
}
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Import/Export</h2>
    <p class="wpea-text-muted">Import and export custom roles as JSON.</p>
  </div>

  <div class="wpea-grid-2">
    <!-- Export Section -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Export Roles</h3>
      <p class="wpea-text-muted">Export custom roles to JSON format. Core and external roles are automatically excluded.</p>

      <div class="wpea-stack">
        <div class="wpea-field">
          <label class="wpea-control" style="margin: 0;">
            <input
              type="radio"
              name="export-type"
              checked={exportAllRoles}
              onchange={() => exportAllRoles = true}
            />
            <span>Export all custom roles</span>
          </label>
        </div>

        <div class="wpea-field">
          <label class="wpea-control" style="margin: 0;">
            <input
              type="radio"
              name="export-type"
              checked={!exportAllRoles}
              onchange={() => {
                exportAllRoles = false;
                selectedRoles = [];
              }}
            />
            <span>Select specific roles to export</span>
          </label>
        </div>

        {#if !exportAllRoles}
          <div class="wpea-stack wpea-stack--sm" style="padding-left: var(--wpea-space--lg); border-left: 2px solid var(--wpea-surface--border);">
            {#if customRoles.length === 0}
              <p class="wpea-text-muted">No custom roles available to export.</p>
            {:else}
              {#each customRoles as role}
                <label class="wpea-control" style="margin: 0;">
                  <input
                    type="checkbox"
                    value={role.slug}
                    checked={selectedRoles.includes(role.slug)}
                    onchange={(e) => {
                      if (e.target.checked) {
                        selectedRoles = [...selectedRoles, role.slug];
                      } else {
                        selectedRoles = selectedRoles.filter(r => r !== role.slug);
                      }
                    }}
                  />
                  <span>{role.name}</span>
                </label>
              {/each}
            {/if}
          </div>
        {/if}
      </div>

      <button
        type="button"
        class="wpea-btn wpea-btn--primary"
        onclick={exportRoles}
        disabled={!exportAllRoles && selectedRoles.length === 0}
      >
        Export Roles
      </button>

      {#if exportData}
        <div class="wpea-stack wpea-stack--sm">
          <div class="wpea-cluster wpea-cluster--sm" style="justify-content: space-between; align-items: center;">
            <label class="wpea-label">Exported Data:</label>
            <button
              type="button"
              class="wpea-btn wpea-btn--sm"
              onclick={downloadExport}
            >
              Download JSON
            </button>
          </div>
          <textarea
            readonly
            value={exportData}
            rows="10"
            class="wpea-textarea"
            style="font-family: monospace; font-size: var(--wpea-text--sm);"
          ></textarea>
        </div>
      {/if}
    </div>

    <!-- Import Section -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Import Roles</h3>
      <p class="wpea-text-muted">Upload a JSON file or paste JSON data to import custom roles.</p>

      <div class="wpea-field">
        <label for="import-file" class="wpea-label">Upload JSON file:</label>
        <input
          id="import-file"
          type="file"
          accept=".json,application/json"
          class="wpea-input"
          onchange={handleFileUpload}
        />
        <p class="wpea-help">Choose a JSON file exported from this plugin.</p>
      </div>

      <div style="text-align: center; padding: var(--wpea-space--sm) 0; color: var(--wpea-surface--text-muted);">
        — or —
      </div>

      <div class="wpea-field">
        <label for="import-data" class="wpea-label">Paste JSON data:</label>
        <textarea
          id="import-data"
          bind:value={importData}
          placeholder="Paste your JSON data here..."
          rows="10"
          class="wpea-textarea"
          style="font-family: monospace; font-size: var(--wpea-text--sm);"
        ></textarea>
      </div>

      <button
        type="button"
        class="wpea-btn wpea-btn--primary"
        onclick={importRoles}
        disabled={!importData.trim()}
      >
        Import Roles
      </button>

      <div class="wpea-alert wpea-alert--warning">
        <p>
          <strong>Warning:</strong> Importing roles will create new custom roles.
          Existing roles will not be modified. Always backup your data before importing.
        </p>
      </div>
    </div>
  </div>
</div>
