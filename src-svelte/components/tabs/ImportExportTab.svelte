<script>
/**
 * Import/Export Tab Component
 *
 * Import and export custom roles as JSON
 * Full backup/restore functionality for all custom data
 *
 * @package WP_Easy\RoleManager
 */

let { store } = $props();

let importData = $state('');
let exportData = $state('');
let selectedRoles = $state([]);
let exportAllRoles = $state(true);
let exportType = $state('roles'); // 'roles' or 'full-backup'

// Get custom roles (exclude core and external)
let customRoles = $derived(
  store.roles.filter(role => !role.isCore && !role.isExternal)
);

async function exportRoles() {
  try {
    store.showSaving();

    let data;
    if (exportType === 'full-backup') {
      // Full backup includes all custom data
      data = await store.apiRequest('/export?type=full');
    } else {
      // Roles only export
      let rolesToExport;
      if (exportAllRoles) {
        rolesToExport = customRoles.map(r => r.slug);
      } else {
        rolesToExport = selectedRoles;
      }
      data = await store.apiRequest(`/export?roles=${rolesToExport.join(',')}`);
    }

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

    // Check if this is a full backup
    const isFullBackup = jsonData.backup_type === 'full' && jsonData.version;

    if (isFullBackup) {
      if (!confirm('This appears to be a full backup. This will restore all custom roles, capabilities, and role assignments. Continue?')) {
        return;
      }
    }

    store.showSaving();
    await store.apiRequest('/import', {
      method: 'POST',
      body: JSON.stringify(jsonData),
    });

    // Refresh all data
    await Promise.all([
      store.fetchRoles(),
      store.fetchCapabilityMatrix(),
      store.fetchUsers(),
    ]);

    store.showSaved();
    importData = '';

    if (isFullBackup) {
      alert('Full backup restored successfully! All custom roles, capabilities, and assignments have been imported.');
    }
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

  // Use different filename based on export type
  const timestamp = new Date().toISOString().split('T')[0];
  if (exportType === 'full-backup') {
    a.download = `wpe-role-manager-backup-${timestamp}.json`;
  } else {
    a.download = `wpe-roles-export-${Date.now()}.json`;
  }

  a.click();
  URL.revokeObjectURL(url);
}
</script>

<div class="wpea-stack">
  <!-- Header -->
  <div class="wpea-stack wpea-stack--sm">
    <h2 class="wpea-heading wpea-heading--md">Import/Export</h2>
    <p class="wpea-text-muted">Export custom roles or create a full backup. Import to restore data.</p>
  </div>

  <div class="wpea-grid-2">
    <!-- Export Section -->
    <div class="wpea-card">
      <h3 class="wpea-heading wpea-heading--sm">Export</h3>
      <p class="wpea-text-muted">Export custom roles or create a complete backup of all custom data.</p>

      <div class="wpea-stack">
        <!-- Export Type Selection -->
        <div class="wpea-field">
          <label class="wpea-label">Export Type:</label>
          <div class="wpea-stack wpea-stack--xs">
            <label class="wpea-control" style="margin: 0;">
              <input
                type="radio"
                name="export-mode"
                value="roles"
                bind:group={exportType}
              />
              <span><strong>Roles Only</strong> - Export selected custom roles</span>
            </label>
            <label class="wpea-control" style="margin: 0;">
              <input
                type="radio"
                name="export-mode"
                value="full-backup"
                bind:group={exportType}
              />
              <span><strong>Full Backup</strong> - All custom roles, capabilities, and role assignments</span>
            </label>
          </div>
        </div>

        {#if exportType === 'full-backup'}
          <div class="wpea-alert wpea-alert--info">
            <p><strong>Full Backup includes:</strong></p>
            <ul style="margin: var(--wpea-space--xs) 0 0 var(--wpea-space--md); padding: 0;">
              <li>All custom roles and their configurations</li>
              <li>All custom capabilities and their assignments</li>
              <li>Plugin-managed capability assignments for all roles</li>
              <li>Metadata for proper restoration</li>
            </ul>
            <p style="margin-top: var(--wpea-space--xs);"><strong>Core and external data are excluded.</strong></p>
          </div>
        {:else}
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
        {/if}
      </div>

      <button
        type="button"
        class="wpea-btn wpea-btn--primary"
        onclick={exportRoles}
        disabled={exportType === 'roles' && !exportAllRoles && selectedRoles.length === 0}
      >
        {exportType === 'full-backup' ? 'Create Full Backup' : 'Export Roles'}
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
      <h3 class="wpea-heading wpea-heading--sm">Import / Restore</h3>
      <p class="wpea-text-muted">Upload a JSON file or paste JSON data to import custom roles or restore from a full backup.</p>

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
        Import / Restore
      </button>

      <div class="wpea-alert wpea-alert--success">
        <p><strong>Smart Import:</strong> This plugin automatically detects whether you're importing roles only or restoring from a full backup, and handles each appropriately.</p>
      </div>

      <div class="wpea-alert wpea-alert--warning">
        <p>
          <strong>Warning:</strong> Importing will create new custom roles and capabilities.
          Existing roles will not be modified. Full backups will restore all custom data.
          Always backup your data before importing.
        </p>
      </div>
    </div>
  </div>
</div>
