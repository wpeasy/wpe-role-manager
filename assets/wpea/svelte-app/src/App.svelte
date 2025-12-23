<script lang="ts">
  import { createRawSnippet } from 'svelte';

  // Component imports
  import Alert from './lib/Alert.svelte';
  import Badge from './lib/Badge.svelte';
  import Button from './lib/Button.svelte';
  import Card from './lib/Card.svelte';
  import Switch from './lib/Switch.svelte';
  import Input from './lib/Input.svelte';
  import NumberInput from './lib/NumberInput.svelte';
  import Select from './lib/Select.svelte';
  import Textarea from './lib/Textarea.svelte';
  import Radio from './lib/Radio.svelte';
  import Range from './lib/Range.svelte';
  import MultiSelect from './lib/MultiSelect.svelte';
  import AdvancedSelect from './lib/AdvancedSelect.svelte';
  import Panel from './lib/Panel.svelte';
  import Tabs from './lib/Tabs.svelte';
  import VerticalTabs from './lib/VerticalTabs.svelte';
  import Accordion from './lib/Accordion.svelte';
  import Table from './lib/Table.svelte';
  import Stack from './lib/Stack.svelte';
  import Cluster from './lib/Cluster.svelte';
  import Modal from './lib/Modal.svelte';
  import Toggle3State from './lib/Toggle3State.svelte';
  import DoubleOptInButton from './lib/DoubleOptInButton.svelte';
  import Popover from './lib/Popover.svelte';
  import FrameworkSettings from './lib/FrameworkSettings.svelte';

  // State and constants
  import {
    state, derived,
    radioOptions, frameworkOptions,
    tableColumns, tableData,
    defaultTabs, primaryTabs, secondaryTabs,
    defaultAccordion, primaryAccordion, secondaryAccordion,
    setTheme, toggleDensity, toggleSidebar, showToast,
    handleAdvancedSelectCreate, handleAdvancedSelectDelete, handleAdvancedSelectLock
  } from './state.svelte';

  function handleOverlayKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleSidebar();
    }
  }

  // Intersection observer for active section tracking
  $effect(() => {
    const observerOptions = {
      root: null,
      rootMargin: '-80px 0px -60% 0px',
      threshold: 0
    };

    const observerCallback = (entries: IntersectionObserverEntry[]) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const id = entry.target.getAttribute('id');
          if (id) state.activeSection = id;
        }
      });
    };

    const observer = new IntersectionObserver(observerCallback, observerOptions);
    const sections = document.querySelectorAll('.demo-section');
    sections.forEach(section => observer.observe(section));

    return () => {
      observer.disconnect();
    };
  });

  // AdvancedSelect event listeners
  $effect(() => {
    document.addEventListener('AdvancedSelect:create', handleAdvancedSelectCreate as EventListener);
    document.addEventListener('AdvancedSelect:delete', handleAdvancedSelectDelete as EventListener);
    document.addEventListener('AdvancedSelect:lock', handleAdvancedSelectLock as EventListener);

    return () => {
      document.removeEventListener('AdvancedSelect:create', handleAdvancedSelectCreate as EventListener);
      document.removeEventListener('AdvancedSelect:delete', handleAdvancedSelectDelete as EventListener);
      document.removeEventListener('AdvancedSelect:lock', handleAdvancedSelectLock as EventListener);
    };
  });
</script>

