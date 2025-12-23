<script>
/**
 * Incoming Webhooks Documentation Component
 *
 * Shows documentation for the incoming webhook endpoint.
 *
 * @package WP_Easy\RoleManager
 */

import { onMount } from 'svelte';
import { Card, Alert, Badge } from '../../lib/index.ts';

let { store } = $props();

// State
let endpoint = $state('');
let actions = $state({});
let loading = $state(true);
let copiedCode = $state('');

// Fetch incoming actions documentation
async function fetchActions() {
  try {
    loading = true;
    const response = await store.apiRequest('/webhook/incoming/actions');
    actions = response.actions || {};
    endpoint = response.endpoint || '';
  } catch (error) {
    console.error('Error fetching actions:', error);
  } finally {
    loading = false;
  }
}

// Copy code to clipboard
async function copyCode(code, id) {
  try {
    await navigator.clipboard.writeText(code);
    copiedCode = id;
    setTimeout(() => { copiedCode = ''; }, 2000);
  } catch (e) {
    console.error('Failed to copy:', e);
  }
}

// Active code tab for examples
let activeCodeTab = $state('curl');

// Generate code examples for an action
function getCurlExample(actionKey, actionDef) {
  const params = Object.entries(actionDef.params)
    .map(([key, def]) => `    "${key}": "${def.type === 'integer' ? '123' : def.type === 'array' ? '["value1", "value2"]' : 'value'}"`)
    .join(',\n');

  return `curl -X POST "${endpoint}" \\
  -u "username:application_password" \\
  -H "Content-Type: application/json" \\
  -d '{
  "action": "${actionKey}",
  "params": {
${params}
  }
}'`;
}

function getJsExample(actionKey, actionDef) {
  const params = Object.entries(actionDef.params)
    .map(([key, def]) => `      ${key}: ${def.type === 'integer' ? '123' : def.type === 'array' ? '["value1", "value2"]' : '"value"'}`)
    .join(',\n');

  return `const response = await fetch("${endpoint}", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Authorization": "Basic " + btoa("username:application_password")
  },
  body: JSON.stringify({
    action: "${actionKey}",
    params: {
${params}
    }
  })
});

const result = await response.json();
console.log(result);`;
}

function getPhpExample(actionKey, actionDef) {
  const params = Object.entries(actionDef.params)
    .map(([key, def]) => `        '${key}' => ${def.type === 'integer' ? '123' : def.type === 'array' ? "['value1', 'value2']" : "'value'"}`)
    .join(",\n");

  return `<?php
$response = wp_remote_post('${endpoint}', [
    'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode('username:application_password'),
    ],
    'body' => json_encode([
        'action' => '${actionKey}',
        'params' => [
${params}
        ],
    ]),
]);

$body = json_decode(wp_remote_retrieve_body($response), true);
print_r($body);`;
}

// Initialize
onMount(() => {
  fetchActions();
});
</script>

