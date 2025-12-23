/**
 * Display Settings management for WPE Role Manager
 *
 * Handles all framework appearance settings including:
 * - Theme mode (light/dark/system)
 * - Compact mode
 * - Spacing, typography, radius scales
 * - Color overrides
 *
 * @package WP_Easy\RoleManager
 */

import { emit } from './events';
import { api } from './api';

// Storage key used by both PHP and JS
const STORAGE_KEY = 'wpe_rm_display_settings';

/**
 * Display settings type definition
 */
export type DisplaySettings = {
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

/**
 * Default display settings
 */
export const defaultDisplaySettings: DisplaySettings = {
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

/**
 * Load settings from localStorage
 */
function loadFromLocalStorage(): DisplaySettings | null {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return { ...defaultDisplaySettings, ...JSON.parse(stored) };
    }
  } catch {
    // Ignore parse errors
  }
  return null;
}

/**
 * Save settings to localStorage
 */
function saveToLocalStorage(settings: DisplaySettings): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
  } catch {
    // Ignore storage errors
  }
}

/**
 * Apply display settings to the DOM
 * Updates the <style id="wpea-display-overrides"> element that PHP creates
 */
function applyToDocument(settings: DisplaySettings): void {
  // Apply theme mode
  if (settings.theme_mode === 'light') {
    document.documentElement.style.setProperty('color-scheme', 'light only');
  } else if (settings.theme_mode === 'dark') {
    document.documentElement.style.setProperty('color-scheme', 'dark only');
  } else {
    document.documentElement.style.setProperty('color-scheme', 'light dark');
  }

  // Apply compact mode to body AND admin wrapper(s)
  // The WPEA framework expects .wpea-compact on the container, not just body
  const adminWrappers = document.querySelectorAll('.wpe-rm-admin');
  if (settings.compact_mode) {
    document.body.classList.add('wpea-compact');
    adminWrappers.forEach(el => el.classList.add('wpea-compact'));
  } else {
    document.body.classList.remove('wpea-compact');
    adminWrappers.forEach(el => el.classList.remove('wpea-compact'));
  }

  // Create or update style element for display overrides
  let styleEl = document.getElementById('wpea-display-overrides') as HTMLStyleElement | null;
  if (!styleEl) {
    styleEl = document.createElement('style');
    styleEl.id = 'wpea-display-overrides';
    document.head.appendChild(styleEl);
  }

  styleEl.textContent = `
    :root {
      --wpea-space-base: ${settings.space_base}px !important;
      --wpea-space-scale: ${settings.space_scale} !important;
      --wpea-space-compact: ${settings.compact_multiplier} !important;
      --wpea-fs-base: ${settings.font_base}px !important;
      --wpea-type-scale: ${settings.type_scale} !important;
      --wpea-radius-base: ${settings.radius_base}px !important;
      --wpea-radius-scale: ${settings.radius_scale} !important;
      --wpea-color--primary-light-override: ${settings.primary_light};
      --wpea-color--primary-dark-override: ${settings.primary_dark};
      --wpea-color--secondary-light-override: ${settings.secondary_light};
      --wpea-color--secondary-dark-override: ${settings.secondary_dark};
      --wpea-color--neutral-light-override: ${settings.neutral_light};
      --wpea-color--neutral-dark-override: ${settings.neutral_dark};
      --wpea-color--success-light-override: ${settings.success_light};
      --wpea-color--success-dark-override: ${settings.success_dark};
      --wpea-color--warning-light-override: ${settings.warning_light};
      --wpea-color--warning-dark-override: ${settings.warning_dark};
      --wpea-color--danger-light-override: ${settings.danger_light};
      --wpea-color--danger-dark-override: ${settings.danger_dark};
      --wpea-color--info-light-override: ${settings.info_light};
      --wpea-color--info-dark-override: ${settings.info_dark};
    }
  `;
}

export const displaySettings = {
  /**
   * Get current display settings
   * Loads from localStorage first, falls back to defaults
   */
  get(): DisplaySettings {
    // First try localStorage for instant access
    const stored = loadFromLocalStorage();
    if (stored) {
      return stored;
    }

    // Fall back to server-provided settings
    const serverSettings = window.wpeRmData?.settings?.framework_settings;
    if (serverSettings && typeof serverSettings === 'object') {
      return { ...defaultDisplaySettings, ...serverSettings };
    }

    return { ...defaultDisplaySettings };
  },

  /**
   * Set display settings (saves to localStorage, applies to DOM, syncs to server)
   */
  set(settings: Partial<DisplaySettings>): void {
    const current = this.get();
    const updated = { ...current, ...settings };

    saveToLocalStorage(updated);
    applyToDocument(updated);
    emit('displaySettings:changed', updated);

    // Sync to server (fire and forget)
    api.post('/settings', { framework_settings: updated }).catch((error) => {
      console.warn('[WPE_RM] Failed to sync display settings to server:', error);
    });
  },

  /**
   * Apply settings to document without saving
   * Used for live preview during slider drags
   */
  apply(settings: DisplaySettings): void {
    applyToDocument(settings);
  },

  /**
   * Initialize display settings on page load
   * Called from shared/index.ts
   */
  init(): void {
    // Load current settings (localStorage or server)
    const settings = this.get();

    // Apply to document (PHP already rendered initial CSS, but this ensures consistency)
    applyToDocument(settings);

    // Sync localStorage with server-provided settings
    const serverSettings = window.wpeRmData?.settings?.framework_settings;
    if (serverSettings && typeof serverSettings === 'object') {
      const merged = { ...defaultDisplaySettings, ...serverSettings };
      saveToLocalStorage(merged);
    }
  },

  /**
   * Reset to defaults
   */
  reset(): void {
    this.set(defaultDisplaySettings);
  },

  /**
   * Default settings for reference
   */
  defaults: defaultDisplaySettings,
};
