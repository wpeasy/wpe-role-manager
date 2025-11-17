<?php
/**
 * Instructions Page Template
 *
 * @package WP_Easy\RoleManager
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wpe-rm-instructions wpea">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="wpea-stack" style="max-width: 1200px;">
        <!-- Introduction -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('Welcome to Role Manager', 'wp-easy-role-manager'); ?></h2>
            </div>
            <p><?php esc_html_e('Role Manager provides a comprehensive interface for managing WordPress roles, capabilities, and user permissions. This guide will help you understand how to use each feature effectively.', 'wp-easy-role-manager'); ?></p>
        </div>

        <!-- Roles Management -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('1. Roles Management', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Creating a New Role', 'wp-easy-role-manager'); ?></h3>
                    <ol class="wpea-list wpea-list--ordered">
                        <li><?php esc_html_e('Click the "+ Create Role" button', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Enter a unique role slug (lowercase, underscores only, e.g., "content_manager")', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Enter a display name (e.g., "Content Manager")', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Optionally, select an existing role to copy capabilities from', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Create Role" to save', 'wp-easy-role-manager'); ?></li>
                    </ol>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Role Types', 'wp-easy-role-manager'); ?></h3>
                    <div class="wpea-stack wpea-stack--xs">
                        <div class="wpea-cluster wpea-cluster--xs" style="align-items: flex-start;">
                            <span class="badge core"><?php esc_html_e('Core', 'wp-easy-role-manager'); ?></span>
                            <span><?php esc_html_e('WordPress default roles (Administrator, Editor, Author, Contributor, Subscriber) - Cannot be deleted or disabled', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs" style="align-items: flex-start;">
                            <span class="badge external"><?php esc_html_e('External', 'wp-easy-role-manager'); ?></span>
                            <span><?php esc_html_e('Roles created by other plugins or themes - Can be viewed but not managed', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs" style="align-items: flex-start;">
                            <span class="badge badge--primary"><?php esc_html_e('Custom', 'wp-easy-role-manager'); ?></span>
                            <span><?php esc_html_e('Roles created by this plugin - Full control (enable, disable, delete)', 'wp-easy-role-manager'); ?></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Disabling vs Deleting Roles', 'wp-easy-role-manager'); ?></h3>
                    <div class="wpea-stack wpea-stack--xs">
                        <div class="wpea-cluster wpea-cluster--xs" style="align-items: flex-start;">
                            <strong><?php esc_html_e('Disable:', 'wp-easy-role-manager'); ?></strong>
                            <span><?php esc_html_e('Users keep the role assignment, but the role grants no capabilities. Good for temporary deactivation or deprecating roles.', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs" style="align-items: flex-start;">
                            <strong><?php esc_html_e('Delete:', 'wp-easy-role-manager'); ?></strong>
                            <span><?php esc_html_e('Permanently removes the role. If users are assigned to this role, you must migrate them to another role first.', 'wp-easy-role-manager'); ?></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('How Disabled Roles Work', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('When a role is disabled:', 'wp-easy-role-manager'); ?></p>
                    <ul class="wpea-list">
                        <li><?php esc_html_e('Users with the disabled role retain the assignment in the admin area', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('The role is completely invisible to WordPress core, plugins, and themes', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('No capabilities from the disabled role are granted to users', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Role membership checks (like Bricks Builder conditions) return false', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Disabled roles can still be assigned to users with a visual warning', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('In user edit screens, disabled roles display as red badges', 'wp-easy-role-manager'); ?></li>
                    </ul>
                    <div class="wpea-alert wpea-alert--info" style="margin-top: var(--wpea-space--sm);">
                        <p><strong><?php esc_html_e('Use Case:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Disable roles when you want to temporarily revoke access without reassigning users. This is useful for seasonal roles, testing access restrictions, or deprecating roles gradually.', 'wp-easy-role-manager'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capability Management -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('2. Capability Management', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Understanding the Capability Matrix', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('The capability matrix shows all capabilities (rows) and roles (columns). Each cell shows whether a role has that capability.', 'wp-easy-role-manager'); ?></p>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Capability States', 'wp-easy-role-manager'); ?></h3>
                    <div class="wpea-stack wpea-stack--xs">
                        <div class="wpea-cluster wpea-cluster--xs">
                            <span class="badge badge--success"><?php esc_html_e('Granted', 'wp-easy-role-manager'); ?></span>
                            <span><?php esc_html_e('User with this role has the capability', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs">
                            <span class="badge badge--danger"><?php esc_html_e('Denied', 'wp-easy-role-manager'); ?></span>
                            <span><?php esc_html_e('User with this role explicitly does NOT have the capability', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs">
                            <span class="badge"><?php esc_html_e('Unset', 'wp-easy-role-manager'); ?></span>
                            <span><?php esc_html_e('Capability is not assigned to this role', 'wp-easy-role-manager'); ?></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Adding a New Capability', 'wp-easy-role-manager'); ?></h3>
                    <ol class="wpea-list wpea-list--ordered">
                        <li><?php esc_html_e('Click "+ Add Capability"', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Select the role to add it to', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Enter the capability name (e.g., "manage_custom_posts")', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('The "Also add to Administrator" option is enabled by default for safety', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Add Capability"', 'wp-easy-role-manager'); ?></li>
                    </ol>
                </div>

                <div class="wpea-alert wpea-alert--info">
                    <p><strong><?php esc_html_e('Tip:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Use the search and filter options to quickly find specific capabilities in large capability matrices.', 'wp-easy-role-manager'); ?></p>
                </div>
            </div>
        </div>

        <!-- User Management -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('3. User Management', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Assigning Roles to Users', 'wp-easy-role-manager'); ?></h3>
                    <ol class="wpea-list wpea-list--ordered">
                        <li><?php esc_html_e('Find the user in the list (use search if needed)', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Edit Roles"', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Check/uncheck roles - Changes save automatically', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Users can have multiple roles', 'wp-easy-role-manager'); ?></li>
                    </ol>
                    <div class="wpea-alert wpea-alert--warning" style="margin-top: var(--wpea-space--sm);">
                        <p><strong><?php esc_html_e('Disabled Roles:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('When assigning roles on the user edit page, disabled roles appear in red italic text with a warning notice. You can still assign them, but users will not receive any capabilities or role membership from disabled roles.', 'wp-easy-role-manager'); ?></p>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Testing User Capabilities', 'wp-easy-role-manager'); ?></h3>
                    <ol class="wpea-list wpea-list--ordered">
                        <li><?php esc_html_e('Click "Test Capability" for any user', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Filter or select a capability from the list', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Test user_can()" to check if the user has that capability', 'wp-easy-role-manager'); ?></li>
                    </ol>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Code Generation', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('After testing a capability, you can generate code snippets (automatically copied to clipboard):', 'wp-easy-role-manager'); ?></p>
                    <div class="wpea-stack wpea-stack--xs">
                        <div class="wpea-cluster wpea-cluster--xs">
                            <code style="background: var(--wpea-surface--muted); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm);">Shortcode</code>
                            <span><?php esc_html_e('WordPress shortcode to conditionally show content', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs">
                            <code style="background: var(--wpea-surface--muted); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm);">PHP</code>
                            <span><?php esc_html_e('PHP code with multiple templates (Has Capability, Redirect on Login, Restrict Page/Post)', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs">
                            <code style="background: var(--wpea-surface--muted); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm);">Fetch</code>
                            <span><?php esc_html_e('ES6 fetch code to test via REST API', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs">
                            <code style="background: var(--wpea-surface--muted); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm);">REST URL</code>
                            <span><?php esc_html_e('Direct REST API endpoint URL', 'wp-easy-role-manager'); ?></span>
                        </div>
                        <div class="wpea-cluster wpea-cluster--xs">
                            <code style="background: var(--wpea-surface--muted); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm);">Bricks Token</code>
                            <span><?php esc_html_e('Bricks Builder dynamic tag token (if Bricks is active)', 'wp-easy-role-manager'); ?></span>
                        </div>
                    </div>

                    <div class="wpea-alert wpea-alert--warning" style="margin-top: var(--wpea-space--sm);">
                        <p><strong><?php esc_html_e('Important: PHP Hook Timing', 'wp-easy-role-manager'); ?></strong></p>
                        <p><?php esc_html_e('When using the "Redirect on Login" PHP code:', 'wp-easy-role-manager'); ?></p>
                        <ul class="wpea-list">
                            <li><strong><?php esc_html_e('WPCodeBox users:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Set to "Auto-Execute" or "init" hook', 'wp-easy-role-manager'); ?></li>
                            <li><strong><?php esc_html_e('functions.php users:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Code runs automatically on file load', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('The code must run BEFORE the login_redirect hook is called', 'wp-easy-role-manager'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Developer Integrations -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('3.5. Developer Integrations', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Shortcode: [wpe_rm_cap]', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('Use this shortcode to conditionally display content based on user capabilities.', 'wp-easy-role-manager'); ?></p>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Basic Usage:', 'wp-easy-role-manager'); ?></strong>
                        <pre style="background: var(--wpea-surface--muted); padding: var(--wpea-space--sm); border-radius: var(--wpea-radius--sm); overflow-x: auto; margin-top: var(--wpea-space--xs);"><code>[wpe_rm_cap capability="edit_posts" granted="true"]
    This content is only visible to users with edit_posts capability
[/wpe_rm_cap]</code></pre>
                    </div>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Show content when user lacks capability:', 'wp-easy-role-manager'); ?></strong>
                        <pre style="background: var(--wpea-surface--muted); padding: var(--wpea-space--sm); border-radius: var(--wpea-radius--sm); overflow-x: auto; margin-top: var(--wpea-space--xs);"><code>[wpe_rm_cap capability="edit_posts" granted="false"]
    Upgrade to Editor to create posts!
[/wpe_rm_cap]</code></pre>
                    </div>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Check specific user (by ID):', 'wp-easy-role-manager'); ?></strong>
                        <pre style="background: var(--wpea-surface--muted); padding: var(--wpea-space--sm); border-radius: var(--wpea-radius--sm); overflow-x: auto; margin-top: var(--wpea-space--xs);"><code>[wpe_rm_cap capability="manage_options" granted="true" user_id="5"]
    User ID 5 is an administrator
[/wpe_rm_cap]</code></pre>
                    </div>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Attributes:', 'wp-easy-role-manager'); ?></strong>
                        <ul class="wpea-list" style="margin-top: var(--wpea-space--xs);">
                            <li><code>capability</code> <?php esc_html_e('(required) - The capability to check', 'wp-easy-role-manager'); ?></li>
                            <li><code>granted</code> <?php esc_html_e('(optional, default: true) - Show content when user has (true) or lacks (false) the capability', 'wp-easy-role-manager'); ?></li>
                            <li><code>user_id</code> <?php esc_html_e('(optional, default: current user) - Check a specific user by ID', 'wp-easy-role-manager'); ?></li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Bricks Builder Integration', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('If you have Bricks Builder installed, you can use custom conditions and dynamic data tags.', 'wp-easy-role-manager'); ?></p>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Element Conditions:', 'wp-easy-role-manager'); ?></strong>
                        <ul class="wpea-list" style="margin-top: var(--wpea-space--xs);">
                            <li><strong>User Has Capability</strong> - <?php esc_html_e('Check if current user has a capability (dropdown selection)', 'wp-easy-role-manager'); ?></li>
                            <li><strong>Specific User Has Capability</strong> - <?php esc_html_e('Check capability for specific user (format: capability:user_id)', 'wp-easy-role-manager'); ?></li>
                        </ul>
                        <p style="margin-top: var(--wpea-space--xs);" class="wpea-text-muted"><?php esc_html_e('Find these under "Role Manager" group in Bricks element conditions.', 'wp-easy-role-manager'); ?></p>
                    </div>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Dynamic Data Tag:', 'wp-easy-role-manager'); ?></strong>
                        <pre style="background: var(--wpea-surface--muted); padding: var(--wpea-space--sm); border-radius: var(--wpea-radius--sm); overflow-x: auto; margin-top: var(--wpea-space--xs);"><code>{wpe_rm_capability_status:edit_posts}</code></pre>
                        <p style="margin-top: var(--wpea-space--xs);" class="wpea-text-muted"><?php esc_html_e('Returns: "granted", "not-granted", or "denied"', 'wp-easy-role-manager'); ?></p>

                        <strong style="display: block; margin-top: var(--wpea-space--sm);"><?php esc_html_e('For specific user:', 'wp-easy-role-manager'); ?></strong>
                        <pre style="background: var(--wpea-surface--muted); padding: var(--wpea-space--sm); border-radius: var(--wpea-radius--sm); overflow-x: auto; margin-top: var(--wpea-space--xs);"><code>{wpe_rm_capability_status:edit_posts:5}</code></pre>
                    </div>

                    <div class="wpea-alert wpea-alert--info" style="margin-top: var(--wpea-space--sm);">
                        <p><strong><?php esc_html_e('Tip:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Use the "Bricks Token" button in Test Capability to automatically generate the correct token syntax for any capability and user.', 'wp-easy-role-manager'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import/Export -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('4. Import/Export', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Exporting Roles', 'wp-easy-role-manager'); ?></h3>
                    <ol class="wpea-list wpea-list--ordered">
                        <li><?php esc_html_e('Choose "Export all custom roles" or "Select specific roles"', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('If selecting specific roles, check the ones you want to export', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Export Roles"', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Download JSON" to save the file', 'wp-easy-role-manager'); ?></li>
                    </ol>
                    <div class="wpea-alert wpea-alert--info">
                        <p><strong><?php esc_html_e('Note:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Core and external roles are automatically excluded from exports.', 'wp-easy-role-manager'); ?></p>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Importing Roles', 'wp-easy-role-manager'); ?></h3>
                    <ol class="wpea-list wpea-list--ordered">
                        <li><?php esc_html_e('Either upload a JSON file or paste JSON data directly', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Click "Import Roles"', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Existing roles will not be modified; only new roles will be created', 'wp-easy-role-manager'); ?></li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Security -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('5. Security Features', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div class="wpea-alert wpea-alert--warning">
                    <p><strong><?php esc_html_e('Important:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('This plugin implements comprehensive security measures to protect your WordPress site from privilege escalation and code execution vulnerabilities.', 'wp-easy-role-manager'); ?></p>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Administrator-Only Access', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('All role and capability management operations require the "manage_options" capability, which is only available to Administrators (and Super Admins in multisite).', 'wp-easy-role-manager'); ?></p>
                    <ul class="wpea-list">
                        <li><?php esc_html_e('Only administrators can create, modify, or delete roles', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Only administrators can add, remove, or toggle capabilities', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Only administrators can assign roles to users', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Non-administrators cannot access any part of this plugin', 'wp-easy-role-manager'); ?></li>
                    </ul>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Dangerous Capabilities Protection', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('The plugin prevents adding the following dangerous capabilities to any role for security reasons:', 'wp-easy-role-manager'); ?></p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--wpea-space--xs); margin-top: var(--wpea-space--sm);">
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">unfiltered_html</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">unfiltered_upload</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">edit_plugins</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">edit_themes</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">edit_files</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">install_plugins</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">install_themes</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">update_core</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">update_plugins</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">update_themes</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">delete_plugins</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">delete_themes</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">manage_options</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">activate_plugins</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">delete_users</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">create_users</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">promote_users</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">edit_users</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">list_users</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">remove_users</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">switch_themes</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">edit_dashboard</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">customize</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">delete_site</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">import</code>
                        <code style="background: var(--wpea-color--danger-l-9); color: var(--wpea-color--danger); padding: var(--wpea-space--2xs) var(--wpea-space--xs); border-radius: var(--wpea-radius--sm); font-size: var(--wpea-text--sm);">export</code>
                    </div>
                    <p style="margin-top: var(--wpea-space--sm);"><?php esc_html_e('These capabilities can lead to code execution, privilege escalation, or complete site takeover. They are permanently blocked from being added to any role through this plugin.', 'wp-easy-role-manager'); ?></p>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Additional Security Protections', 'wp-easy-role-manager'); ?></h3>
                    <ul class="wpea-list">
                        <li><strong><?php esc_html_e('Self-Modification Prevention:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Administrators cannot modify their own roles to prevent accidental lockout', 'wp-easy-role-manager'); ?></li>
                        <li><strong><?php esc_html_e('Administrator Protection:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Non-super-admins cannot modify administrator accounts', 'wp-easy-role-manager'); ?></li>
                        <li><strong><?php esc_html_e('CSRF Protection:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('All operations are protected with WordPress nonces and referer validation', 'wp-easy-role-manager'); ?></li>
                        <li><strong><?php esc_html_e('Input Sanitization:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('All user input is sanitized and validated before processing', 'wp-easy-role-manager'); ?></li>
                        <li><strong><?php esc_html_e('Core Protection:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('WordPress core roles and capabilities cannot be deleted or modified', 'wp-easy-role-manager'); ?></li>
                    </ul>
                </div>

                <div class="wpea-alert wpea-alert--danger">
                    <p><strong><?php esc_html_e('Security Best Practice:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Never grant powerful capabilities to roles that don\'t need them. If you need to give users additional permissions, create a new role with only the specific capabilities they require, or assign them multiple roles instead of adding dangerous capabilities to existing roles.', 'wp-easy-role-manager'); ?></p>
                </div>
            </div>
        </div>

        <!-- Settings & Content Restrictions -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('5.5. Settings & Content Restrictions', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Plugin Settings', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('Access plugin settings from the Settings tab to customize behavior and enable advanced features.', 'wp-easy-role-manager'); ?></p>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Available Settings:', 'wp-easy-role-manager'); ?></strong>
                        <ul class="wpea-list" style="margin-top: var(--wpea-space--xs);">
                            <li><strong><?php esc_html_e('Appearance:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Color scheme (Light/Dark/Auto) and compact mode for denser UI', 'wp-easy-role-manager'); ?></li>
                            <li><strong><?php esc_html_e('Security:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Control dangerous capability protection and external role deletion', 'wp-easy-role-manager'); ?></li>
                            <li><strong><?php esc_html_e('Performance:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Autosave delay, log retention, and revision retention limits', 'wp-easy-role-manager'); ?></li>
                            <li><strong><?php esc_html_e('Content Restrictions:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Enable metabox on post edit screens for per-content access control', 'wp-easy-role-manager'); ?></li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Content Restrictions Metabox', 'wp-easy-role-manager'); ?></h3>
                    <p><?php esc_html_e('When enabled in Settings, a "Content Restrictions" metabox appears on all Pages, Posts, and Custom Post Types, allowing you to restrict access to individual content items.', 'wp-easy-role-manager'); ?></p>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('Setting Up Restrictions:', 'wp-easy-role-manager'); ?></strong>
                        <ol class="wpea-list wpea-list--ordered" style="margin-top: var(--wpea-space--xs);">
                            <li><?php esc_html_e('Go to Settings → Content Restrictions', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Enable "Enable restrictions metabox on edit screens"', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Edit any Page, Post, or Custom Post Type', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Find the "Content Restrictions" metabox in the sidebar', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Check "Enable restrictions" to activate access control', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Select required capabilities (users must have at least one)', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('For pages: optionally enable "Include child pages" to apply restrictions to all children', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Choose action: "Show Message" or "Redirect to URL"', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Save the post/page', 'wp-easy-role-manager'); ?></li>
                        </ol>
                    </div>

                    <div style="margin-top: var(--wpea-space--sm);">
                        <strong><?php esc_html_e('How Restrictions Work:', 'wp-easy-role-manager'); ?></strong>
                        <ul class="wpea-list" style="margin-top: var(--wpea-space--xs);">
                            <li><?php esc_html_e('Non-logged-in users are always restricted', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Logged-in users must have at least one of the required capabilities', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Administrators (users with manage_options) always have access', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Child page restrictions are inherited from parent when "Include children" is enabled', 'wp-easy-role-manager'); ?></li>
                            <li><?php esc_html_e('Users without access see the configured message or are redirected', 'wp-easy-role-manager'); ?></li>
                        </ul>
                    </div>

                    <div class="wpea-alert wpea-alert--info" style="margin-top: var(--wpea-space--sm);">
                        <p><strong><?php esc_html_e('Tip:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Use the "Test Capability" feature in the Users tab to generate PHP code snippets for custom page/post restrictions and login redirects. All generated code automatically excludes administrators from restrictions.', 'wp-easy-role-manager'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Best Practices -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('6. Best Practices', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Security', 'wp-easy-role-manager'); ?></h3>
                    <ul class="wpea-list">
                        <li><?php esc_html_e('Always keep at least one Administrator user', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Test new roles on a staging site first', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Be cautious when granting powerful capabilities like "delete_users" or "edit_theme_options"', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Use the "Test Capability" feature to verify user permissions before deploying', 'wp-easy-role-manager'); ?></li>
                    </ul>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Workflow', 'wp-easy-role-manager'); ?></h3>
                    <ul class="wpea-list">
                        <li><?php esc_html_e('Create custom roles based on real job functions (e.g., "SEO Manager", "Content Reviewer")', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Use the capability matrix to fine-tune permissions', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Export your custom roles regularly as backups', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Document custom capabilities you create for future reference', 'wp-easy-role-manager'); ?></li>
                    </ul>
                </div>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Maintenance', 'wp-easy-role-manager'); ?></h3>
                    <ul class="wpea-list">
                        <li><?php esc_html_e('Regularly review user role assignments', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Remove or disable unused custom roles', 'wp-easy-role-manager'); ?></li>
                        <li><?php esc_html_e('Use Import/Export to sync roles across multiple sites', 'wp-easy-role-manager'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('7. Troubleshooting', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <div class="wpea-stack wpea-stack--xs">
                    <div>
                        <strong><?php esc_html_e('Q: I can\'t delete a capability', 'wp-easy-role-manager'); ?></strong>
                        <p class="wpea-text-muted"><?php esc_html_e('A: You can only delete capabilities that were added by this plugin. Core and external capabilities are read-only but you can use the toggle to grant/deny them.', 'wp-easy-role-manager'); ?></p>
                    </div>
                    <div>
                        <strong><?php esc_html_e('Q: Changes aren\'t showing in the capability matrix after creating a role', 'wp-easy-role-manager'); ?></strong>
                        <p class="wpea-text-muted"><?php esc_html_e('A: This should now auto-refresh. If issues persist, try refreshing your browser.', 'wp-easy-role-manager'); ?></p>
                    </div>
                    <div>
                        <strong><?php esc_html_e('Q: Can I give a user multiple roles?', 'wp-easy-role-manager'); ?></strong>
                        <p class="wpea-text-muted"><?php esc_html_e('A: Yes! In the Users tab, you can assign multiple roles to any user. The user will have the combined capabilities of all their roles.', 'wp-easy-role-manager'); ?></p>
                    </div>
                    <div>
                        <strong><?php esc_html_e('Q: What happens to users when I delete a role?', 'wp-easy-role-manager'); ?></strong>
                        <p class="wpea-text-muted"><?php esc_html_e('A: If users are assigned to the role you\'re trying to delete, you must first migrate them to another role. The plugin will prevent deletion until all users are reassigned.', 'wp-easy-role-manager'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Logs -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('8. Activity Logs', 'wp-easy-role-manager'); ?></h2>
            </div>

            <div class="wpea-stack wpea-stack--sm">
                <p><?php esc_html_e('The Logs tab tracks all changes made to roles, capabilities, and user assignments.', 'wp-easy-role-manager'); ?></p>

                <div>
                    <h3 class="wpea-heading wpea-heading--sm"><?php esc_html_e('Using Log Filters', 'wp-easy-role-manager'); ?></h3>
                    <ul class="wpea-list">
                        <li><strong><?php esc_html_e('Filter by Action:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Select specific action types (e.g., "Role Created", "Capability Added")', 'wp-easy-role-manager'); ?></li>
                        <li><strong><?php esc_html_e('Filter by Details:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Search log details, action names, or usernames', 'wp-easy-role-manager'); ?></li>
                    </ul>
                </div>

                <div class="wpea-alert wpea-alert--info">
                    <p><strong><?php esc_html_e('Note:', 'wp-easy-role-manager'); ?></strong> <?php esc_html_e('Up to 500 log entries are stored. Older entries are automatically removed. Logs are cleared when the plugin is uninstalled.', 'wp-easy-role-manager'); ?></p>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div class="wpea-card">
            <div class="wpea-card__header">
                <h2 class="wpea-card__title"><?php esc_html_e('9. Need Help?', 'wp-easy-role-manager'); ?></h2>
            </div>

            <p><?php esc_html_e('If you encounter any issues or have questions:', 'wp-easy-role-manager'); ?></p>
            <ul class="wpea-list">
                <li><?php esc_html_e('Check the WordPress Codex for information about specific capabilities', 'wp-easy-role-manager'); ?></li>
                <li><?php esc_html_e('Use the Test Capability feature to verify permissions', 'wp-easy-role-manager'); ?></li>
                <li><?php esc_html_e('Export your current configuration before making major changes', 'wp-easy-role-manager'); ?></li>
                <li><?php esc_html_e('Review the Activity Logs to track recent changes', 'wp-easy-role-manager'); ?></li>
            </ul>
        </div>
    </div>
</div>

<script>
(function() {
    // Apply color scheme from settings
    async function applyColorScheme() {
        try {
            const response = await fetch('<?php echo esc_url(rest_url('wpe-rm/v1/settings')); ?>', {
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            });
            const data = await response.json();
            const colorScheme = data.settings?.color_scheme || 'auto';

            if (colorScheme === 'light') {
                document.documentElement.setAttribute('data-color-scheme', 'light');
            } else if (colorScheme === 'dark') {
                document.documentElement.setAttribute('data-color-scheme', 'dark');
            } else {
                // Auto mode - respect OS preference
                document.documentElement.removeAttribute('data-color-scheme');
            }
        } catch (error) {
            console.error('Failed to load color scheme:', error);
        }
    }

    applyColorScheme();
})();
</script>
