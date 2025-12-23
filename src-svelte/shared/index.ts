/**
 * WPE Role Manager Shared Module
 *
 * Exposes utilities on window.WPE_RM for cross-module access.
 * This module is built as an IIFE and must be loaded before any app modules.
 *
 * @package WP_Easy\RoleManager
 */

import { api, type WPEData, type ApiError } from './api';
import { emit, on, once, type EventMap, type Subscription } from './events';
import { theme, type ThemeMode } from './theme';
import { density } from './density';
import { displaySettings, type DisplaySettings, defaultDisplaySettings } from './displaySettings';
import {
  saveToStorage,
  loadFromStorage,
  removeFromStorage,
  clearPluginStorage,
} from './storage';
import * as utils from './utils';

/**
 * WPE_RM interface exposed on window
 */
export interface WPERM {
  api: typeof api;
  emit: typeof emit;
  on: typeof on;
  once: typeof once;
  theme: typeof theme;
  density: typeof density;
  displaySettings: typeof displaySettings;
  storage: {
    save: typeof saveToStorage;
    load: typeof loadFromStorage;
    remove: typeof removeFromStorage;
    clear: typeof clearPluginStorage;
  };
  utils: typeof utils;
  settings: Record<string, unknown>;
  i18n: Record<string, string>;
  version: string;
}

declare global {
  interface Window {
    WPE_RM: WPERM;
    wpeRmData: WPEData;
  }
}

// Build the shared module object
const WPE_RM: WPERM = {
  api,
  emit,
  on,
  once,
  theme,
  density,
  displaySettings,
  storage: {
    save: saveToStorage,
    load: loadFromStorage,
    remove: removeFromStorage,
    clear: clearPluginStorage,
  },
  utils,
  settings: window.wpeRmData?.settings || {},
  i18n: window.wpeRmData?.i18n || {},
  version: window.wpeRmData?.version || '0.0.0',
};

// Initialize display settings and density immediately to prevent flash
// These run synchronously before any Svelte app mounts
// Note: displaySettings handles theme_mode, so we don't call theme.init() separately
displaySettings.init();
density.init();

// Expose on window
window.WPE_RM = WPE_RM;

// Log initialization in development
if (typeof window !== 'undefined' && window.wpeRmData) {
  console.log('[WPE_RM] Shared module initialized', {
    version: WPE_RM.version,
    theme: theme.get(),
    compact: density.get(),
  });
}

// Re-export for ES module imports (when not using window.WPE_RM)
export { api, emit, on, once, theme, density, displaySettings, defaultDisplaySettings, utils };
export type { WPEData, ApiError, EventMap, Subscription, ThemeMode, DisplaySettings };
