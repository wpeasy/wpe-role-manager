/**
 * Theme management for WPE Role Manager
 *
 * @package WP_Easy\RoleManager
 */

import { emit } from './events';
import { saveToStorage, loadFromStorage } from './storage';
import { api } from './api';

export type ThemeMode = 'light' | 'dark' | 'auto';

const STORAGE_KEY = 'theme';

/**
 * Apply theme to the document
 */
function applyToDocument(mode: ThemeMode): void {
  const root = document.documentElement;

  switch (mode) {
    case 'light':
      root.style.setProperty('color-scheme', 'light only');
      break;
    case 'dark':
      root.style.setProperty('color-scheme', 'dark only');
      break;
    default:
      root.style.setProperty('color-scheme', 'light dark');
  }
}

export const theme = {
  /**
   * Get current theme mode
   */
  get(): ThemeMode {
    // First try localStorage for instant access
    const stored = loadFromStorage<ThemeMode>(STORAGE_KEY);
    if (stored && ['light', 'dark', 'auto'].includes(stored)) {
      return stored;
    }

    // Fall back to PHP-provided settings
    const settings = window.wpeRmData?.settings;
    const colorScheme = settings?.color_scheme as ThemeMode | undefined;
    return colorScheme || 'auto';
  },

  /**
   * Set theme mode (saves to localStorage and syncs to server)
   */
  set(mode: ThemeMode): void {
    saveToStorage(STORAGE_KEY, mode);
    applyToDocument(mode);
    emit('theme:changed', { mode });

    // Sync to server (fire and forget)
    api.post('/settings', { color_scheme: mode }).catch((error) => {
      console.warn('[WPE_RM] Failed to sync theme to server:', error);
    });
  },

  /**
   * Apply theme to document without saving
   */
  apply(mode: ThemeMode): void {
    applyToDocument(mode);
  },

  /**
   * Initialize theme on page load
   */
  init(): void {
    const mode = this.get();
    this.apply(mode);

    // Sync localStorage with server-provided settings
    const serverMode = window.wpeRmData?.settings?.color_scheme as ThemeMode | undefined;
    if (serverMode && serverMode !== loadFromStorage<ThemeMode>(STORAGE_KEY)) {
      saveToStorage(STORAGE_KEY, serverMode);
    }
  },
};