<div class="incoming-docs">
  <Card>
    <h3>Incoming Webhooks</h3>

    <Alert variant="info">
      Incoming webhooks allow external systems (like N8N, Zapier, or Make) to trigger actions in WordPress.
      Authentication uses <strong>WordPress Application Passwords</strong>.
    </Alert>

    <div class="incoming-docs__section">
      <h4>Endpoint</h4>
      <div class="incoming-docs__endpoint">
        <Badge variant="primary">POST</Badge>
        <code>{endpoint || 'Loading...'}</code>
      </div>
    </div>

    <div class="incoming-docs__section">
      <h4>Authentication</h4>
      <p>Use WordPress Application Passwords for authentication. Generate one at:</p>
      <p><strong>Users → Your Profile → Application Passwords</strong></p>
      <p>Pass credentials using HTTP Basic Auth:</p>
      <pre><code>Authorization: Basic base64(username:application_password)</code></pre>
      <Alert variant="warning">
        The authenticating user must have the <code>manage_options</code> capability (Administrator).
      </Alert>
    </div>

    <div class="incoming-docs__section">
      <h4>Request Format</h4>
      <pre><code>{`{
  "action": "action_name",
  "params": {
    "param1": "value1",
    "param2": "value2"
  }
}`}</code></pre>
    </div>
  </Card>

  <Card>
    <h3>Available Actions</h3>

    {#if loading}
      <p>Loading actions...</p>
    {:else}
      <div class="incoming-docs__actions">
        {#each Object.entries(actions) as [actionKey, actionDef]}
          <div class="incoming-docs__action">
            <div class="incoming-docs__action-header">
              <code class="incoming-docs__action-name">{actionKey}</code>
              <span class="incoming-docs__action-desc">{actionDef.description}</span>
            </div>

            <div class="incoming-docs__action-params">
              <strong>Parameters:</strong>
              <table class="incoming-docs__params-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Default</th>
                  </tr>
                </thead>
                <tbody>
                  {#each Object.entries(actionDef.params) as [paramKey, paramDef]}
                    <tr>
                      <td><code>{paramKey}</code></td>
                      <td>{paramDef.type}</td>
                      <td>
                        <Badge variant={paramDef.required ? 'danger' : 'secondary'} size="sm">
                          {paramDef.required ? 'Yes' : 'No'}
                        </Badge>
                      </td>
                      <td>{paramDef.default !== undefined ? String(paramDef.default) : '-'}</td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>

            <div class="incoming-docs__action-examples">
              <div class="incoming-docs__code-tabs">
                <button
                  class="incoming-docs__code-tab"
                  class:active={activeCodeTab === 'curl'}
                  onclick={() => activeCodeTab = 'curl'}
                >cURL</button>
                <button
                  class="incoming-docs__code-tab"
                  class:active={activeCodeTab === 'js'}
                  onclick={() => activeCodeTab = 'js'}
                >JavaScript</button>
                <button
                  class="incoming-docs__code-tab"
                  class:active={activeCodeTab === 'php'}
                  onclick={() => activeCodeTab = 'php'}
                >PHP</button>
              </div>

              <div class="incoming-docs__code-block">
                {#if activeCodeTab === 'curl'}
                  <pre><code>{getCurlExample(actionKey, actionDef)}</code></pre>
                {:else if activeCodeTab === 'js'}
                  <pre><code>{getJsExample(actionKey, actionDef)}</code></pre>
                {:else if activeCodeTab === 'php'}
                  <pre><code>{getPhpExample(actionKey, actionDef)}</code></pre>
                {/if}
                <button
                  class="incoming-docs__copy-btn"
                  onclick={() => copyCode(
                    activeCodeTab === 'curl' ? getCurlExample(actionKey, actionDef) :
                    activeCodeTab === 'js' ? getJsExample(actionKey, actionDef) :
                    getPhpExample(actionKey, actionDef),
                    `${actionKey}-${activeCodeTab}`
                  )}
                >
                  {copiedCode === `${actionKey}-${activeCodeTab}` ? 'Copied!' : 'Copy'}
                </button>
              </div>
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </Card>

  <Card>
    <h3>Rate Limiting</h3>
    <p>Incoming webhooks are rate limited to <strong>100 requests per minute</strong> per IP address.</p>
    <p>If you exceed this limit, you'll receive a <code>429 Too Many Requests</code> response.</p>
  </Card>
</div>

<style>
  .incoming-docs {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .incoming-docs h3 {
    margin: 0 0 var(--wpea-space--md) 0;
    font-size: var(--wpea-text--lg);
    font-weight: 600;
  }

  .incoming-docs h4 {
    margin: 0 0 var(--wpea-space--sm) 0;
    font-size: var(--wpea-text--md);
    font-weight: 600;
  }

  .incoming-docs p {
    margin: 0 0 var(--wpea-space--sm) 0;
    line-height: 1.5;
  }

  .incoming-docs__section {
    margin-bottom: var(--wpea-space--lg);
  }

  .incoming-docs__endpoint {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
  }

  .incoming-docs__endpoint code {
    font-size: var(--wpea-text--sm);
    padding: var(--wpea-space--sm) var(--wpea-space--md);
    background: var(--wpea-color--neutral-l-9);
    border-radius: var(--wpea-radius--md);
  }

  .incoming-docs pre {
    margin: var(--wpea-space--sm) 0;
    padding: var(--wpea-space--md);
    background: var(--wpea-color--neutral-l-9);
    border-radius: var(--wpea-radius--md);
    overflow-x: auto;
    font-size: var(--wpea-text--xs);
    line-height: 1.5;
  }

  .incoming-docs code {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
  }

  .incoming-docs__actions {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xl);
  }

  .incoming-docs__action {
    padding: var(--wpea-space--lg);
    background: var(--wpea-surface--muted);
    border-radius: var(--wpea-radius--md);
  }

  .incoming-docs__action-header {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--md);
    margin-bottom: var(--wpea-space--md);
  }

  .incoming-docs__action-name {
    font-size: var(--wpea-text--md);
    font-weight: 600;
    padding: var(--wpea-space--xs) var(--wpea-space--sm);
    background: var(--wpea-color--primary);
    color: white;
    border-radius: var(--wpea-radius--sm);
  }

  .incoming-docs__action-desc {
    color: var(--wpea-surface--text-muted);
  }

  .incoming-docs__action-params {
    margin-bottom: var(--wpea-space--md);
  }

  .incoming-docs__params-table {
    width: 100%;
    margin-top: var(--wpea-space--sm);
    border-collapse: collapse;
  }

  .incoming-docs__params-table th,
  .incoming-docs__params-table td {
    padding: var(--wpea-space--sm);
    text-align: left;
    border-bottom: 1px solid var(--wpea-surface--border);
  }

  .incoming-docs__params-table th {
    font-weight: 600;
    font-size: var(--wpea-text--sm);
    background: var(--wpea-surface--bg);
  }

  .incoming-docs__params-table td {
    font-size: var(--wpea-text--sm);
  }

  .incoming-docs__action-examples {
    margin-top: var(--wpea-space--md);
  }

  .incoming-docs__code-tabs {
    display: flex;
    gap: var(--wpea-space--xs);
    margin-bottom: var(--wpea-space--sm);
  }

  .incoming-docs__code-tab {
    padding: var(--wpea-space--xs) var(--wpea-space--md);
    font-size: var(--wpea-text--sm);
    background: var(--wpea-surface--bg);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--sm);
    cursor: pointer;
    transition: all 0.2s;
  }

  .incoming-docs__code-tab:hover {
    background: var(--wpea-surface--muted);
  }

  .incoming-docs__code-tab.active {
    background: var(--wpea-color--primary);
    color: white;
    border-color: var(--wpea-color--primary);
  }

  .incoming-docs__code-block {
    position: relative;
    margin-top: var(--wpea-space--sm);
  }

  .incoming-docs__copy-btn {
    position: absolute;
    top: var(--wpea-space--sm);
    right: var(--wpea-space--sm);
    padding: var(--wpea-space--xs) var(--wpea-space--sm);
    font-size: var(--wpea-text--xs);
    background: var(--wpea-surface--panel);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--sm);
    cursor: pointer;
  }

  .incoming-docs__copy-btn:hover {
    background: var(--wpea-surface--bg);
  }
</style>