<div class="wpea wpea-full wpea-cq demo-shell" style="--wpea-density: {state.density}; font-size: {state.density === 'compact' ? 'calc(var(--wpea-fs-base) * 0.875)' : 'var(--wpea-fs-base)'}; font-family: var(--wpea-font-sans);">
  <!-- HEADER -->
  <header class="demo-header">
    <Cluster>
      <button
        class="hamburger {state.sidebarOpen ? 'active' : ''}"
        onclick={toggleSidebar}
        aria-label="Toggle menu"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
      <h1 class="wpea-text-xl" style="margin: 0; font-weight: 600;">WPE_Admin</h1>
      <Badge variant="primary">Svelte 5</Badge>
    </Cluster>
    <Cluster>
      <Button variant="ghost" onclick={toggleDensity} class="wpea-hide-md-down">
        Compact
      </Button>
      <Button variant="secondary">New Item</Button>
      <select
        class="wpea-select"
        style="width: auto;"
        bind:value={state.theme}
        onchange={(e) => setTheme(e.currentTarget.value as 'auto' | 'light' | 'dark')}
      >
        <option value="auto">Respect OS</option>
        <option value="light">Light</option>
        <option value="dark">Dark</option>
      </select>
    </Cluster>
  </header>

  <!-- Sidebar overlay -->
  <div
    class="sidebar-overlay {state.sidebarOpen ? 'open' : ''}"
    role="button"
    tabindex={state.sidebarOpen ? 0 : -1}
    onclick={toggleSidebar}
    onkeydown={handleOverlayKeydown}
    aria-label="Close sidebar"
  ></div>

  <!-- SIDEBAR -->
  <aside class="demo-sidebar {state.sidebarOpen ? 'open' : ''}">
    <nav class="demo-nav">
      <div class="demo-nav__section">Components</div>
      <a href="#alerts-badges" class={state.activeSection === 'alerts-badges' ? 'active' : ''}>Alerts & Badges</a>
      <a href="#toasts" class={state.activeSection === 'toasts' ? 'active' : ''}>Toasts</a>
      <a href="#typography" class={state.activeSection === 'typography' ? 'active' : ''}>Typography</a>
      <a href="#buttons" class={state.activeSection === 'buttons' ? 'active' : ''}>Buttons</a>
      <a href="#forms-inputs" class={state.activeSection === 'forms-inputs' ? 'active' : ''}>Forms & Inputs</a>
      <a href="#tables" class={state.activeSection === 'tables' ? 'active' : ''}>Tables</a>
      <a href="#tabs" class={state.activeSection === 'tabs' ? 'active' : ''}>Tabs</a>
      <a href="#accordions" class={state.activeSection === 'accordions' ? 'active' : ''}>Accordions</a>
      <a href="#cards-panels" class={state.activeSection === 'cards-panels' ? 'active' : ''}>Cards & Panels</a>
      <a href="#modals" class={state.activeSection === 'modals' ? 'active' : ''}>Modals</a>
      <a href="#framework-settings" class={state.activeSection === 'framework-settings' ? 'active' : ''}>Framework Settings</a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="demo-main">
    <!-- ALERTS & BADGES -->
    <section id="alerts-badges" class="demo-section">
      <h2 class="demo-section__title">Alerts & Badges</h2>

      <Stack>
        <Card title="Alerts">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Alert variant="success">All systems nominal.</Alert>
              <Alert variant="warning">2 integrations need attention.</Alert>
              <Alert variant="danger">API rate limit nearing quota.</Alert>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Alerts are used to display important messages to the user. They support semantic color variants to indicate different states.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>.wpea-alert</code></td>
                    <td>Base alert class</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-alert--success</code></td>
                    <td>Success state (green)</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-alert--warning</code></td>
                    <td>Warning state (amber)</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-alert--danger</code></td>
                    <td>Danger/error state (red)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Badges">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Cluster>
                <Badge>Default</Badge>
                <Badge variant="primary">Primary</Badge>
                <Badge variant="success">Success</Badge>
                <Badge variant="warning">Warning</Badge>
                <Badge variant="danger">Danger</Badge>
              </Cluster>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Badges are small inline labels used to highlight status, categories, or counts. They work well alongside text or in clusters.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>.badge</code></td>
                    <td>Base badge (neutral)</td>
                  </tr>
                  <tr>
                    <td><code>.badge--primary</code></td>
                    <td>Primary color</td>
                  </tr>
                  <tr>
                    <td><code>.badge--success</code></td>
                    <td>Success state (green)</td>
                  </tr>
                  <tr>
                    <td><code>.badge--warning</code></td>
                    <td>Warning state (amber)</td>
                  </tr>
                  <tr>
                    <td><code>.badge--danger</code></td>
                    <td>Danger state (red)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Framework Info">
          <div class="wpea-text-muted">Svelte 5 SPA with runes — resize to see container queries.</div>
          <hr class="wpea-divider" />
          <Cluster>
            <Badge variant="primary">Svelte 5</Badge>
            <Badge>Density: {state.density}</Badge>
            <Badge>Theme: {derived.themeLabel}</Badge>
          </Cluster>
        </Card>
      </Stack>
    </section>

    <!-- TOASTS -->
    <section id="toasts" class="demo-section">
      <h2 class="demo-section__title">Toasts</h2>

      <Stack>
        <Card title="Toast Notifications">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Positions</strong>
                  <Cluster style="margin-top: 8px;">
                    <Button variant="primary" size="s" onclick={() => showToast('top-left')}>Top Left</Button>
                    <Button variant="primary" size="s" onclick={() => showToast('top-center')}>Top Center</Button>
                    <Button variant="primary" size="s" onclick={() => showToast('top-right')}>Top Right</Button>
                    <Button variant="primary" size="s" onclick={() => showToast('bottom-left')}>Bottom Left</Button>
                    <Button variant="primary" size="s" onclick={() => showToast('bottom-center')}>Bottom Center</Button>
                    <Button variant="primary" size="s" onclick={() => showToast('bottom-right')}>Bottom Right</Button>
                    <Button variant="primary" size="s" onclick={() => showToast('center')}>Center</Button>
                  </Cluster>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Variants</strong>
                  <Cluster style="margin-top: 8px;">
                    <Button variant="secondary" size="s" onclick={() => showToast('top-right', 'default')}>Default</Button>
                    <Button variant="secondary" size="s" onclick={() => showToast('top-right', 'success')}>Success</Button>
                    <Button variant="secondary" size="s" onclick={() => showToast('top-right', 'warning')}>Warning</Button>
                    <Button variant="secondary" size="s" onclick={() => showToast('top-right', 'danger')}>Danger</Button>
                    <Button variant="secondary" size="s" onclick={() => showToast('top-right', 'info')}>Info</Button>
                  </Cluster>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Toasts provide brief, non-blocking notifications. They auto-dismiss after a timeout and can be positioned anywhere on screen.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>.wpea-toast-container</code></td>
                    <td>Container for positioning</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-toast</code></td>
                    <td>Base toast class</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-toast--success</code></td>
                    <td>Success variant</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-toast--warning</code></td>
                    <td>Warning variant</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-toast--danger</code></td>
                    <td>Danger variant</td>
                  </tr>
                  <tr>
                    <td><code>.wpea-toast--info</code></td>
                    <td>Info variant</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- TYPOGRAPHY -->
    <section id="typography" class="demo-section">
      <h2 class="demo-section__title">Typography</h2>

      <Stack>
        <Card title="Text Sizes">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div class="wpea-text-4xl">Text 4XL</div>
                <div class="wpea-text-3xl">Text 3XL</div>
                <div class="wpea-text-2xl">Text 2XL</div>
                <div class="wpea-text-xl">Text XL</div>
                <div class="wpea-text-lg">Text LG</div>
                <div class="wpea-text-md">Text MD (base)</div>
                <div class="wpea-text-sm">Text SM</div>
                <div class="wpea-text-xs">Text XS</div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Text size utilities for scaling text. Based on the modular scale defined by <code>--wpea-scale</code>.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Variable</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-text-4xl</code></td><td><code>--wpea-text--4xl</code></td></tr>
                  <tr><td><code>.wpea-text-3xl</code></td><td><code>--wpea-text--3xl</code></td></tr>
                  <tr><td><code>.wpea-text-2xl</code></td><td><code>--wpea-text--2xl</code></td></tr>
                  <tr><td><code>.wpea-text-xl</code></td><td><code>--wpea-text--xl</code></td></tr>
                  <tr><td><code>.wpea-text-lg</code></td><td><code>--wpea-text--lg</code></td></tr>
                  <tr><td><code>.wpea-text-md</code></td><td><code>--wpea-text--md</code></td></tr>
                  <tr><td><code>.wpea-text-sm</code></td><td><code>--wpea-text--sm</code></td></tr>
                  <tr><td><code>.wpea-text-xs</code></td><td><code>--wpea-text--xs</code></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Headings">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div class="wpea-heading wpea-heading--lg">Large Heading</div>
                <div class="wpea-heading wpea-heading--md">Medium Heading</div>
                <div class="wpea-heading wpea-heading--sm">Small Heading</div>
                <div class="wpea-heading">Default Heading</div>
                <div class="wpea-heading wpea-heading--muted">Muted Heading</div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Heading styles with semantic sizes and optional muted variant for secondary headings.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-heading</code></td><td>Base heading style</td></tr>
                  <tr><td><code>.wpea-heading--lg</code></td><td>Large heading</td></tr>
                  <tr><td><code>.wpea-heading--md</code></td><td>Medium heading</td></tr>
                  <tr><td><code>.wpea-heading--sm</code></td><td>Small heading</td></tr>
                  <tr><td><code>.wpea-heading--muted</code></td><td>Muted/secondary</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Color Variables">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div style="color: var(--wpea-color--heading);">Heading Color</div>
                <div style="color: var(--wpea-color--text);">Text Color</div>
                <div style="color: var(--wpea-color--text-muted);">Muted Text</div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Semantic color variables for text that automatically adapt to light/dark mode.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Variable</th>
                    <th>Usage</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>--wpea-color--heading</code></td><td>Headings, titles</td></tr>
                  <tr><td><code>--wpea-color--text</code></td><td>Body text</td></tr>
                  <tr><td><code>--wpea-color--text-muted</code></td><td>Secondary text</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- BUTTONS -->
    <section id="buttons" class="demo-section">
      <h2 class="demo-section__title">Buttons</h2>

      <Stack>
        <Card title="Buttons">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Variants</strong>
                  <Cluster style="margin-top: 8px;">
                    <button class="wpea-btn">Default</button>
                    <Button variant="primary">Primary</Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="ghost">Ghost</Button>
                  </Cluster>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Sizes</strong>
                  <Cluster style="margin-top: 8px;">
                    <Button variant="primary" size="xs">XS</Button>
                    <Button variant="primary" size="s">S</Button>
                    <Button variant="primary" size="m">M</Button>
                    <Button variant="primary" size="l">L</Button>
                    <Button variant="primary" size="xl">XL</Button>
                    <Button variant="primary" size="2xl">2XL</Button>
                  </Cluster>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Buttons support multiple variants for different actions and sizes for various contexts. Use primary for main actions, secondary for alternatives, and ghost for subtle actions.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-btn</code></td><td>Base button (neutral)</td></tr>
                  <tr><td><code>.wpea-btn--primary</code></td><td>Primary action</td></tr>
                  <tr><td><code>.wpea-btn--secondary</code></td><td>Secondary action</td></tr>
                  <tr><td><code>.wpea-btn--ghost</code></td><td>Subtle/text button</td></tr>
                  <tr><td><code>.wpea-btn--xs</code></td><td>Extra small size</td></tr>
                  <tr><td><code>.wpea-btn--s</code></td><td>Small size</td></tr>
                  <tr><td><code>.wpea-btn--m</code></td><td>Medium size</td></tr>
                  <tr><td><code>.wpea-btn--l</code></td><td>Large size</td></tr>
                  <tr><td><code>.wpea-btn--xl</code></td><td>Extra large size</td></tr>
                  <tr><td><code>.wpea-btn--2xl</code></td><td>2X large size</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- FORMS & INPUTS -->
    <section id="forms-inputs" class="demo-section">
      <h2 class="demo-section__title">Forms & Inputs</h2>

      <Stack>
        <!-- Text Input -->
        <Card title="Text Input">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Input
                id="demo-text"
                label="Text"
                placeholder="Enter text..."
                bind:value={state.demoText}
                help="Standard text input field"
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Basic text input for single-line text entry. Supports label, placeholder, and help text.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Class</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>.wpea-input</code></td><td>Base input styling</td></tr>
                  <tr><td><code>.wpea-field</code></td><td>Field wrapper with label</td></tr>
                  <tr><td><code>.wpea-label</code></td><td>Label styling</td></tr>
                  <tr><td><code>.wpea-help</code></td><td>Help text styling</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Password Input -->
        <Card title="Password Input">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Input
                id="demo-password"
                type="password"
                label="Password"
                placeholder="Enter password..."
                bind:value={state.demoPassword}
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Password input with masked characters. Use <code>type="password"</code> prop.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop</th><th>Value</th></tr></thead>
                <tbody>
                  <tr><td><code>type</code></td><td><code>"password"</code></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Email Input -->
        <Card title="Email Input">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Input
                id="demo-email"
                type="email"
                label="Email"
                placeholder="you@example.com"
                bind:value={state.demoEmail}
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Email input with browser validation. Use <code>type="email"</code> prop.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop</th><th>Value</th></tr></thead>
                <tbody>
                  <tr><td><code>type</code></td><td><code>"email"</code></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Number Input -->
        <Card title="Number Input">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <NumberInput
                  id="demo-number"
                  label="Quantity"
                  bind:value={state.demoNumber}
                  min={0}
                  max={100}
                  step={1}
                  help="Custom styled number input with spinner controls"
                />
                <NumberInput
                  id="demo-number-step"
                  label="Price (step: 5)"
                  bind:value={state.demoNumberStep}
                  min={0}
                  max={500}
                  step={5}
                />
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Custom number input with styled increment/decrement buttons matching the AdvancedSelect aesthetic.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>min</code></td><td>Minimum value</td></tr>
                  <tr><td><code>max</code></td><td>Maximum value</td></tr>
                  <tr><td><code>step</code></td><td>Increment amount (default: 1)</td></tr>
                  <tr><td><code>disabled</code></td><td>Disable input</td></tr>
                  <tr><td><code>readonly</code></td><td>Read-only mode</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Textarea -->
        <Card title="Textarea">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Textarea
                id="demo-textarea"
                label="Description"
                placeholder="Enter a longer description..."
                rows={4}
                bind:value={state.demoTextarea}
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Multi-line text input. Use <code>rows</code> prop to set initial height.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Class</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>.wpea-textarea</code></td><td>Base textarea styling</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Select -->
        <Card title="Select">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Select
                id="demo-select"
                label="Choose an option"
                bind:value={state.demoSelect}
                options={[
                  { value: 'option1', label: 'Option One' },
                  { value: 'option2', label: 'Option Two' },
                  { value: 'option3', label: 'Option Three' }
                ]}
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Native select dropdown. Pass <code>options</code> array with <code>value</code> and <code>label</code>.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Class</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>.wpea-select</code></td><td>Base select styling</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- MultiSelect -->
        <Card title="MultiSelect">
          <div class="component-panel">
            <div class="component-panel__demo">
              <MultiSelect
                id="demo-multiselect"
                label="Select frameworks"
                placeholder="Choose frameworks..."
                bind:value={state.demoMultiSelect}
                options={frameworkOptions}
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Multiselect with search, tags, and keyboard navigation. Supports <code>clearable</code>, <code>searchable</code>, and <code>maxItems</code> props.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Class</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>.wpea-multiselect</code></td><td>Container</td></tr>
                  <tr><td><code>.wpea-multiselect__control</code></td><td>Input area</td></tr>
                  <tr><td><code>.wpea-multiselect__tag</code></td><td>Selected item tag</td></tr>
                  <tr><td><code>.wpea-multiselect__menu</code></td><td>Dropdown menu</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- AdvancedSelect - Single -->
        <Card title="AdvancedSelect (Single)">
          <div class="component-panel">
            <div class="component-panel__demo">
              <AdvancedSelect
                id="demo-single-select"
                label="Select one framework"
                placeholder="Choose a framework..."
                bind:value={state.demoSingleSelect}
                options={frameworkOptions}
                multiple={false}
              />
              <div class="wpea-text-sm wpea-text-muted" style="margin-top: 8px;">
                Selected: {state.demoSingleSelect || 'none'}
              </div>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Single selection mode. Set <code>multiple={false}</code> to allow only one selection. Dropdown closes after selection.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>multiple</code></td><td>false for single select</td></tr>
                  <tr><td><code>value</code></td><td>string (not array)</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- AdvancedSelect - Manageable -->
        <Card title="AdvancedSelect (Manageable)">
          <div class="component-panel">
            <div class="component-panel__demo">
              <AdvancedSelect
                id="demo-manageable-select"
                label="Manageable Tags"
                placeholder="Select or create tags..."
                bind:value={state.demoManageableSelect}
                options={state.demoManageableOptions}
                creatable={true}
                manageable={true}
                color="primary"
              />
              <div class="wpea-text-sm wpea-text-muted" style="margin-top: 8px;">
                Selected: {state.demoManageableSelect.join(', ') || 'none'}
              </div>
              <div class="wpea-text-xs wpea-text-muted" style="margin-top: 4px;">
                Events: AdvancedSelect:create, AdvancedSelect:delete, AdvancedSelect:lock
              </div>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Manageable mode dispatches DOM events. Options can have <code>deletable</code> and <code>locked</code> flags. External listeners modify the state.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop/Flag</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>manageable</code></td><td>Show lock/delete buttons</td></tr>
                  <tr><td><code>option.deletable</code></td><td>true=deletable, false=protected</td></tr>
                  <tr><td><code>option.locked</code></td><td>Temporarily locked (red)</td></tr>
                </tbody>
              </table>
              <div class="component-panel__notes" style="margin-top: var(--wpea-space--md);">
                <strong>Events</strong> (bubble up, listen on document)
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Event</th><th>Detail</th></tr></thead>
                <tbody>
                  <tr><td><code>AdvancedSelect:create</code></td><td>id, value, query</td></tr>
                  <tr><td><code>AdvancedSelect:delete</code></td><td>id, value, option</td></tr>
                  <tr><td><code>AdvancedSelect:lock</code></td><td>id, value, option</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Radio -->
        <Card title="Radio">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <Radio
                  name="demo-radio"
                  label="Select one option"
                  bind:value={state.demoRadio}
                  options={radioOptions}
                />
                <hr class="wpea-divider" />
                <Radio
                  name="demo-radio-inline"
                  label="Inline radio buttons"
                  bind:value={state.demoRadio}
                  options={radioOptions}
                  inline={true}
                  color="primary"
                />
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Radio button group for single selection. Supports <code>inline</code> layout and color variants.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Class</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>.wpea-radio</code></td><td>Radio container</td></tr>
                  <tr><td><code>.wpea-radio-group</code></td><td>Group wrapper</td></tr>
                  <tr><td><code>.wpea-radio-group--inline</code></td><td>Horizontal layout</td></tr>
                  <tr><td><code>.wpea-radio--primary</code></td><td>Primary color</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Switch (Checkbox replacement) -->
        <Card title="Switch (Checkbox)">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Sizes</strong>
                  <Cluster style="margin-top: 8px;">
                    <Switch id="sw-xs" size="xs" bind:checked={state.switchXs} label="XS" />
                    <Switch id="sw-s" size="s" bind:checked={state.switchS} label="S" />
                    <Switch id="sw-default" bind:checked={state.switchDefault} label="Default" />
                    <Switch id="sw-m" size="m" bind:checked={state.switchM} label="M" />
                    <Switch id="sw-l" size="l" bind:checked={state.switchL} label="L" />
                  </Cluster>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Colors</strong>
                  <Cluster style="margin-top: 8px;">
                    <Switch id="sw-neutral" color="neutral" bind:checked={state.switchNeutral} label="Neutral" />
                    <Switch id="sw-primary" color="primary" bind:checked={state.switchPrimary} label="Primary" />
                    <Switch id="sw-secondary" color="secondary" bind:checked={state.switchSecondary} label="Secondary" />
                  </Cluster>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Toggle switch for boolean values. Use instead of checkboxes for cleaner UI.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Class</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>.wpea-switch</code></td><td>Base switch</td></tr>
                  <tr><td><code>.wpea-switch--xs/s/m/l</code></td><td>Size variants</td></tr>
                  <tr><td><code>.wpea-switch--primary</code></td><td>Primary color</td></tr>
                  <tr><td><code>.wpea-switch--secondary</code></td><td>Secondary color</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- 3-State Toggle Button -->
        <Card title="3-State Toggle Button">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Theme Selector</strong>
                  <div style="margin-top: 12px;">
                    <Toggle3State
                      bind:value={state.theme}
                      ariaLabel="Theme selection"
                      onChange={(value) => setTheme(value as 'auto' | 'light' | 'dark')}
                      options={[
                        {
                          value: 'light',
                          label: 'Light',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="4" stroke-width="2"/><path stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>`
                          }))
                        },
                        {
                          value: 'dark',
                          label: 'Dark',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>`
                          }))
                        },
                        {
                          value: 'auto',
                          label: 'Auto',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>`
                          }))
                        }
                      ]}
                    />
                  </div>
                </div>
                <hr class="wpea-divider">
                <div>
                  <strong>View Mode Selector</strong>
                  <div style="margin-top: 12px;">
                    <Toggle3State
                      bind:value={state.viewMode}
                      ariaLabel="View mode selection"
                      options={[
                        {
                          value: 'grid',
                          label: 'Grid',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>`
                          }))
                        },
                        {
                          value: 'list',
                          label: 'List',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>`
                          }))
                        },
                        {
                          value: 'table',
                          label: 'Table',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>`
                          }))
                        }
                      ]}
                    />
                  </div>
                </div>
                <hr class="wpea-divider">
                <div>
                  <strong>Icon Only (with popovers)</strong>
                  <div style="margin-top: 12px;">
                    <Toggle3State
                      bind:value={state.theme}
                      ariaLabel="Theme selection"
                      iconOnly={true}
                      showPopover={true}
                      popoverPosition="top"
                      popoverSize="xs"
                      onChange={(value) => setTheme(value as 'auto' | 'light' | 'dark')}
                      options={[
                        {
                          value: 'light',
                          label: 'Light',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="4" stroke-width="2"/><path stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/></svg>`
                          }))
                        },
                        {
                          value: 'dark',
                          label: 'Dark',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>`
                          }))
                        },
                        {
                          value: 'auto',
                          label: 'Auto',
                          icon: createRawSnippet(() => ({
                            render: () => `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>`
                          }))
                        }
                      ]}
                    />
                  </div>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                A 3-state toggle button group. Supports icons, labels, and icon-only mode with optional popovers.
              </div>
            </div>
          </div>
        </Card>

        <!-- Double Opt-In Button -->
        <Card title="Double Opt-In Button">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Delete Action (Danger)</strong>
                  <div style="margin-top: 12px;">
                    <DoubleOptInButton
                      defaultLabel="Delete"
                      confirmLabel="Confirm?"
                      timeout={2000}
                      onClick={() => showToast('center', 'danger')}
                    >
                      {#snippet defaultIcon()}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      {/snippet}
                      {#snippet confirmIcon()}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                      {/snippet}
                    </DoubleOptInButton>
                  </div>
                </div>
                <hr class="wpea-divider">
                <div>
                  <strong>Archive Action (Success variant)</strong>
                  <div style="margin-top: 12px;">
                    <DoubleOptInButton
                      defaultLabel="Archive"
                      confirmLabel="Confirm?"
                      timeout={3000}
                      variant="success"
                      onClick={() => showToast('center', 'success')}
                    >
                      {#snippet defaultIcon()}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                      {/snippet}
                      {#snippet confirmIcon()}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                      {/snippet}
                    </DoubleOptInButton>
                  </div>
                </div>
                <hr class="wpea-divider">
                <div>
                  <strong>Icon Only (with popovers)</strong>
                  <Cluster style="margin-top: 12px;">
                    <Popover content="Delete" position="top" size="xs">
                      {#snippet children()}
                        <DoubleOptInButton
                          iconOnly={true}
                          ariaLabel="Delete"
                          timeout={2000}
                          onClick={() => showToast('center', 'danger')}
                        >
                          {#snippet defaultIcon()}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                          {/snippet}
                          {#snippet confirmIcon()}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                          {/snippet}
                        </DoubleOptInButton>
                      {/snippet}
                    </Popover>
                    <Popover content="Archive" position="top" size="xs">
                      {#snippet children()}
                        <DoubleOptInButton
                          iconOnly={true}
                          ariaLabel="Archive"
                          timeout={3000}
                          variant="success"
                          onClick={() => showToast('center', 'success')}
                        >
                          {#snippet defaultIcon()}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                          {/snippet}
                          {#snippet confirmIcon()}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                          {/snippet}
                        </DoubleOptInButton>
                      {/snippet}
                    </Popover>
                  </Cluster>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                A button requiring two clicks to confirm action. First click enters confirm state, second click executes. Auto-resets after timeout.
              </div>
            </div>
          </div>
        </Card>

        <!-- Range Slider -->
        <Card title="Range Slider">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <Range
                  id="demo-range"
                  label="Volume (step: 1)"
                  min={0}
                  max={100}
                  step={1}
                  bind:value={state.demoRangeVolume}
                />
                <Range
                  id="demo-range-step"
                  label="Rating (step: 10)"
                  min={0}
                  max={100}
                  step={10}
                  bind:value={state.demoRangeRating}
                  color="primary"
                />
                <Range
                  id="demo-range-decimal"
                  label="Opacity (step: 0.1)"
                  min={0}
                  max={1}
                  step={0.1}
                  bind:value={state.demoRangeOpacity}
                  color="secondary"
                />
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Single-handle slider. Use <code>step</code> to control increment size. Supports <code>min</code>, <code>max</code>, and color variants.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>min</code></td><td>Minimum value</td></tr>
                  <tr><td><code>max</code></td><td>Maximum value</td></tr>
                  <tr><td><code>step</code></td><td>Increment amount</td></tr>
                  <tr><td><code>color</code></td><td>primary, secondary</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <!-- Dual Range Slider -->
        <Card title="Dual Range Slider">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <Range
                  id="demo-range-dual"
                  label="Price Range"
                  min={0}
                  max={100}
                  step={5}
                  range={true}
                  bind:valueMin={state.demoRangePriceMin}
                  bind:valueMax={state.demoRangePriceMax}
                />
                <Range
                  id="demo-range-dual-primary"
                  label="Age Range"
                  min={18}
                  max={65}
                  step={1}
                  range={true}
                  bind:valueMin={state.demoRangeAgeMin}
                  bind:valueMax={state.demoRangeAgeMax}
                  color="primary"
                />
                <Range
                  id="demo-range-dual-secondary"
                  label="Time Range (hours)"
                  min={0}
                  max={24}
                  step={1}
                  range={true}
                  bind:valueMin={state.demoRangeTimeMin}
                  bind:valueMax={state.demoRangeTimeMax}
                  color="secondary"
                />
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Dual-handle slider for selecting a range. Use <code>range={true}</code> and bind to <code>valueMin</code> / <code>valueMax</code>.
              </div>
              <table class="component-panel__table">
                <thead><tr><th>Prop</th><th>Description</th></tr></thead>
                <tbody>
                  <tr><td><code>range</code></td><td>Enable dual handles</td></tr>
                  <tr><td><code>valueMin</code></td><td>Lower bound value</td></tr>
                  <tr><td><code>valueMax</code></td><td>Upper bound value</td></tr>
                  <tr><td><code>step</code></td><td>Increment amount</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- TABLES -->
    <section id="tables" class="demo-section">
      <h2 class="demo-section__title">Tables</h2>

      <Stack>
        <Card title="Default Table">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Table columns={tableColumns} data={tableData}>
                {#snippet tbody()}
                  {#each tableData as row}
                    <tr>
                      <td>{row.order}</td>
                      <td>{row.customer}</td>
                      <td><Badge variant={row.statusVariant as any}>{row.status}</Badge></td>
                      <td>{row.total}</td>
                      <td class="wpea-hide-md-down">{row.date}</td>
                      <td style="text-align:end;">
                        <Button variant="ghost" size="s">View</Button>
                      </td>
                    </tr>
                  {/each}
                {/snippet}
              </Table>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Tables display tabular data with styled headers, borders, and optional hover states. Use with Badge and Button components for rich data displays.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-table</code></td><td>Base table styling</td></tr>
                  <tr><td><code>.wpea-table th</code></td><td>Header cells</td></tr>
                  <tr><td><code>.wpea-table td</code></td><td>Data cells</td></tr>
                  <tr><td><code>.wpea-hide-md-down</code></td><td>Hide on mobile</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Striped Table">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Table columns={tableColumns} data={tableData} striped={true}>
                {#snippet tbody()}
                  {#each tableData as row}
                    <tr>
                      <td>{row.order}</td>
                      <td>{row.customer}</td>
                      <td><Badge variant={row.statusVariant as any}>{row.status}</Badge></td>
                      <td>{row.total}</td>
                      <td class="wpea-hide-md-down">{row.date}</td>
                      <td style="text-align:end;">
                        <Button variant="ghost" size="s">View</Button>
                      </td>
                    </tr>
                  {/each}
                {/snippet}
              </Table>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Add <code>striped</code> prop (or <code>.wpea-table--striped</code> class) for alternating row backgrounds. Improves readability for tables with many rows.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-table--striped</code></td><td>Alternating row colors</td></tr>
                  <tr><td><code>striped={true}</code></td><td>Svelte component prop</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- TABS -->
    <section id="tabs" class="demo-section">
      <h2 class="demo-section__title">Tabs</h2>

      <Stack>
        <Card title="Horizontal Tabs">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Default</strong>
                  <div style="margin-top: 8px;"><Tabs tabs={defaultTabs} /></div>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Primary</strong>
                  <div style="margin-top: 8px;"><Tabs tabs={primaryTabs} variant="primary" /></div>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Secondary</strong>
                  <div style="margin-top: 8px;"><Tabs tabs={secondaryTabs} variant="secondary" /></div>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Tabs organize content into separate views. Supports multiple color variants and handles active state automatically.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-tabs</code></td><td>Tab container</td></tr>
                  <tr><td><code>.wpea-tabs__list</code></td><td>Tab button list</td></tr>
                  <tr><td><code>.wpea-tabs__tab</code></td><td>Individual tab</td></tr>
                  <tr><td><code>.wpea-tabs--primary</code></td><td>Primary variant</td></tr>
                  <tr><td><code>.wpea-tabs--secondary</code></td><td>Secondary variant</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Vertical Tabs">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Default</strong>
                  <div style="margin-top: 8px;"><VerticalTabs tabs={defaultTabs} /></div>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Primary</strong>
                  <div style="margin-top: 8px;"><VerticalTabs tabs={primaryTabs} variant="primary" /></div>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Secondary</strong>
                  <div style="margin-top: 8px;"><VerticalTabs tabs={secondaryTabs} variant="secondary" /></div>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Vertical tabs display navigation on the left with content on the right. Ideal for settings pages or multi-step forms.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-vtabs</code></td><td>Vertical tab container</td></tr>
                  <tr><td><code>.wpea-vtabs__list</code></td><td>Tab button list</td></tr>
                  <tr><td><code>.wpea-vtabs__tab</code></td><td>Individual tab</td></tr>
                  <tr><td><code>.wpea-vtabs__content</code></td><td>Content area</td></tr>
                  <tr><td><code>.wpea-vtabs--primary</code></td><td>Primary variant</td></tr>
                  <tr><td><code>.wpea-vtabs--secondary</code></td><td>Secondary variant</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- ACCORDIONS -->
    <section id="accordions" class="demo-section">
      <h2 class="demo-section__title">Accordions</h2>

      <Stack>
        <Card title="Accordions">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Default</strong>
                  <div style="margin-top: 8px;"><Accordion items={defaultAccordion} /></div>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Primary</strong>
                  <div style="margin-top: 8px;"><Accordion items={primaryAccordion} variant="primary" /></div>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Secondary</strong>
                  <div style="margin-top: 8px;"><Accordion items={secondaryAccordion} variant="secondary" /></div>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Accordions expand/collapse content sections. Only one item can be open at a time within each accordion group.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-accordion</code></td><td>Container</td></tr>
                  <tr><td><code>.wpea-accordion__item</code></td><td>Single item</td></tr>
                  <tr><td><code>.wpea-accordion__header</code></td><td>Clickable header</td></tr>
                  <tr><td><code>.wpea-accordion__content</code></td><td>Collapsible content</td></tr>
                  <tr><td><code>.wpea-accordion--primary</code></td><td>Primary variant</td></tr>
                  <tr><td><code>.wpea-accordion--secondary</code></td><td>Secondary variant</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- CARDS & PANELS -->
    <section id="cards-panels" class="demo-section">
      <h2 class="demo-section__title">Cards & Panels</h2>

      <Stack>
        <Card title="Cards">
          <div class="component-panel">
            <div class="component-panel__demo">
              <div class="wpea-grid-2">
                <Card title="Revenue" subtitle="Last 30 days">
                  <div class="wpea-heading wpea-heading--lg">$84,120</div>
                  {#snippet actions()}
                    <Button variant="ghost" size="s">Details</Button>
                  {/snippet}
                </Card>
                <Card title="Errors" subtitle="Last 24h" hover={true}>
                  <div class="wpea-heading wpea-heading--lg">37</div>
                  {#snippet actions()}
                    <Button variant="primary" size="s">Investigate</Button>
                  {/snippet}
                </Card>
              </div>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Cards are flexible containers for content with optional title, subtitle, and action buttons. Add <code>hover={true}</code> for hover effects.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-card</code></td><td>Base card</td></tr>
                  <tr><td><code>.wpea-card__header</code></td><td>Header area</td></tr>
                  <tr><td><code>.wpea-card__title</code></td><td>Title text</td></tr>
                  <tr><td><code>.wpea-card__subtitle</code></td><td>Subtitle text</td></tr>
                  <tr><td><code>.wpea-card__actions</code></td><td>Action buttons</td></tr>
                  <tr><td><code>.wpea-card--hover</code></td><td>Hover effect</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>

        <Card title="Panels">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Panel>
                <div class="wpea-panel__header">
                  <h4 class="wpea-heading wpea-heading--sm">Panel Example</h4>
                </div>
                <p>Panels group related content with a subtle background.</p>
                <Cluster>
                  <Button variant="ghost" size="s">Learn More</Button>
                  <Button variant="secondary" size="s">Get Started</Button>
                </Cluster>
              </Panel>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Panels are simpler containers than cards, with a muted background. Good for grouping form sections or related content.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-panel</code></td><td>Base panel</td></tr>
                  <tr><td><code>.wpea-panel__header</code></td><td>Header area</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- MODALS -->
    <section id="modals" class="demo-section">
      <h2 class="demo-section__title">Modals</h2>

      <Stack>
        <Card title="Modals">
          <div class="component-panel">
            <div class="component-panel__demo">
              <Stack>
                <div>
                  <strong>Sizes</strong>
                  <Cluster style="margin-top: 8px;">
                    <Button variant="primary" size="s" onclick={() => state.modalStandard = true}>Standard</Button>
                    <Button variant="secondary" size="s" onclick={() => state.modalLarge = true}>Large</Button>
                    <Button variant="ghost" size="s" onclick={() => state.modalFullscreen = true}>Fullscreen</Button>
                  </Cluster>
                </div>
                <hr class="wpea-divider" />
                <div>
                  <strong>Scrollable Content</strong>
                  <div style="margin-top: 8px;">
                    <Button variant="primary" size="s" onclick={() => state.modalScrollable = true}>Scrollable Modal</Button>
                  </div>
                </div>
              </Stack>
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                Modals are dialog overlays that focus user attention. Support multiple sizes and automatically handle scrollable content.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>.wpea-modal</code></td><td>Base modal overlay</td></tr>
                  <tr><td><code>.wpea-modal--open</code></td><td>Visible state</td></tr>
                  <tr><td><code>.wpea-modal__dialog</code></td><td>Dialog container</td></tr>
                  <tr><td><code>.wpea-modal__header</code></td><td>Header with title</td></tr>
                  <tr><td><code>.wpea-modal__body</code></td><td>Scrollable content</td></tr>
                  <tr><td><code>.wpea-modal__footer</code></td><td>Action buttons</td></tr>
                  <tr><td><code>.wpea-modal--large</code></td><td>Large size</td></tr>
                  <tr><td><code>.wpea-modal--fullscreen</code></td><td>Fullscreen</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>

    <!-- FRAMEWORK SETTINGS -->
    <section id="framework-settings" class="demo-section">
      <h2 class="demo-section__title">Framework Settings</h2>

      <Stack>
        <Card title="Display Settings">
          <div class="component-panel">
            <div class="component-panel__demo component-panel__demo--full">
              <FrameworkSettings
                bind:settings={state.frameworkSettings}
                onchange={(s) => console.log('Settings changed:', s)}
              />
            </div>
            <div class="component-panel__info">
              <div class="component-panel__notes">
                The FrameworkSettings component provides a complete UI for customizing the framework's appearance. It includes theme mode switching, compact mode, color pickers for brand and semantic colors, and sliders for spacing, typography, and border radius scales.
              </div>
              <table class="component-panel__table">
                <thead>
                  <tr>
                    <th>Prop</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>settings</code></td><td>Bindable FrameworkDisplaySettings object</td></tr>
                  <tr><td><code>onchange</code></td><td>Callback when settings change</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </Card>
      </Stack>
    </section>
  </main>
</div>

<!-- Modal Components -->
<!-- Standard Modal -->
<Modal bind:open={state.modalStandard} title="Standard Modal" size="standard">
  {#snippet children()}
    <p>This is a standard modal dialog. It's perfect for confirmations, short forms, or displaying concise information.</p>
    <Stack>
      <Input
        id="svelte-modal-input"
        label="Your Name"
        placeholder="Enter your name"
      />
      <Select
        id="svelte-modal-select"
        label="Choose an option"
        options={[
          { value: '1', label: 'Option 1' },
          { value: '2', label: 'Option 2' },
          { value: '3', label: 'Option 3' }
        ]}
      />
    </Stack>
  {/snippet}
  {#snippet footer()}
    <Button variant="ghost" onclick={() => state.modalStandard = false}>Cancel</Button>
    <Button variant="primary">Save Changes</Button>
  {/snippet}
</Modal>

<!-- Large Modal -->
<Modal bind:open={state.modalLarge} title="Large Modal" size="large">
  {#snippet children()}
    <p>This is a large modal, perfect for more complex forms or detailed content displays.</p>
    <div class="wpea-grid-2">
      <Card title="Section 1">
        <p>Content for the first section of your large modal.</p>
      </Card>
      <Card title="Section 2">
        <p>Content for the second section of your large modal.</p>
      </Card>
    </div>
    <div style="margin-top: var(--wpea-space--lg);">
      <Textarea
        id="svelte-modal-textarea"
        label="Description"
        rows={4}
        placeholder="Enter a description..."
      />
    </div>
  {/snippet}
  {#snippet footer()}
    <Button variant="ghost" onclick={() => state.modalLarge = false}>Cancel</Button>
    <Button variant="secondary">Save Draft</Button>
    <Button variant="primary">Publish</Button>
  {/snippet}
</Modal>

<!-- Fullscreen Modal -->
<Modal bind:open={state.modalFullscreen} title="Fullscreen Modal" size="fullscreen">
  {#snippet children()}
    <p>This is a fullscreen modal, taking up the entire viewport. Great for immersive experiences or complex workflows.</p>
    <div class="wpea-grid-3" style="margin-top: var(--wpea-space--lg);">
      <Card title="Feature 1">
        <p>Description of the first feature.</p>
        {#snippet actions()}
          <Button variant="primary">Learn More</Button>
        {/snippet}
      </Card>
      <Card title="Feature 2">
        <p>Description of the second feature.</p>
        {#snippet actions()}
          <Button variant="primary">Learn More</Button>
        {/snippet}
      </Card>
      <Card title="Feature 3">
        <p>Description of the third feature.</p>
        {#snippet actions()}
          <Button variant="primary">Learn More</Button>
        {/snippet}
      </Card>
    </div>
  {/snippet}
  {#snippet footer()}
    <Button variant="ghost" onclick={() => state.modalFullscreen = false}>Close</Button>
  {/snippet}
</Modal>

<!-- Scrollable Modal -->
<Modal bind:open={state.modalScrollable} title="Scrollable Content Modal" size="standard">
  {#snippet children()}
    <h4 class="wpea-heading wpea-heading--sm" style="margin: 0 0 var(--wpea-space--sm) 0;">Custom Scrollbars</h4>
    <p>This modal demonstrates custom styled scrollbars. The scrollbar has a neutral background and primary color thumb.</p>

    <h4 class="wpea-heading wpea-heading--sm" style="margin-top: var(--wpea-space--lg);">Lorem Ipsum</h4>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>

    <h4 class="wpea-heading wpea-heading--sm" style="margin-top: var(--wpea-space--lg);">More Content</h4>
    <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

    <h4 class="wpea-heading wpea-heading--sm" style="margin-top: var(--wpea-space--lg);">Additional Section</h4>
    <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis.</p>

    <h4 class="wpea-heading wpea-heading--sm" style="margin-top: var(--wpea-space--lg);">Even More Content</h4>
    <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p>

    <h4 class="wpea-heading wpea-heading--sm" style="margin-top: var(--wpea-space--lg);">Final Section</h4>
    <p>Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus.</p>

    <Alert variant="success" style="margin-top: var(--wpea-space--lg);">
      Scroll down to see the custom scrollbar styling!
    </Alert>

    <p style="margin-top: var(--wpea-space--lg);">More content to make this modal scrollable. The scrollbar should be thin with a neutral background and primary color thumb that becomes more opaque on hover.</p>
  {/snippet}
  {#snippet footer()}
    <Button variant="primary" onclick={() => state.modalScrollable = false}>Got it!</Button>
  {/snippet}
</Modal>
