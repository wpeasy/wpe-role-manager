<script module lang="ts">
  // Type definition for framework display settings
  export type FrameworkDisplaySettings = {
    compact_mode: boolean;
    compact_multiplier: number;
    theme_mode: 'light' | 'dark' | 'system';
    // Spacing
    space_base: number;
    space_scale: number;
    // Typography
    font_base: number;
    type_scale: number;
    // Border Radius
    radius_base: number;
    radius_scale: number;
    // Brand colors
    primary_light: string;
    primary_dark: string;
    secondary_light: string;
    secondary_dark: string;
    neutral_light: string;
    neutral_dark: string;
    // Semantic colors
    success_light: string;
    success_dark: string;
    warning_light: string;
    warning_dark: string;
    danger_light: string;
    danger_dark: string;
    info_light: string;
    info_dark: string;
  };

  // Default display settings
  export const defaultFrameworkSettings: FrameworkDisplaySettings = {
    compact_mode: false,
    compact_multiplier: 0.7,
    theme_mode: 'system',
    space_base: 8,
    space_scale: 1.5,
    font_base: 13,
    type_scale: 1.2,
    radius_base: 6,
    radius_scale: 1.67,
    primary_light: '#a402ba',
    primary_dark: '#a402ba',
    secondary_light: '#32a8ac',
    secondary_dark: '#32a8ac',
    neutral_light: '#777777',
    neutral_dark: '#9aa0a6',
    success_light: '#22c55e',
    success_dark: '#4ade80',
    warning_light: '#f59e0b',
    warning_dark: '#fbbf24',
    danger_light: '#ef4444',
    danger_dark: '#f87171',
    info_light: '#3b82f6',
    info_dark: '#60a5fa',
  };
</script>

