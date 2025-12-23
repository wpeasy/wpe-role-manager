<script lang="ts">
/**
 * Instructions App Component - WP Easy Role Manager
 *
 * Displays plugin instructions using Vertical Tabs.
 *
 * @package WP_Easy\RoleManager
 */

import { VerticalTabs, Tabs, Card, Alert, Badge } from '../../lib/index.ts';

// Sub-tab state for Developer section
let developerSubTab = $state('php');
</script>

{#snippet gettingStarted()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Welcome to Role Manager</h3>
    <p>WP Easy Role Manager provides an easy-to-use interface for managing WordPress roles and capabilities.</p>

    <h4 style="margin-top: var(--wpea-space--md);">Key Features</h4>
    <ul class="wpea-rich-text">
      <li>Create, disable, and delete custom roles</li>
      <li>Manage capabilities across all roles</li>
      <li>Assign multiple roles to users</li>
      <li>View effective capabilities for any user</li>
      <li>Import/export roles and full backups</li>
      <li>Revision history with restore capability</li>
      <li>Content restrictions per page/post</li>
      <li>Bricks Builder & Elementor integration</li>
    </ul>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Quick Start</h3>
    <ol class="wpea-rich-text">
      <li>Go to the <strong>Roles</strong> tab to create custom roles</li>
      <li>Use the <strong>Capabilities</strong> tab to fine-tune permissions</li>
      <li>Assign roles to users in the <strong>Users</strong> tab</li>
      <li>Enable content restrictions in <strong>Settings</strong> if needed</li>
    </ol>
  </Card>
</div>
{/snippet}

{#snippet roleManagement()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Creating a New Role</h3>
    <ol class="wpea-rich-text">
      <li>Click the "+ Create Role" button</li>
      <li>Enter a unique role slug (lowercase, underscores only)</li>
      <li>Enter a display name</li>
      <li>Optionally copy capabilities from an existing role</li>
      <li>Click "Create Role" to save</li>
    </ol>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Role Types</h3>
    <div class="wpea-stack wpea-stack--sm">
      <div class="wpea-cluster" style="align-items: flex-start;">
        <Badge variant="neutral">Core</Badge>
        <span>WordPress default roles (Administrator, Editor, etc.) - Cannot be deleted or disabled</span>
      </div>
      <div class="wpea-cluster" style="align-items: flex-start;">
        <Badge variant="warning">External</Badge>
        <span>Roles created by other plugins/themes - Can be viewed but not managed</span>
      </div>
      <div class="wpea-cluster" style="align-items: flex-start;">
        <Badge variant="primary">Custom</Badge>
        <span>Roles created by this plugin - Full control (enable, disable, delete)</span>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Disabling vs Deleting Roles</h3>
    <div class="wpea-stack wpea-stack--sm">
      <p><strong>Disable:</strong> Users keep the role assignment, but it grants no capabilities. Good for temporary deactivation.</p>
      <p><strong>Delete:</strong> Permanently removes the role. Users must be migrated to another role first.</p>
    </div>

    <Alert variant="info" style="margin-top: var(--wpea-space--md);">
      <strong>How Disabled Roles Work:</strong> When disabled, users retain the assignment but the role is invisible to WordPress, plugins, and themes. No capabilities are granted.
    </Alert>
  </Card>
</div>
{/snippet}

{#snippet capabilityManagement()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Understanding the Capability Matrix</h3>
    <p>The capability matrix shows all capabilities (rows) and roles (columns). Each cell shows whether a role has that capability.</p>

    <h4 style="margin-top: var(--wpea-space--md);">Capability States</h4>
    <div class="wpea-stack wpea-stack--sm">
      <div class="wpea-cluster">
        <Badge variant="success">Granted</Badge>
        <span>User with this role has the capability</span>
      </div>
      <div class="wpea-cluster">
        <Badge variant="danger">Denied</Badge>
        <span>User with this role explicitly does NOT have the capability</span>
      </div>
      <div class="wpea-cluster">
        <Badge variant="neutral">Unset</Badge>
        <span>Capability is not assigned to this role</span>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Adding a New Capability</h3>
    <ol class="wpea-rich-text">
      <li>Click "+ Add Capability"</li>
      <li>Select the role to add it to</li>
      <li>Enter the capability name (e.g., "manage_custom_posts")</li>
      <li>The "Also add to Administrator" option is enabled by default</li>
      <li>Click "Add Capability"</li>
    </ol>

    <Alert variant="info" style="margin-top: var(--wpea-space--md);">
      <strong>Tip:</strong> Use the search and filter options to quickly find specific capabilities.
    </Alert>
  </Card>
</div>
{/snippet}

{#snippet userManagement()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Assigning Roles to Users</h3>
    <ol class="wpea-rich-text">
      <li>Find the user in the list (use search if needed)</li>
      <li>Click "Edit Roles"</li>
      <li>Check/uncheck roles - Changes save automatically</li>
      <li>Users can have multiple roles</li>
    </ol>

    <Alert variant="warning" style="margin-top: var(--wpea-space--md);">
      <strong>Disabled Roles:</strong> When assigning roles, disabled roles appear in red italic text. You can still assign them, but users won't receive capabilities from disabled roles.
    </Alert>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Testing User Capabilities</h3>
    <ol class="wpea-rich-text">
      <li>Click "Test Capability" for any user</li>
      <li>Filter or select a capability from the list</li>
      <li>Click "Test user_can()" to check permissions</li>
    </ol>

    <h4 style="margin-top: var(--wpea-space--md);">Code Generation</h4>
    <p>After testing, generate code snippets (auto-copied to clipboard):</p>
    <ul class="wpea-rich-text">
      <li><code>Shortcode</code> - WordPress shortcode for conditional content</li>
      <li><code>PHP</code> - PHP code with multiple templates</li>
      <li><code>Fetch</code> - ES6 fetch code for REST API</li>
      <li><code>REST URL</code> - Direct REST API endpoint</li>
      <li><code>Bricks Token</code> - Bricks Builder dynamic tag (if active)</li>
    </ul>
  </Card>
</div>
{/snippet}

{#snippet integrations()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Shortcode: [wpe_rm_cap]</h3>
    <p>Conditionally display content based on user capabilities.</p>

    <h4 style="margin-top: var(--wpea-space--md);">Basic Usage</h4>
    <pre class="wpea-rich-text"><code>[wpe_rm_cap capability="edit_posts" granted="true"]
  Content for users with edit_posts
[/wpe_rm_cap]</code></pre>

    <h4 style="margin-top: var(--wpea-space--md);">Show when user lacks capability</h4>
    <pre class="wpea-rich-text"><code>[wpe_rm_cap capability="edit_posts" granted="false"]
  Upgrade to Editor to create posts!
[/wpe_rm_cap]</code></pre>

    <h4 style="margin-top: var(--wpea-space--md);">Attributes</h4>
    <ul class="wpea-rich-text">
      <li><code>capability</code> (required) - The capability to check</li>
      <li><code>granted</code> (optional, default: true) - Show when user has/lacks capability</li>
      <li><code>user_id</code> (optional) - Check a specific user by ID</li>
    </ul>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Bricks Builder Integration</h3>
    <p>If Bricks Builder is installed, use custom conditions and dynamic tags.</p>

    <h4 style="margin-top: var(--wpea-space--md);">Element Conditions</h4>
    <ul class="wpea-rich-text">
      <li><strong>User Has Capability</strong> - Check if current user has a capability</li>
      <li><strong>Specific User Has Capability</strong> - Check for specific user (format: capability:user_id)</li>
    </ul>
    <p class="wpea-text-muted">Find these under "Role Manager" group in element conditions.</p>

    <h4 style="margin-top: var(--wpea-space--md);">Dynamic Data Tag</h4>
    <pre class="wpea-rich-text"><code>{'{'}'wpe_rm_capability_status:edit_posts{'}'}</code></pre>
    <p class="wpea-text-muted">Returns: "granted", "not-granted", or "denied"</p>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Elementor Integration</h3>
    <p>When Elementor is active, add capability conditions to any element.</p>
    <ol class="wpea-rich-text">
      <li>Select any element in Elementor</li>
      <li>Go to Advanced → Visibility</li>
      <li>Enable "Capability Condition"</li>
      <li>Enter the required capability</li>
    </ol>
  </Card>
</div>
{/snippet}

{#snippet importExport()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Exporting Roles</h3>
    <ol class="wpea-rich-text">
      <li>Choose "Export all custom roles" or "Select specific roles"</li>
      <li>If selecting specific roles, check the ones you want</li>
      <li>Click "Export Roles"</li>
      <li>Click "Download JSON" to save</li>
    </ol>

    <Alert variant="info" style="margin-top: var(--wpea-space--md);">
      Core and external roles are automatically excluded from exports.
    </Alert>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Importing Roles</h3>
    <ol class="wpea-rich-text">
      <li>Upload a JSON file or paste JSON data directly</li>
      <li>Click "Import Roles"</li>
      <li>Existing roles will not be modified; only new roles are created</li>
    </ol>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Full Backup</h3>
    <p>Full backups include all custom roles, capabilities, role assignments, and plugin metadata. Use for complete site migration or disaster recovery.</p>
  </Card>
</div>
{/snippet}

{#snippet security()}
<div class="wpea-stack">
  <Alert variant="warning">
    <strong>Important:</strong> This plugin implements comprehensive security measures to protect your site from privilege escalation and code execution vulnerabilities.
  </Alert>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Administrator-Only Access</h3>
    <p>All role and capability management requires the "manage_options" capability.</p>
    <ul class="wpea-rich-text">
      <li>Only administrators can create, modify, or delete roles</li>
      <li>Only administrators can add, remove, or toggle capabilities</li>
      <li>Only administrators can assign roles to users</li>
      <li>Non-administrators cannot access any part of this plugin</li>
    </ul>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Dangerous Capabilities Protection</h3>
    <p>The following capabilities are blocked from being assigned to roles:</p>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--wpea-space--xs); margin-top: var(--wpea-space--sm);">
      <code class="wpea-code--danger">unfiltered_html</code>
      <code class="wpea-code--danger">edit_plugins</code>
      <code class="wpea-code--danger">edit_themes</code>
      <code class="wpea-code--danger">install_plugins</code>
      <code class="wpea-code--danger">install_themes</code>
      <code class="wpea-code--danger">update_core</code>
      <code class="wpea-code--danger">delete_plugins</code>
      <code class="wpea-code--danger">manage_options</code>
      <code class="wpea-code--danger">activate_plugins</code>
      <code class="wpea-code--danger">delete_users</code>
      <code class="wpea-code--danger">create_users</code>
      <code class="wpea-code--danger">promote_users</code>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Additional Protections</h3>
    <ul class="wpea-rich-text">
      <li><strong>Self-Modification Prevention:</strong> Administrators cannot modify their own roles</li>
      <li><strong>Administrator Protection:</strong> Non-super-admins cannot modify admin accounts</li>
      <li><strong>CSRF Protection:</strong> All operations use WordPress nonces</li>
      <li><strong>Input Sanitization:</strong> All user input is validated</li>
      <li><strong>Core Protection:</strong> Core roles and capabilities are read-only</li>
    </ul>
  </Card>
</div>
{/snippet}

{#snippet troubleshooting()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Common Questions</h3>
    <div class="wpea-stack wpea-stack--sm">
      <div>
        <strong>Q: I can't delete a capability</strong>
        <p class="wpea-text-muted">You can only delete capabilities added by this plugin. Core and external capabilities are read-only.</p>
      </div>

      <div>
        <strong>Q: Can I give a user multiple roles?</strong>
        <p class="wpea-text-muted">Yes! In the Users tab, assign multiple roles. The user gets combined capabilities from all roles.</p>
      </div>

      <div>
        <strong>Q: What happens when I delete a role?</strong>
        <p class="wpea-text-muted">If users are assigned to the role, you must migrate them to another role first.</p>
      </div>

      <div>
        <strong>Q: How do disabled roles work?</strong>
        <p class="wpea-text-muted">Users keep the assignment but receive no capabilities. The role is invisible to WordPress and plugins.</p>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Best Practices</h3>
    <ul class="wpea-rich-text">
      <li>Always keep at least one Administrator user</li>
      <li>Test new roles on a staging site first</li>
      <li>Use the "Test Capability" feature to verify permissions</li>
      <li>Export your custom roles regularly as backups</li>
      <li>Create roles based on job functions (e.g., "SEO Manager")</li>
    </ul>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Activity Logs</h3>
    <p>The Logs tab tracks all changes to roles, capabilities, and user assignments.</p>
    <ul class="wpea-rich-text">
      <li><strong>Filter by Action:</strong> Select specific action types</li>
      <li><strong>Filter by Details:</strong> Search log details or usernames</li>
    </ul>
    <Alert variant="info" style="margin-top: var(--wpea-space--sm);">
      Up to 500 log entries are stored. Older entries are automatically removed.
    </Alert>
  </Card>
</div>
{/snippet}

{#snippet phpDocs()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">RoleManager Class</h3>
    <p class="wpea-text-muted">Namespace: <code>WP_Easy\RoleManager\Helpers</code></p>

    <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
      <div class="method-doc">
        <h4><code>RoleManager::get_all_roles()</code></h4>
        <p>Returns all WordPress roles with metadata (core, external, disabled status, user count).</p>
        <pre><code>$roles = \WP_Easy\RoleManager\Helpers\RoleManager::get_all_roles();
// Returns: array of role objects with slug, name, capabilities, isCore, isExternal, disabled, userCount</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::create_role( $slug, $name, $copy_from )</code></h4>
        <p>Creates a new custom role, optionally copying capabilities from an existing role.</p>
        <pre><code>$result = \WP_Easy\RoleManager\Helpers\RoleManager::create_role(
    'content_manager',
    'Content Manager',
    'editor'  // Copy capabilities from Editor role
);
// Returns: WP_Role|false</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::delete_role( $slug )</code></h4>
        <p>Deletes a custom role (non-core only). Returns false if role has users assigned.</p>
        <pre><code>$success = \WP_Easy\RoleManager\Helpers\RoleManager::delete_role( 'content_manager' );
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::is_role_disabled( $slug )</code></h4>
        <p>Checks if a role is currently disabled.</p>
        <pre><code>$is_disabled = \WP_Easy\RoleManager\Helpers\RoleManager::is_role_disabled( 'custom_role' );
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::enable_role( $slug )</code></h4>
        <p>Enables a previously disabled role.</p>
        <pre><code>\WP_Easy\RoleManager\Helpers\RoleManager::enable_role( 'custom_role' );
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::disable_role( $slug )</code></h4>
        <p>Disables a role (users keep assignment but lose capabilities).</p>
        <pre><code>\WP_Easy\RoleManager\Helpers\RoleManager::disable_role( 'custom_role' );
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::export_custom_roles( $roles )</code></h4>
        <p>Exports custom roles to JSON-ready array.</p>
        <pre><code>$export = \WP_Easy\RoleManager\Helpers\RoleManager::export_custom_roles();
// Or export specific roles:
$export = \WP_Easy\RoleManager\Helpers\RoleManager::export_custom_roles( ['custom_role1', 'custom_role2'] );
// Returns: array</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>RoleManager::import_roles( $data )</code></h4>
        <p>Imports roles from exported JSON data.</p>
        <pre><code>$result = \WP_Easy\RoleManager\Helpers\RoleManager::import_roles( $json_data );
// Returns: array with 'success', 'errors', 'messages' keys</code></pre>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">CapabilityManager Class</h3>
    <p class="wpea-text-muted">Namespace: <code>WP_Easy\RoleManager\Helpers</code></p>

    <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
      <div class="method-doc">
        <h4><code>CapabilityManager::get_all_capabilities()</code></h4>
        <p>Returns all capabilities across all roles.</p>
        <pre><code>$caps = \WP_Easy\RoleManager\Helpers\CapabilityManager::get_all_capabilities();
// Returns: array of capability names</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>CapabilityManager::get_capability_matrix()</code></h4>
        <p>Returns a matrix of all capabilities with their status per role.</p>
        <pre><code>$matrix = \WP_Easy\RoleManager\Helpers\CapabilityManager::get_capability_matrix();
// Returns: array with capability, isCore, isExternal, roles (with granted/denied/managed status)</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>CapabilityManager::add_capability( $role_slug, $capability, $grant )</code></h4>
        <p>Adds a capability to a role.</p>
        <pre><code>$success = \WP_Easy\RoleManager\Helpers\CapabilityManager::add_capability(
    'editor',
    'manage_custom_posts',
    true  // Grant the capability
);
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>CapabilityManager::remove_capability( $role_slug, $capability )</code></h4>
        <p>Removes a capability from a role (only plugin-managed capabilities).</p>
        <pre><code>$success = \WP_Easy\RoleManager\Helpers\CapabilityManager::remove_capability(
    'editor',
    'manage_custom_posts'
);
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>CapabilityManager::toggle_capability( $role_slug, $capability, $action )</code></h4>
        <p>Toggles capability state: 'grant', 'deny', or 'unset'.</p>
        <pre><code>$success = \WP_Easy\RoleManager\Helpers\CapabilityManager::toggle_capability(
    'editor',
    'manage_custom_posts',
    'deny'  // 'grant', 'deny', or 'unset'
);
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>CapabilityManager::is_core_capability( $capability )</code></h4>
        <p>Checks if a capability is a WordPress core capability.</p>
        <pre><code>$is_core = \WP_Easy\RoleManager\Helpers\CapabilityManager::is_core_capability( 'edit_posts' );
// Returns: bool</code></pre>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">UserManager Class</h3>
    <p class="wpea-text-muted">Namespace: <code>WP_Easy\RoleManager\Helpers</code></p>

    <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
      <div class="method-doc">
        <h4><code>UserManager::get_users()</code></h4>
        <p>Returns all users with their roles.</p>
        <pre><code>$users = \WP_Easy\RoleManager\Helpers\UserManager::get_users();
// Returns: array of user objects with id, username, email, roles</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>UserManager::get_user( $user_id )</code></h4>
        <p>Returns a single user with roles.</p>
        <pre><code>$user = \WP_Easy\RoleManager\Helpers\UserManager::get_user( 123 );
// Returns: object|null</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>UserManager::update_user_roles( $user_id, $roles )</code></h4>
        <p>Updates a user's roles.</p>
        <pre><code>$success = \WP_Easy\RoleManager\Helpers\UserManager::update_user_roles(
    123,
    ['editor', 'custom_role']
);
// Returns: bool</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>UserManager::get_effective_capabilities( $user_id )</code></h4>
        <p>Returns all capabilities a user has from all their roles.</p>
        <pre><code>$caps = \WP_Easy\RoleManager\Helpers\UserManager::get_effective_capabilities( 123 );
// Returns: array of capability => granted status</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>UserManager::test_user_capability( $user_id, $capability )</code></h4>
        <p>Tests if a user has a specific capability.</p>
        <pre><code>$result = \WP_Easy\RoleManager\Helpers\UserManager::test_user_capability( 123, 'edit_posts' );
// Returns: array with 'result' ('granted', 'denied', 'not_set', 'role_disabled'), 'granting_roles', etc.</code></pre>
      </div>
    </div>
  </Card>
</div>
{/snippet}

{#snippet jsDocs()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">JavaScript Events</h3>
    <p>All events are dispatched on <code>document</code> with the prefix <code>wpe-rm:</code>. Listen using standard DOM events:</p>

    <pre><code>document.addEventListener('wpe-rm:role:created', (event) => {'{'}
  console.log('Role created:', event.detail);
{'}'});</code></pre>

    <h4 style="margin-top: var(--wpea-space--lg);">Role Events</h4>
    <div class="wpea-table-wrapper">
      <table class="wpea-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Payload</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>wpe-rm:role:created</code></td>
            <td><code>{'{'} slug, name, copyFrom? {'}'}</code></td>
            <td>Fired after a new role is created</td>
          </tr>
          <tr>
            <td><code>wpe-rm:role:updated</code></td>
            <td><code>{'{'} slug, changes {'}'}</code></td>
            <td>Fired after a role is modified</td>
          </tr>
          <tr>
            <td><code>wpe-rm:role:deleted</code></td>
            <td><code>{'{'} slug, name {'}'}</code></td>
            <td>Fired after a role is deleted</td>
          </tr>
          <tr>
            <td><code>wpe-rm:role:disabled</code></td>
            <td><code>{'{'} slug {'}'}</code></td>
            <td>Fired when a role is disabled</td>
          </tr>
          <tr>
            <td><code>wpe-rm:role:enabled</code></td>
            <td><code>{'{'} slug {'}'}</code></td>
            <td>Fired when a role is enabled</td>
          </tr>
          <tr>
            <td><code>wpe-rm:roles:updated</code></td>
            <td><em>void</em></td>
            <td>Fired after any role change</td>
          </tr>
        </tbody>
      </table>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">Capability Events</h4>
    <div class="wpea-table-wrapper">
      <table class="wpea-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Payload</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>wpe-rm:capability:added</code></td>
            <td><code>{'{'} role, capability, granted {'}'}</code></td>
            <td>Fired after a capability is added to a role</td>
          </tr>
          <tr>
            <td><code>wpe-rm:capability:removed</code></td>
            <td><code>{'{'} role, capability {'}'}</code></td>
            <td>Fired after a capability is removed from a role</td>
          </tr>
          <tr>
            <td><code>wpe-rm:capability:toggled</code></td>
            <td><code>{'{'} role, capability, action {'}'}</code></td>
            <td>Fired after a capability state is toggled (grant/deny/unset)</td>
          </tr>
          <tr>
            <td><code>wpe-rm:capabilities:updated</code></td>
            <td><em>void</em></td>
            <td>Fired after any capability change</td>
          </tr>
        </tbody>
      </table>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">User Events</h4>
    <div class="wpea-table-wrapper">
      <table class="wpea-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Payload</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>wpe-rm:user:rolesUpdated</code></td>
            <td><code>{'{'} userId, roles, previousRoles {'}'}</code></td>
            <td>Fired after a user's roles are updated</td>
          </tr>
          <tr>
            <td><code>wpe-rm:users:updated</code></td>
            <td><em>void</em></td>
            <td>Fired after any user change</td>
          </tr>
        </tbody>
      </table>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">Other Events</h4>
    <div class="wpea-table-wrapper">
      <table class="wpea-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Payload</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>wpe-rm:revision:restored</code></td>
            <td><code>{'{'} revisionId {'}'}</code></td>
            <td>Fired after a revision is restored</td>
          </tr>
          <tr>
            <td><code>wpe-rm:revision:deleted</code></td>
            <td><code>{'{'} revisionId {'}'}</code></td>
            <td>Fired after a revision is deleted</td>
          </tr>
          <tr>
            <td><code>wpe-rm:settings:saved</code></td>
            <td><code>{'{'} success {'}'}</code></td>
            <td>Fired after settings are saved</td>
          </tr>
          <tr>
            <td><code>wpe-rm:displaySettings:changed</code></td>
            <td><code>Record&lt;string, unknown&gt;</code></td>
            <td>Fired when display/theme settings change</td>
          </tr>
        </tbody>
      </table>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Global JavaScript API</h3>
    <p>The plugin exposes a global <code>window.WPE_RM</code> object with utilities:</p>

    <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
      <div class="method-doc">
        <h4><code>WPE_RM.api</code></h4>
        <p>REST API client for making requests to plugin endpoints.</p>
        <pre><code>// GET request
const roles = await WPE_RM.api.get('/roles');

// POST request
const result = await WPE_RM.api.post('/roles', {'{'} slug: 'new_role', name: 'New Role' {'}'});

// PATCH request
await WPE_RM.api.patch('/roles/custom_role', {'{'} disabled: true {'}'});

// DELETE request
await WPE_RM.api.delete('/roles/custom_role');</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>WPE_RM.emit(event, detail)</code></h4>
        <p>Emit a custom event on the document.</p>
        <pre><code>WPE_RM.emit('custom:event', {'{'} foo: 'bar' {'}'});</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>WPE_RM.on(event, handler)</code></h4>
        <p>Subscribe to an event. Returns an object with <code>unsubscribe()</code> method.</p>
        <pre><code>const subscription = WPE_RM.on('role:created', (detail) => {'{'}
  console.log('New role:', detail.slug);
{'}'});

// Later, unsubscribe:
subscription.unsubscribe();</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>WPE_RM.displaySettings</code></h4>
        <p>Manage display/appearance settings.</p>
        <pre><code>// Get current settings
const settings = WPE_RM.displaySettings.get();

// Update settings
WPE_RM.displaySettings.set({'{'} theme_mode: 'dark' {'}'});

// Reset to defaults
WPE_RM.displaySettings.reset();</code></pre>
      </div>

      <div class="method-doc">
        <h4><code>WPE_RM.i18n</code></h4>
        <p>Internationalized strings passed from PHP.</p>
        <pre><code>const label = WPE_RM.i18n.loading || 'Loading...';</code></pre>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">REST API Endpoints</h3>
    <p>All endpoints require authentication and <code>manage_options</code> capability.</p>
    <p class="wpea-text-muted">Base URL: <code>/wp-json/wpe-rm/v1</code></p>

    <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
      <h4>Roles</h4>
      <div class="wpea-table-wrapper">
        <table class="wpea-table">
          <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
          <tbody>
            <tr><td><Badge variant="success">GET</Badge></td><td><code>/roles</code></td><td>Get all roles</td></tr>
            <tr><td><Badge variant="primary">POST</Badge></td><td><code>/roles</code></td><td>Create a new role</td></tr>
            <tr><td><Badge variant="warning">PATCH</Badge></td><td><code>/roles/{'{'}slug{'}'}</code></td><td>Update a role</td></tr>
            <tr><td><Badge variant="danger">DELETE</Badge></td><td><code>/roles/{'{'}slug{'}'}</code></td><td>Delete a role</td></tr>
          </tbody>
        </table>
      </div>

      <h4>Capabilities</h4>
      <div class="wpea-table-wrapper">
        <table class="wpea-table">
          <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
          <tbody>
            <tr><td><Badge variant="success">GET</Badge></td><td><code>/capabilities</code></td><td>Get all capabilities</td></tr>
            <tr><td><Badge variant="success">GET</Badge></td><td><code>/capabilities/matrix</code></td><td>Get capability matrix</td></tr>
            <tr><td><Badge variant="primary">POST</Badge></td><td><code>/roles/{'{'}slug{'}'}/caps</code></td><td>Add capability to role</td></tr>
            <tr><td><Badge variant="warning">PATCH</Badge></td><td><code>/roles/{'{'}slug{'}'}/caps/{'{'}cap{'}'}</code></td><td>Toggle capability state</td></tr>
            <tr><td><Badge variant="danger">DELETE</Badge></td><td><code>/roles/{'{'}slug{'}'}/caps/{'{'}cap{'}'}</code></td><td>Remove capability</td></tr>
          </tbody>
        </table>
      </div>

      <h4>Users</h4>
      <div class="wpea-table-wrapper">
        <table class="wpea-table">
          <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
          <tbody>
            <tr><td><Badge variant="success">GET</Badge></td><td><code>/users</code></td><td>Get all users with roles</td></tr>
            <tr><td><Badge variant="warning">PATCH</Badge></td><td><code>/users/{'{'}id{'}'}/roles</code></td><td>Update user's roles</td></tr>
            <tr><td><Badge variant="success">GET</Badge></td><td><code>/users/{'{'}id{'}'}/effective-caps</code></td><td>Get user's effective capabilities</td></tr>
            <tr><td><Badge variant="success">GET</Badge></td><td><code>/users/{'{'}id{'}'}/can/{'{'}cap{'}'}</code></td><td>Test if user has capability</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </Card>
</div>
{/snippet}

{#snippet wpDocs()}
<div class="wpea-stack">
  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Action Hooks</h3>
    <p>WordPress action hooks fired during plugin operations. Use <code>add_action()</code> to hook into these.</p>

    <h4 style="margin-top: var(--wpea-space--lg);">Role Actions</h4>
    <div class="wpea-stack wpea-stack--sm">
      <div class="hook-doc">
        <h5><code>wpe_rm_before_role_create</code></h5>
        <p>Fired before a new role is created.</p>
        <pre><code>add_action( 'wpe_rm_before_role_create', function( $slug, $name, $copy_from ) {'{'}
    // Your code here
{'}'}, 10, 3 );</code></pre>
        <p class="wpea-text-muted">Parameters: <code>$slug</code> (string), <code>$name</code> (string), <code>$copy_from</code> (string|null)</p>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_after_role_create</code></h5>
        <p>Fired after a new role is created successfully.</p>
        <pre><code>add_action( 'wpe_rm_after_role_create', function( $slug, $name, $role, $copy_from ) {'{'}
    // Your code here
{'}'}, 10, 4 );</code></pre>
        <p class="wpea-text-muted">Parameters: <code>$slug</code>, <code>$name</code>, <code>$role</code> (WP_Role), <code>$copy_from</code></p>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_before_role_update</code></h5>
        <p>Fired before a role is updated.</p>
        <pre><code>add_action( 'wpe_rm_before_role_update', function( $slug, $params ) {'{'}
    // $params contains: disabled, name, etc.
{'}'}, 10, 2 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_after_role_update</code></h5>
        <p>Fired after a role is updated.</p>
        <pre><code>add_action( 'wpe_rm_after_role_update', function( $slug, $params, $success ) {'{'}
    // Your code here
{'}'}, 10, 3 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_before_role_delete</code></h5>
        <p>Fired before a role is deleted.</p>
        <pre><code>add_action( 'wpe_rm_before_role_delete', function( $slug, $role_data, $user_count ) {'{'}
    // Your code here
{'}'}, 10, 3 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_after_role_delete</code></h5>
        <p>Fired after a role is deleted.</p>
        <pre><code>add_action( 'wpe_rm_after_role_delete', function( $slug, $role_data, $caps_removed ) {'{'}
    // Your code here
{'}'}, 10, 3 );</code></pre>
      </div>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">Capability Actions</h4>
    <div class="wpea-stack wpea-stack--sm">
      <div class="hook-doc">
        <h5><code>wpe_rm_before_capability_add</code> / <code>wpe_rm_after_capability_add</code></h5>
        <p>Fired before/after a capability is added to a role.</p>
        <pre><code>add_action( 'wpe_rm_after_capability_add', function( $role_slug, $capability, $grant ) {'{'}
    // Your code here
{'}'}, 10, 3 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_before_capability_toggle</code> / <code>wpe_rm_after_capability_toggle</code></h5>
        <p>Fired before/after a capability state is toggled.</p>
        <pre><code>add_action( 'wpe_rm_after_capability_toggle', function( $role_slug, $capability, $action, $success ) {'{'}
    // $action is 'grant', 'deny', or 'unset'
{'}'}, 10, 4 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_before_capability_remove</code> / <code>wpe_rm_after_capability_remove</code></h5>
        <p>Fired before/after a capability is removed from a role.</p>
        <pre><code>add_action( 'wpe_rm_after_capability_remove', function( $role_slug, $capability ) {'{'}
    // Your code here
{'}'}, 10, 2 );</code></pre>
      </div>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">User Actions</h4>
    <div class="wpea-stack wpea-stack--sm">
      <div class="hook-doc">
        <h5><code>wpe_rm_before_user_roles_update</code> / <code>wpe_rm_after_user_roles_update</code></h5>
        <p>Fired before/after a user's roles are updated.</p>
        <pre><code>add_action( 'wpe_rm_after_user_roles_update', function( $user_id, $new_roles, $old_roles, $user ) {'{'}
    // $user is the WP_User object
{'}'}, 10, 4 );</code></pre>
      </div>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">Import/Export Actions</h4>
    <div class="wpea-stack wpea-stack--sm">
      <div class="hook-doc">
        <h5><code>wpe_rm_before_import</code> / <code>wpe_rm_after_import</code></h5>
        <p>Fired before/after roles are imported.</p>
        <pre><code>add_action( 'wpe_rm_after_import', function( $imported_count, $caps_count, $errors, $messages ) {'{'}
    // Your code here
{'}'}, 10, 4 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_before_export</code></h5>
        <p>Fired before export data is returned.</p>
        <pre><code>add_action( 'wpe_rm_before_export', function( $export_data, $export_type ) {'{'}
    // $export_type is 'full' or 'roles'
{'}'}, 10, 2 );</code></pre>
      </div>
    </div>

    <h4 style="margin-top: var(--wpea-space--lg);">Settings & Revision Actions</h4>
    <div class="wpea-stack wpea-stack--sm">
      <div class="hook-doc">
        <h5><code>wpe_rm_before_settings_update</code> / <code>wpe_rm_after_settings_update</code></h5>
        <p>Fired before/after plugin settings are updated.</p>
        <pre><code>add_action( 'wpe_rm_after_settings_update', function( $settings, $old_settings, $changes ) {'{'}
    // $changes is array of human-readable change descriptions
{'}'}, 10, 3 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_before_revision_restore</code> / <code>wpe_rm_after_revision_restore</code></h5>
        <p>Fired before/after a revision is restored.</p>
        <pre><code>add_action( 'wpe_rm_after_revision_restore', function( $revision_id, $revision ) {'{'}
    // Your code here
{'}'}, 10, 2 );</code></pre>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Filter Hooks</h3>
    <p>WordPress filter hooks to modify plugin behavior. Use <code>add_filter()</code> to hook into these.</p>

    <div class="wpea-stack wpea-stack--sm" style="margin-top: var(--wpea-space--md);">
      <div class="hook-doc">
        <h5><code>wpe_rm_role_creation_data</code></h5>
        <p>Filter role data before creation. Useful for validation or auto-modification.</p>
        <pre><code>add_filter( 'wpe_rm_role_creation_data', function( $data, $slug, $name ) {'{'}
    // Auto-prefix role slugs
    $data['slug'] = 'myprefix_' . $data['slug'];
    return $data;
{'}'}, 10, 3 );</code></pre>
        <p class="wpea-text-muted">Parameters: <code>$data</code> (array), <code>$slug</code>, <code>$name</code></p>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_allow_role_deletion</code></h5>
        <p>Filter to allow or prevent role deletion. Return <code>false</code> to block.</p>
        <pre><code>add_filter( 'wpe_rm_allow_role_deletion', function( $allow, $slug, $role_data, $user_count ) {'{'}
    // Prevent deletion of roles starting with 'protected_'
    if ( strpos( $slug, 'protected_' ) === 0 ) {'{'}
        return false;
    {'}'}
    return $allow;
{'}'}, 10, 4 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_dangerous_capabilities</code></h5>
        <p>Filter the list of dangerous capabilities that are blocked from assignment.</p>
        <pre><code>add_filter( 'wpe_rm_dangerous_capabilities', function( $caps ) {'{'}
    // Add custom capability to blocked list
    $caps[] = 'my_dangerous_cap';
    return $caps;
{'}'} );

// Or remove from blocked list (use with caution!)
add_filter( 'wpe_rm_dangerous_capabilities', function( $caps ) {'{'}
    return array_diff( $caps, ['unfiltered_html'] );
{'}'} );</code></pre>
        <Alert variant="danger" style="margin-top: var(--wpea-space--sm);">
          <strong>Warning:</strong> Removing capabilities from the blocked list can create security vulnerabilities!
        </Alert>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_user_roles</code></h5>
        <p>Filter roles before assigning to a user.</p>
        <pre><code>add_filter( 'wpe_rm_user_roles', function( $roles, $user_id, $user, $old_roles ) {'{'}
    // Always ensure subscriber role is present
    if ( ! in_array( 'subscriber', $roles ) ) {'{'}
        $roles[] = 'subscriber';
    {'}'}
    return $roles;
{'}'}, 10, 4 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_import_data</code></h5>
        <p>Filter import data before processing.</p>
        <pre><code>add_filter( 'wpe_rm_import_data', function( $data, $is_full_backup ) {'{'}
    // Modify role names during import
    foreach ( $data['roles'] as &$role ) {'{'}
        $role['name'] = '[Imported] ' . $role['name'];
    {'}'}
    return $data;
{'}'}, 10, 2 );</code></pre>
      </div>

      <div class="hook-doc">
        <h5><code>wpe_rm_export_data</code></h5>
        <p>Filter export data before returning.</p>
        <pre><code>add_filter( 'wpe_rm_export_data', function( $data, $export_type ) {'{'}
    // Add metadata to exports
    $data['exported_by'] = get_current_user_id();
    $data['site_name'] = get_bloginfo( 'name' );
    return $data;
{'}'}, 10, 2 );</code></pre>
      </div>
    </div>
  </Card>

  <Card>
    <h3 class="wpea-heading wpea-heading--sm">Default Dangerous Capabilities</h3>
    <p>These capabilities are blocked from being assigned to roles by default:</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--wpea-space--xs); margin-top: var(--wpea-space--sm);">
      <code class="wpea-code--danger">unfiltered_html</code>
      <code class="wpea-code--danger">edit_plugins</code>
      <code class="wpea-code--danger">edit_themes</code>
      <code class="wpea-code--danger">edit_files</code>
      <code class="wpea-code--danger">install_plugins</code>
      <code class="wpea-code--danger">install_themes</code>
      <code class="wpea-code--danger">update_plugins</code>
      <code class="wpea-code--danger">update_themes</code>
      <code class="wpea-code--danger">update_core</code>
      <code class="wpea-code--danger">delete_plugins</code>
      <code class="wpea-code--danger">delete_themes</code>
      <code class="wpea-code--danger">manage_options</code>
      <code class="wpea-code--danger">activate_plugins</code>
      <code class="wpea-code--danger">delete_users</code>
      <code class="wpea-code--danger">create_users</code>
      <code class="wpea-code--danger">promote_users</code>
      <code class="wpea-code--danger">remove_users</code>
      <code class="wpea-code--danger">edit_users</code>
      <code class="wpea-code--danger">list_users</code>
      <code class="wpea-code--danger">export</code>
      <code class="wpea-code--danger">import</code>
      <code class="wpea-code--danger">switch_themes</code>
      <code class="wpea-code--danger">customize</code>
      <code class="wpea-code--danger">manage_network</code>
      <code class="wpea-code--danger">manage_sites</code>
      <code class="wpea-code--danger">manage_network_users</code>
    </div>

    <Alert variant="info" style="margin-top: var(--wpea-space--md);">
      <strong>Note:</strong> Use the <code>wpe_rm_dangerous_capabilities</code> filter to modify this list, or enable "Allow assigning dangerous capabilities" in Settings.
    </Alert>
  </Card>
</div>
{/snippet}

{#snippet developer()}
<div class="wpea-stack">
  <Alert variant="info">
    <strong>Developer Documentation</strong> — Reference for integrating with WP Easy Role Manager via PHP, JavaScript, or WordPress hooks.
  </Alert>

  <Tabs
    tabs={[
      { id: 'php', label: 'PHP Classes', content: phpDocs },
      { id: 'javascript', label: 'JavaScript API', content: jsDocs },
      { id: 'wordpress', label: 'WordPress Hooks', content: wpDocs },
    ]}
    bind:activeTab={developerSubTab}
  />
</div>
{/snippet}

<div class="wpea-stack">
  <div class="wpea-cluster" style="justify-content: space-between; align-items: center;">
    <h2 class="wpea-heading wpea-heading--md">Instructions</h2>
  </div>

  <VerticalTabs tabs={[
    { id: 'getting-started', label: 'Getting Started', content: gettingStarted },
    { id: 'roles', label: 'Role Management', content: roleManagement },
    { id: 'capabilities', label: 'Capabilities', content: capabilityManagement },
    { id: 'users', label: 'User Management', content: userManagement },
    { id: 'integrations', label: 'Integrations', content: integrations },
    { id: 'import-export', label: 'Import/Export', content: importExport },
    { id: 'security', label: 'Security', content: security },
    { id: 'troubleshooting', label: 'Troubleshooting', content: troubleshooting },
    { id: 'developer', label: 'Developer', content: developer },
  ]} />
</div>

<style>
  :global(.wpe-rm-instructions) .wpea-code--danger {
    background: light-dark(var(--wpea-color--danger-l-9), color-mix(in oklab, var(--wpea-color--danger), transparent 70%));
    color: var(--wpea-color--danger);
    padding: calc(var(--wpea-space--xs) * 0.5) var(--wpea-space--xs);
    border-radius: var(--wpea-radius--sm);
    font-size: var(--wpea-text--sm);
  }

  /* Method and Hook Documentation Styles */
  :global(.wpe-rm-instructions) .method-doc,
  :global(.wpe-rm-instructions) .hook-doc {
    padding: var(--wpea-space--md);
    background: var(--wpea-surface--muted);
    border-radius: var(--wpea-radius--md);
    border-left: 3px solid var(--wpea-color--primary);
  }

  :global(.wpe-rm-instructions) .method-doc h4,
  :global(.wpe-rm-instructions) .hook-doc h5 {
    margin: 0 0 var(--wpea-space--xs) 0;
    font-size: var(--wpea-text--sm);
    font-weight: 600;
    color: var(--wpea-color--primary);
  }

  :global(.wpe-rm-instructions) .method-doc p,
  :global(.wpe-rm-instructions) .hook-doc p {
    margin: 0 0 var(--wpea-space--sm) 0;
    font-size: var(--wpea-text--sm);
  }

  :global(.wpe-rm-instructions) .method-doc code,
  :global(.wpe-rm-instructions) .hook-doc code {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
    font-size: inherit;
  }

  /* Table wrapper overflow */
  :global(.wpe-rm-instructions) .wpea-table-wrapper {
    overflow-x: auto;
  }

  /* All pre blocks - override WPEA framework */
  :global(.wpe-rm-instructions) pre,
  :global(.wpe-rm-instructions) .wpea-rich-text pre,
  :global(.wpe-rm-instructions) .method-doc pre,
  :global(.wpe-rm-instructions) .hook-doc pre {
    margin: var(--wpea-space--sm) 0 0 0;
    padding: var(--wpea-space--sm);
    background: var(--wpea-color--neutral-l-9) !important;
    border-radius: var(--wpea-radius--sm);
    overflow-x: auto;
    font-size: var(--wpea-text--xs);
    line-height: 1.5;
    color: var(--wpea-surface--text) !important;
  }

  :global(.wpe-rm-instructions) code {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
  }

  /* Inline code in paragraphs, lists, tables */
  :global(.wpe-rm-instructions) p code,
  :global(.wpe-rm-instructions) li code,
  :global(.wpe-rm-instructions) td code,
  :global(.wpe-rm-instructions) .wpea-table code {
    font-size: var(--wpea-text--xs);
    padding: calc(var(--wpea-space--xs) * 0.25) calc(var(--wpea-space--xs) * 0.5);
    background: var(--wpea-color--neutral-l-8) !important;
    border-radius: var(--wpea-radius--xs);
    color: var(--wpea-surface--text) !important;
  }
</style>