<script lang="ts">
  import ColorPicker from 'svelte-awesome-color-picker';
  import Switch from './Switch.svelte';
  import Toggle3State from './Toggle3State.svelte';
  import DoubleOptInButton from './DoubleOptInButton.svelte';
  import Card from './Card.svelte';
  import NumberInput from './NumberInput.svelte';

  type Props = {
    settings?: FrameworkDisplaySettings;
    onchange?: (settings: FrameworkDisplaySettings) => void;
    class?: string;
    style?: string;
  };

  let {
    settings = $bindable({ ...defaultFrameworkSettings }),
    onchange,
    class: className = '',
    style
  }: Props = $props();

  // Local slider state for live preview without triggering save during drag
  let sliderState = $state({
    space_base: settings.space_base,
    space_scale: settings.space_scale,
    font_base: settings.font_base,
    type_scale: settings.type_scale,
    radius_base: settings.radius_base,
    radius_scale: settings.radius_scale,
  });

  // Sync slider state when settings changes externally
  $effect(() => {
    sliderState.space_base = settings.space_base;
    sliderState.space_scale = settings.space_scale;
    sliderState.font_base = settings.font_base;
    sliderState.type_scale = settings.type_scale;
    sliderState.radius_base = settings.radius_base;
    sliderState.radius_scale = settings.radius_scale;
  });

  // Track which color picker is open
  let colorPickerStates = $state<Record<string, boolean>>({
    primary_light: false,
    primary_dark: false,
    secondary_light: false,
    secondary_dark: false,
    neutral_light: false,
    neutral_dark: false,
    success_light: false,
    success_dark: false,
    warning_light: false,
    warning_dark: false,
    danger_light: false,
    danger_dark: false,
    info_light: false,
    info_dark: false,
  });

  // Helper to check if any picker is open
  const anyPickerOpen = $derived(Object.entries(colorPickerStates).find(([_, open]) => open));
  const openPickerKey = $derived(anyPickerOpen ? anyPickerOpen[0] : null);

  // Apply theme mode
  function applyThemeMode(mode: 'light' | 'dark' | 'system') {
    if (mode === 'light') {
      document.body.style.setProperty('color-scheme', 'light only');
    } else if (mode === 'dark') {
      document.body.style.setProperty('color-scheme', 'dark only');
    } else {
      document.body.style.setProperty('color-scheme', 'light dark');
    }
  }

  // Apply compact mode via class (works with .wpea-compact CSS rules)
  $effect(() => {
    if (settings.compact_mode) {
      document.body.classList.add('wpea-compact');
    } else {
      document.body.classList.remove('wpea-compact');
    }
  });

  // Temporarily switch theme when color picker opens
  $effect(() => {
    if (openPickerKey) {
      if (openPickerKey.endsWith('_light')) {
        document.body.style.setProperty('color-scheme', 'light only');
      } else {
        document.body.style.setProperty('color-scheme', 'dark only');
      }
    } else {
      applyThemeMode(settings.theme_mode);
    }
  });

  // Apply color overrides when any color changes
  $effect(() => {
    const root = document.documentElement;
    root.style.setProperty('--wpea-color--primary-light-override', settings.primary_light);
    root.style.setProperty('--wpea-color--primary-dark-override', settings.primary_dark);
    root.style.setProperty('--wpea-color--secondary-light-override', settings.secondary_light);
    root.style.setProperty('--wpea-color--secondary-dark-override', settings.secondary_dark);
    root.style.setProperty('--wpea-color--neutral-light-override', settings.neutral_light);
    root.style.setProperty('--wpea-color--neutral-dark-override', settings.neutral_dark);
    root.style.setProperty('--wpea-color--success-light-override', settings.success_light);
    root.style.setProperty('--wpea-color--success-dark-override', settings.success_dark);
    root.style.setProperty('--wpea-color--warning-light-override', settings.warning_light);
    root.style.setProperty('--wpea-color--warning-dark-override', settings.warning_dark);
    root.style.setProperty('--wpea-color--danger-light-override', settings.danger_light);
    root.style.setProperty('--wpea-color--danger-dark-override', settings.danger_dark);
    root.style.setProperty('--wpea-color--info-light-override', settings.info_light);
    root.style.setProperty('--wpea-color--info-dark-override', settings.info_dark);
  });

  // Apply spacing, typography, and radius overrides via style element
  let styleElement: HTMLStyleElement | null = null;

  $effect(() => {
    if (!styleElement) {
      styleElement = document.createElement('style');
      styleElement.id = 'wpea-framework-settings-overrides';
      document.head.appendChild(styleElement);
    }

    styleElement.textContent = `
      :root {
        --wpea-space-base: ${sliderState.space_base}px !important;
        --wpea-space-scale: ${sliderState.space_scale} !important;
        --wpea-space-compact: ${settings.compact_multiplier} !important;
        --wpea-fs-base: ${sliderState.font_base}px !important;
        --wpea-type-scale: ${sliderState.type_scale} !important;
        --wpea-radius-base: ${sliderState.radius_base}px !important;
        --wpea-radius-scale: ${sliderState.radius_scale} !important;
      }
    `;

    return () => {
      if (styleElement) {
        styleElement.remove();
        styleElement = null;
      }
    };
  });

  // Notify parent when settings change
  function notifyChange() {
    onchange?.(settings);
  }

  // Reset all settings to defaults
  function resetAllToDefaults() {
    settings.primary_light = defaultFrameworkSettings.primary_light;
    settings.primary_dark = defaultFrameworkSettings.primary_dark;
    settings.secondary_light = defaultFrameworkSettings.secondary_light;
    settings.secondary_dark = defaultFrameworkSettings.secondary_dark;
    settings.neutral_light = defaultFrameworkSettings.neutral_light;
    settings.neutral_dark = defaultFrameworkSettings.neutral_dark;
    settings.success_light = defaultFrameworkSettings.success_light;
    settings.success_dark = defaultFrameworkSettings.success_dark;
    settings.warning_light = defaultFrameworkSettings.warning_light;
    settings.warning_dark = defaultFrameworkSettings.warning_dark;
    settings.danger_light = defaultFrameworkSettings.danger_light;
    settings.danger_dark = defaultFrameworkSettings.danger_dark;
    settings.info_light = defaultFrameworkSettings.info_light;
    settings.info_dark = defaultFrameworkSettings.info_dark;
    settings.space_base = defaultFrameworkSettings.space_base;
    settings.space_scale = defaultFrameworkSettings.space_scale;
    settings.font_base = defaultFrameworkSettings.font_base;
    settings.type_scale = defaultFrameworkSettings.type_scale;
    settings.radius_base = defaultFrameworkSettings.radius_base;
    settings.radius_scale = defaultFrameworkSettings.radius_scale;
    notifyChange();
  }

  // Computed spacing values (affected by compact mode)
  const computedSpacing = $derived({
    xs: settings.compact_mode
      ? (sliderState.space_base / 2 * settings.compact_multiplier).toFixed(1)
      : (sliderState.space_base / 2).toFixed(1),
    sm: settings.compact_mode
      ? (sliderState.space_base * settings.compact_multiplier).toFixed(1)
      : sliderState.space_base.toFixed(1),
    md: settings.compact_mode
      ? (sliderState.space_base * sliderState.space_scale * settings.compact_multiplier).toFixed(1)
      : (sliderState.space_base * sliderState.space_scale).toFixed(1),
  });

  // Color definitions for iteration
  const brandColors = [
    { key: 'primary', label: 'Primary' },
    { key: 'secondary', label: 'Secondary' },
    { key: 'neutral', label: 'Neutral' },
  ] as const;

  const semanticColors = [
    { key: 'success', label: 'Success' },
    { key: 'warning', label: 'Warning' },
    { key: 'danger', label: 'Danger' },
    { key: 'info', label: 'Info' },
  ] as const;

  // Helper to get/set color values dynamically
  function getColor(key: string, variant: 'light' | 'dark'): string {
    return settings[`${key}_${variant}` as keyof FrameworkDisplaySettings] as string;
  }

  function setColor(key: string, variant: 'light' | 'dark', value: string) {
    settings[`${key}_${variant}` as keyof FrameworkDisplaySettings] = value as never;
    notifyChange();
  }

  // Color palette definitions
  const paletteColors = ['primary', 'secondary', 'neutral'] as const;
  const lightVariants = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
  const darkVariants = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
  const transparencyVariants = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];

  // Copy to clipboard functionality
  let copiedVar = $state<string | null>(null);

  async function copyToClipboard(varName: string) {
    try {
      await navigator.clipboard.writeText(varName);
      copiedVar = varName;
      setTimeout(() => {
        copiedVar = null;
      }, 1500);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  }
</script>

<div class="wpea-framework-settings {className}" {style}>
  <div class="wpea-framework-settings__grid">
    <!-- General Card -->
    <Card title="General">
      <div class="wpea-framework-settings__form-group">
        <span class="wpea-label" id="theme-mode-label">Theme Mode</span>
        <Toggle3State
          bind:value={settings.theme_mode}
          options={[
            { value: 'light', label: 'Light' },
            { value: 'system', label: 'System' },
            { value: 'dark', label: 'Dark' }
          ]}
          onChange={(val) => applyThemeMode(val as 'light' | 'dark' | 'system')}
          ariaLabel="Theme mode selection"
        />
        <small class="wpea-help">Choose the color theme for the admin interface.</small>
      </div>
    </Card>

    <!-- Colors Card -->
    <Card title="Colors" subtitle="Override the default theme colors">
      <div class="wpea-framework-settings__color-columns">
        <div class="wpea-framework-settings__color-column">
          <h4 class="wpea-framework-settings__color-heading">Brand</h4>
          <div class="wpea-framework-settings__color-settings">
            {#each brandColors as color}
              <div class="wpea-framework-settings__color-row">
                <span class="wpea-framework-settings__color-label">{color.label}</span>
                <div class="wpea-framework-settings__color-swatches">
                  <div class="wpea-framework-settings__color-swatch-group">
                    <span class="wpea-framework-settings__swatch-label">Light</span>
                    <ColorPicker
                      hex={getColor(color.key, 'light')}
                      bind:isOpen={colorPickerStates[`${color.key}_light`]}
                      label=""
                      isAlpha={false}
                      position="responsive"
                      textInputModes={['hex', 'hsv']}
                      onInput={(e) => e.hex && setColor(color.key, 'light', e.hex)}
                    />
                  </div>
                  <div class="wpea-framework-settings__color-swatch-group">
                    <span class="wpea-framework-settings__swatch-label">Dark</span>
                    <ColorPicker
                      hex={getColor(color.key, 'dark')}
                      bind:isOpen={colorPickerStates[`${color.key}_dark`]}
                      label=""
                      isAlpha={false}
                      position="responsive"
                      textInputModes={['hex', 'hsv']}
                      onInput={(e) => e.hex && setColor(color.key, 'dark', e.hex)}
                    />
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </div>

        <div class="wpea-framework-settings__color-column">
          <h4 class="wpea-framework-settings__color-heading">Semantic</h4>
          <div class="wpea-framework-settings__color-settings">
            {#each semanticColors as color}
              <div class="wpea-framework-settings__color-row">
                <span class="wpea-framework-settings__color-label">{color.label}</span>
                <div class="wpea-framework-settings__color-swatches">
                  <div class="wpea-framework-settings__color-swatch-group">
                    <span class="wpea-framework-settings__swatch-label">Light</span>
                    <ColorPicker
                      hex={getColor(color.key, 'light')}
                      bind:isOpen={colorPickerStates[`${color.key}_light`]}
                      label=""
                      isAlpha={false}
                      position="responsive"
                      textInputModes={['hex', 'hsv']}
                      onInput={(e) => e.hex && setColor(color.key, 'light', e.hex)}
                    />
                  </div>
                  <div class="wpea-framework-settings__color-swatch-group">
                    <span class="wpea-framework-settings__swatch-label">Dark</span>
                    <ColorPicker
                      hex={getColor(color.key, 'dark')}
                      bind:isOpen={colorPickerStates[`${color.key}_dark`]}
                      label=""
                      isAlpha={false}
                      position="responsive"
                      textInputModes={['hex', 'hsv']}
                      onInput={(e) => e.hex && setColor(color.key, 'dark', e.hex)}
                    />
                  </div>
                </div>
              </div>
            {/each}
          </div>
        </div>
      </div>
    </Card>
  </div>

  <!-- Spacing & Typography Card (full width) -->
  <Card title="Spacing & Typography" subtitle="Adjust the spacing and typography scale">
    <!-- Compact Mode Row (full width) -->
    <div class="wpea-framework-settings__compact-row">
      <Switch
        id="compact_mode"
        bind:checked={settings.compact_mode}
        label="Compact Mode"
      />
      {#if settings.compact_mode}
        <div class="wpea-framework-settings__compact-multiplier">
          <span class="wpea-framework-settings__compact-multiplier-label">Multiplier</span>
          <NumberInput
            bind:value={settings.compact_multiplier}
            min={0.5}
            max={0.9}
            step={0.05}
            size="s"
            onchange={() => notifyChange()}
          />
        </div>
      {/if}
    </div>

    <div class="wpea-framework-settings__layout-split">
      <!-- Left Column: Spacing & Radii -->
      <div class="wpea-framework-settings__layout-settings">
        <div class="wpea-framework-settings__layout-row">
          <div class="wpea-framework-settings__layout-label">
            <span>Spacing Base</span>
            <small>Base unit → xs: {computedSpacing.xs}px, sm: {computedSpacing.sm}px, md: {computedSpacing.md}px</small>
          </div>
          <div class="wpea-framework-settings__layout-control">
            <input
              type="range"
              min="4"
              max="16"
              step="1"
              value={sliderState.space_base}
              oninput={(e) => sliderState.space_base = Number((e.target as HTMLInputElement).value)}
              onchange={(e) => { settings.space_base = Number((e.target as HTMLInputElement).value); notifyChange(); }}
            />
            <span class="wpea-framework-settings__layout-value">
              {#if settings.compact_mode}
                {(sliderState.space_base * settings.compact_multiplier).toFixed(1)}px
              {:else}
                {sliderState.space_base}px
              {/if}
            </span>
          </div>
        </div>

        <div class="wpea-framework-settings__layout-row">
          <div class="wpea-framework-settings__layout-label">
            <span>Spacing Scale</span>
            <small>Multiplier ({sliderState.space_scale.toFixed(1)})</small>
          </div>
          <div class="wpea-framework-settings__layout-control">
            <input
              type="range"
              min="1.2"
              max="2.0"
              step="0.1"
              value={sliderState.space_scale}
              oninput={(e) => sliderState.space_scale = Number((e.target as HTMLInputElement).value)}
              onchange={(e) => { settings.space_scale = Number((e.target as HTMLInputElement).value); notifyChange(); }}
            />
            <span class="wpea-framework-settings__layout-value">{sliderState.space_scale.toFixed(1)}</span>
          </div>
        </div>

        <div class="wpea-framework-settings__layout-row">
          <div class="wpea-framework-settings__layout-label">
            <span>Radius Base</span>
            <small>Base radius ({sliderState.radius_base}px)</small>
          </div>
          <div class="wpea-framework-settings__layout-control">
            <input
              type="range"
              min="0"
              max="16"
              step="1"
              value={sliderState.radius_base}
              oninput={(e) => sliderState.radius_base = Number((e.target as HTMLInputElement).value)}
              onchange={(e) => { settings.radius_base = Number((e.target as HTMLInputElement).value); notifyChange(); }}
            />
            <span class="wpea-framework-settings__layout-value">{sliderState.radius_base}px</span>
          </div>
        </div>

        <div class="wpea-framework-settings__layout-row">
          <div class="wpea-framework-settings__layout-label">
            <span>Radius Scale</span>
            <small>Multiplier ({sliderState.radius_scale.toFixed(2)})</small>
          </div>
          <div class="wpea-framework-settings__layout-control">
            <input
              type="range"
              min="1.0"
              max="5.0"
              step="0.1"
              value={sliderState.radius_scale}
              oninput={(e) => sliderState.radius_scale = Number((e.target as HTMLInputElement).value)}
              onchange={(e) => { settings.radius_scale = Number((e.target as HTMLInputElement).value); notifyChange(); }}
            />
            <span class="wpea-framework-settings__layout-value">{sliderState.radius_scale.toFixed(2)}</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Typography -->
      <div class="wpea-framework-settings__layout-settings">
        <div class="wpea-framework-settings__layout-row">
          <div class="wpea-framework-settings__layout-label">
            <span>Font Base</span>
            <small>Base size ({sliderState.font_base}px)</small>
          </div>
          <div class="wpea-framework-settings__layout-control">
            <input
              type="range"
              min="11"
              max="18"
              step="1"
              value={sliderState.font_base}
              oninput={(e) => sliderState.font_base = Number((e.target as HTMLInputElement).value)}
              onchange={(e) => { settings.font_base = Number((e.target as HTMLInputElement).value); notifyChange(); }}
            />
            <span class="wpea-framework-settings__layout-value">{sliderState.font_base}px</span>
          </div>
        </div>

        <div class="wpea-framework-settings__layout-row">
          <div class="wpea-framework-settings__layout-label">
            <span>Type Scale</span>
            <small>Ratio ({sliderState.type_scale.toFixed(2)})</small>
          </div>
          <div class="wpea-framework-settings__layout-control">
            <input
              type="range"
              min="1.1"
              max="1.5"
              step="0.05"
              value={sliderState.type_scale}
              oninput={(e) => sliderState.type_scale = Number((e.target as HTMLInputElement).value)}
              onchange={(e) => { settings.type_scale = Number((e.target as HTMLInputElement).value); notifyChange(); }}
            />
            <span class="wpea-framework-settings__layout-value">{sliderState.type_scale.toFixed(2)}</span>
          </div>
        </div>
      </div>
    </div>
  </Card>

  <!-- Preview Card (full width) -->
  <Card title="Preview" subtitle="Live preview of your settings">
    <div class="wpea-framework-settings__preview-split">
      <!-- Left Column: Samples -->
      <div class="wpea-framework-settings__preview-samples">
        <div class="wpea-framework-settings__preview-section">
          <span class="wpea-framework-settings__preview-label">Typography</span>
          <div class="wpea-framework-settings__preview-typography">
            <h1 class="wpea-framework-settings__preview-h1">Heading 1</h1>
            <h2 class="wpea-framework-settings__preview-h2">Heading 2</h2>
            <h3 class="wpea-framework-settings__preview-h3">Heading 3</h3>
          </div>
        </div>

        <div class="wpea-framework-settings__preview-section">
          <span class="wpea-framework-settings__preview-label">Spacing</span>
          <div class="wpea-framework-settings__preview-spacing">
            <div class="wpea-framework-settings__spacing-bar wpea-framework-settings__spacing-bar--xs"><span>xs</span></div>
            <div class="wpea-framework-settings__spacing-bar wpea-framework-settings__spacing-bar--sm"><span>sm</span></div>
            <div class="wpea-framework-settings__spacing-bar wpea-framework-settings__spacing-bar--md"><span>md</span></div>
            <div class="wpea-framework-settings__spacing-bar wpea-framework-settings__spacing-bar--lg"><span>lg</span></div>
            <div class="wpea-framework-settings__spacing-bar wpea-framework-settings__spacing-bar--xl"><span>xl</span></div>
          </div>
        </div>

        <div class="wpea-framework-settings__preview-section">
          <span class="wpea-framework-settings__preview-label">Radii</span>
          <div class="wpea-framework-settings__preview-radii">
            <div class="wpea-framework-settings__radius-box wpea-framework-settings__radius-box--sm"><span>sm</span></div>
            <div class="wpea-framework-settings__radius-box wpea-framework-settings__radius-box--md"><span>md</span></div>
            <div class="wpea-framework-settings__radius-box wpea-framework-settings__radius-box--lg"><span>lg</span></div>
          </div>
        </div>
      </div>

      <!-- Right Column: Color Palettes -->
      <div class="wpea-framework-settings__preview-extra">
        {#each paletteColors as colorName}
          <div class="wpea-framework-settings__palette">
            <span class="wpea-framework-settings__palette-label">{colorName}</span>
            <div class="wpea-framework-settings__palette-grid">
              <!-- Base color: 2 cols x 3 rows -->
              <button
                type="button"
                class="wpea-framework-settings__palette-base"
                class:wpea-framework-settings__palette-swatch--copied={copiedVar === `var(--wpea-color--${colorName})`}
                style="background-color: var(--wpea-color--{colorName});"
                onclick={() => copyToClipboard(`var(--wpea-color--${colorName})`)}
                title={`var(--wpea-color--${colorName})`}
              >
                <span class="wpea-framework-settings__palette-tooltip">
                  {copiedVar === `var(--wpea-color--${colorName})` ? 'Copied!' : `var(--wpea-color--${colorName})`}
                </span>
              </button>

              <!-- Light variants: 10 columns -->
              {#each lightVariants as n, i}
                <button
                  type="button"
                  class="wpea-framework-settings__palette-swatch"
                  class:wpea-framework-settings__palette-swatch--copied={copiedVar === `var(--wpea-color--${colorName}-l-${n})`}
                  style="background-color: var(--wpea-color--{colorName}-l-{n}); grid-row: 1; grid-column: {i + 3};"
                  onclick={() => copyToClipboard(`var(--wpea-color--${colorName}-l-${n})`)}
                  title={`var(--wpea-color--${colorName}-l-${n})`}
                >
                  <span class="wpea-framework-settings__palette-tooltip">
                    {copiedVar === `var(--wpea-color--${colorName}-l-${n})` ? 'Copied!' : `var(--wpea-color--${colorName}-l-${n})`}
                  </span>
                </button>
              {/each}

              <!-- Dark variants: 10 columns -->
              {#each darkVariants as n, i}
                <button
                  type="button"
                  class="wpea-framework-settings__palette-swatch"
                  class:wpea-framework-settings__palette-swatch--copied={copiedVar === `var(--wpea-color--${colorName}-d-${n})`}
                  style="background-color: var(--wpea-color--{colorName}-d-{n}); grid-row: 2; grid-column: {i + 3};"
                  onclick={() => copyToClipboard(`var(--wpea-color--${colorName}-d-${n})`)}
                  title={`var(--wpea-color--${colorName}-d-${n})`}
                >
                  <span class="wpea-framework-settings__palette-tooltip">
                    {copiedVar === `var(--wpea-color--${colorName}-d-${n})` ? 'Copied!' : `var(--wpea-color--${colorName}-d-${n})`}
                  </span>
                </button>
              {/each}

              <!-- Transparency variants: 10 columns -->
              {#each transparencyVariants as n, i}
                <button
                  type="button"
                  class="wpea-framework-settings__palette-swatch wpea-framework-settings__palette-swatch--transparent"
                  class:wpea-framework-settings__palette-swatch--copied={copiedVar === `var(--wpea-color--${colorName}-t-${n})`}
                  style="--swatch-color: var(--wpea-color--{colorName}-t-{n}); grid-row: 3; grid-column: {i + 3};"
                  onclick={() => copyToClipboard(`var(--wpea-color--${colorName}-t-${n})`)}
                  title={`var(--wpea-color--${colorName}-t-${n})`}
                >
                  <span class="wpea-framework-settings__palette-tooltip">
                    {copiedVar === `var(--wpea-color--${colorName}-t-${n})` ? 'Copied!' : `var(--wpea-color--${colorName}-t-${n})`}
                  </span>
                </button>
              {/each}
            </div>
          </div>
        {/each}
      </div>
    </div>
  </Card>

  <div class="wpea-framework-settings__actions">
    <DoubleOptInButton
      defaultLabel="Reset All to Defaults"
      confirmLabel="Confirm Reset?"
      onClick={resetAllToDefaults}
    />
  </div>
</div>

<style>
  .wpea-framework-settings {
    container-type: inline-size;
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .wpea-framework-settings__grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--wpea-space--lg);
  }

  @container (max-width: 900px) {
    .wpea-framework-settings__grid {
      grid-template-columns: 1fr;
    }
  }

  .wpea-framework-settings__form-group {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--wpea-space--xs);
  }

  /* Compact Mode Row */
  .wpea-framework-settings__compact-row {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--xl);
    padding-bottom: var(--wpea-space--md);
    margin-bottom: var(--wpea-space--sm);
    border-bottom: 1px solid var(--wpea-surface--border);
  }

  .wpea-framework-settings__compact-multiplier {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__compact-multiplier-label {
    font-size: var(--wpea-text--sm);
    color: var(--wpea-surface--text-muted);
  }

  /* Color Columns */
  .wpea-framework-settings__color-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--wpea-space--xl);
  }

  @container (max-width: 600px) {
    .wpea-framework-settings__color-columns {
      grid-template-columns: 1fr;
    }
  }

  .wpea-framework-settings__color-column {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__color-heading {
    margin: 0;
    font-size: var(--wpea-text--sm);
    font-weight: 600;
    color: var(--wpea-surface--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .wpea-framework-settings__color-settings {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__color-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--wpea-space--md);
  }

  .wpea-framework-settings__color-label {
    font-size: var(--wpea-text--sm);
    font-weight: 500;
    min-width: calc(var(--wpea-space--xl) * 2);
  }

  .wpea-framework-settings__color-swatches {
    display: flex;
    gap: var(--wpea-space--md);
  }

  .wpea-framework-settings__color-swatch-group {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--xs);
  }

  .wpea-framework-settings__swatch-label {
    font-size: var(--wpea-text--xs);
    color: var(--wpea-surface--text-muted);
    text-transform: uppercase;
  }

  /* Color Picker Wrapper Styling - using the library's CSS custom properties */
  .wpea-framework-settings__color-swatch-group :global(.color-picker) {
    /* Picker dimensions */
    --picker-width: 180px;
    --picker-height: 120px;
    --slider-width: 16px;
    /* Theme-aware colors using the library's CSS variables */
    --cp-bg-color: var(--wpea-surface--panel);
    --cp-border-color: var(--wpea-surface--border);
    --cp-input-color: var(--wpea-surface--muted);
    --cp-text-color: var(--wpea-surface--text);
    --cp-button-hover-color: var(--wpea-surface--bg);
    --focus-color: var(--wpea-color--primary);
    --picker-z-index: 1000;
  }

  .wpea-framework-settings__color-swatch-group :global(.color-picker > button) {
    width: var(--wpea-space--xl);
    height: var(--wpea-space--xl);
    border-radius: var(--wpea-radius--md);
    border: 2px solid var(--wpea-surface--border);
    cursor: pointer;
    transition: border-color var(--wpea-anim-duration--fast), box-shadow var(--wpea-anim-duration--fast);
  }

  .wpea-framework-settings__color-swatch-group :global(.color-picker > button:hover) {
    border-color: var(--wpea-color--primary);
  }

  .wpea-framework-settings__color-swatch-group :global(.color-picker > button:focus) {
    outline: none;
    border-color: var(--wpea-color--primary);
    box-shadow: 0 0 0 3px var(--wpea-color--primary-t-30);
  }

  .wpea-framework-settings__color-swatch-group :global(.color-picker .wrapper) {
    box-shadow: var(--wpea-shadow--lg);
  }

  /* Layout Split */
  .wpea-framework-settings__layout-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--wpea-space--xl);
  }

  @container (max-width: 700px) {
    .wpea-framework-settings__layout-split {
      grid-template-columns: 1fr;
    }
  }

  .wpea-framework-settings__layout-settings {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  .wpea-framework-settings__layout-row {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xs);
  }

  .wpea-framework-settings__layout-label {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xs);
  }

  .wpea-framework-settings__layout-label span {
    font-weight: 500;
    font-size: var(--wpea-text--sm);
  }

  .wpea-framework-settings__layout-label small {
    font-size: var(--wpea-text--xs);
    color: var(--wpea-surface--text-muted);
  }

  .wpea-framework-settings__layout-control {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__layout-control input[type="range"] {
    flex: 1;
    height: 6px;
    border-radius: 3px;
    background: transparent;
    appearance: none;
    cursor: pointer;
  }

  .wpea-framework-settings__layout-control input[type="range"]::-webkit-slider-runnable-track {
    height: 6px;
    border-radius: 3px;
    background: light-dark(
      var(--wpea-color--neutral-l-7),
      var(--wpea-color--neutral-d-5)
    );
  }

  .wpea-framework-settings__layout-control input[type="range"]::-moz-range-track {
    height: 6px;
    border-radius: 3px;
    background: light-dark(
      var(--wpea-color--neutral-l-7),
      var(--wpea-color--neutral-d-5)
    );
  }

  .wpea-framework-settings__layout-control input[type="range"]::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--wpea-color--primary);
    cursor: pointer;
    border: 2px solid var(--wpea-surface--panel);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    margin-top: -6px;
  }

  .wpea-framework-settings__layout-control input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--wpea-color--primary);
    cursor: pointer;
    border: 2px solid var(--wpea-surface--panel);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }

  .wpea-framework-settings__layout-control input[type="range"]:focus {
    outline: none;
  }

  .wpea-framework-settings__layout-control input[type="range"]:focus::-webkit-slider-thumb {
    box-shadow: 0 0 0 3px color-mix(in oklab, var(--wpea-color--primary), transparent 70%);
  }

  .wpea-framework-settings__layout-value {
    min-width: var(--wpea-space--2xl);
    font-size: var(--wpea-text--sm);
    font-weight: 500;
    font-family: var(--wpea-font-mono, monospace);
    color: var(--wpea-surface--text);
    text-align: right;
  }

  /* Preview Panel */
  .wpea-framework-settings__layout-preview {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
    padding: var(--wpea-space--lg);
    background: var(--wpea-surface--bg);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--md);
  }

  .wpea-framework-settings__preview-section {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__preview-label {
    font-size: var(--wpea-text--xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--wpea-surface--text-muted);
  }

  .wpea-framework-settings__preview-typography {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xs);
  }

  .wpea-framework-settings__preview-h1 {
    margin: 0;
    font-size: var(--wpea-text--2xl);
    font-weight: 600;
    line-height: 1.2;
  }

  .wpea-framework-settings__preview-h2 {
    margin: 0;
    font-size: var(--wpea-text--xl);
    font-weight: 600;
    line-height: 1.2;
  }

  .wpea-framework-settings__preview-h3 {
    margin: 0;
    font-size: var(--wpea-text--lg);
    font-weight: 600;
    line-height: 1.2;
  }

  /* Spacing Preview */
  .wpea-framework-settings__preview-spacing {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__spacing-bar {
    display: flex;
    align-items: center;
    gap: var(--wpea-space--sm);
  }

  .wpea-framework-settings__spacing-bar::before {
    content: '';
    height: var(--wpea-space--md);
    background: var(--wpea-color--secondary);
    flex-shrink: 0;
  }

  .wpea-framework-settings__spacing-bar span {
    font-size: var(--wpea-text--xs);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--wpea-surface--text-muted);
    white-space: nowrap;
  }

  .wpea-framework-settings__spacing-bar--xs::before {
    width: var(--wpea-space--xs);
  }

  .wpea-framework-settings__spacing-bar--sm::before {
    width: var(--wpea-space--sm);
  }

  .wpea-framework-settings__spacing-bar--md::before {
    width: var(--wpea-space--md);
  }

  .wpea-framework-settings__spacing-bar--lg::before {
    width: var(--wpea-space--lg);
  }

  .wpea-framework-settings__spacing-bar--xl::before {
    width: var(--wpea-space--xl);
  }

  /* Radii Preview */
  .wpea-framework-settings__preview-radii {
    display: flex;
    gap: var(--wpea-space--md);
    flex-wrap: wrap;
  }

  .wpea-framework-settings__radius-box {
    display: flex;
    align-items: center;
    justify-content: center;
    width: var(--wpea-space--4xl);
    height: var(--wpea-space--4xl);
    background: var(--wpea-surface--panel);
    border: 1px solid var(--wpea-surface--border);
    box-shadow: var(--wpea-shadow--md);
    font-size: var(--wpea-text--xs);
    font-weight: 600;
    text-transform: uppercase;
    color: var(--wpea-surface--text-muted);
  }

  .wpea-framework-settings__radius-box--sm {
    border-radius: var(--wpea-radius--sm);
  }

  .wpea-framework-settings__radius-box--md {
    border-radius: var(--wpea-radius--md);
  }

  .wpea-framework-settings__radius-box--lg {
    border-radius: var(--wpea-radius--lg);
  }

  /* Preview Split */
  .wpea-framework-settings__preview-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--wpea-space--xl);
  }

  @container (max-width: 700px) {
    .wpea-framework-settings__preview-split {
      grid-template-columns: 1fr;
    }
  }

  .wpea-framework-settings__preview-samples {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
    padding: var(--wpea-space--lg);
    background: var(--wpea-surface--bg);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--md);
  }

  .wpea-framework-settings__preview-extra {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--lg);
  }

  /* Color Palette */
  .wpea-framework-settings__palette {
    display: flex;
    flex-direction: column;
    gap: var(--wpea-space--xs);
  }

  .wpea-framework-settings__palette-label {
    font-size: var(--wpea-text--xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--wpea-surface--text-muted);
  }

  .wpea-framework-settings__palette-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 2px;
  }

  .wpea-framework-settings__palette-base {
    grid-column: 1 / 3;
    grid-row: 1 / 4;
    position: relative;
    border: none;
    border-radius: var(--wpea-radius--sm);
    cursor: pointer;
    min-height: var(--wpea-space--3xl);
    transition: transform var(--wpea-anim-duration--fast), box-shadow var(--wpea-anim-duration--fast);
  }

  .wpea-framework-settings__palette-base:hover {
    transform: scale(1.02);
    box-shadow: var(--wpea-shadow--md);
    z-index: 1;
  }

  .wpea-framework-settings__palette-base:hover .wpea-framework-settings__palette-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
  }

  .wpea-framework-settings__palette-swatch {
    position: relative;
    border: none;
    padding: 0;
    cursor: pointer;
    aspect-ratio: 1;
    min-height: var(--wpea-space--lg);
    transition: transform var(--wpea-anim-duration--fast), box-shadow var(--wpea-anim-duration--fast);
  }

  /* Corner radius for first and last swatches in each row */
  .wpea-framework-settings__palette-swatch[style*="grid-column: 12"][style*="grid-row: 1"] {
    border-top-right-radius: var(--wpea-radius--sm);
  }

  .wpea-framework-settings__palette-swatch[style*="grid-column: 12"][style*="grid-row: 3"] {
    border-bottom-right-radius: var(--wpea-radius--sm);
  }

  .wpea-framework-settings__palette-swatch:hover {
    transform: scale(1.15);
    box-shadow: var(--wpea-shadow--md);
    z-index: 2;
    border-radius: var(--wpea-radius--sm);
  }

  .wpea-framework-settings__palette-swatch:hover .wpea-framework-settings__palette-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
  }

  .wpea-framework-settings__palette-swatch--copied {
    outline: 2px solid var(--wpea-color--success);
    outline-offset: 1px;
    z-index: 3;
  }

  .wpea-framework-settings__palette-tooltip {
    position: absolute;
    bottom: calc(100% + var(--wpea-space--xs));
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    background: var(--wpea-surface--panel);
    border: 1px solid var(--wpea-surface--border);
    border-radius: var(--wpea-radius--sm);
    padding: var(--wpea-space--xs) var(--wpea-space--sm);
    font-size: var(--wpea-text--xs);
    font-family: var(--wpea-font-mono, monospace);
    color: var(--wpea-surface--text);
    white-space: nowrap;
    box-shadow: var(--wpea-shadow--md);
    opacity: 0;
    visibility: hidden;
    transition: opacity var(--wpea-anim-duration--fast), transform var(--wpea-anim-duration--fast), visibility var(--wpea-anim-duration--fast);
    pointer-events: none;
    z-index: 100;
  }

  /* Transparency swatches need a checkered background */
  .wpea-framework-settings__palette-swatch--transparent {
    background-image:
      linear-gradient(45deg, #ccc 25%, transparent 25%),
      linear-gradient(-45deg, #ccc 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, #ccc 75%),
      linear-gradient(-45deg, transparent 75%, #ccc 75%);
    background-size: 6px 6px;
    background-position: 0 0, 0 3px, 3px -3px, -3px 0px;
    background-color: #fff;
  }

  .wpea-framework-settings__palette-swatch--transparent::after {
    content: '';
    position: absolute;
    inset: 0;
    background-color: var(--swatch-color);
  }

  /* Actions */
  .wpea-framework-settings__actions {
    display: flex;
    justify-content: flex-end;
  }
</style>
