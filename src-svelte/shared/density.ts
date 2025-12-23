/**
 * Density (compact mode) management for WPE Role Manager
 *
 * @package WP_Easy\RoleManager
 */

import { emit } from './events';
import { saveToStorage, loadFromStorage } from './storage';
import { api } from './api';

const STORAGE_KEY = 'compact';

/**
 * Apply density to the document
 * Uses .wpea-compact class on body per WPEA framework CSS
 */
function applyToDocument(compact: boolean): void {
  if (compact) {
    document.body.classList.add('wpea-compact');
  } else {
    document.body.classList.remove('wpea-compact');
  }
}

export const density = {
  /**
   * Get current compact mode state
   */
  get(): boolean {
    // First try localStorage for instant access
    const stored = loadFromStorage<boolean>(STORAGE_KEY);
    if (stored !== null) {
      return stored;
    }

    // Fall back to PHP-provided settings
    return window.wpeRmData?.settings?.compact_mode === true;
  },

  /**
   * Set compact mode (saves to localStorage and syncs to server)
   */
  set(compact: boolean): void {
    saveToStorage(STORAGE_KEY, compact);
    applyToDocument(compact);
    emit('density:changed', { compact });

    // Sync to server (fire and forget)
    api.post('/settings', { compact_mode: compact }).catch((error) => {
      console.warn('[WPE_RM] Failed to sync density to server:', error);
    });
  },

  /**
   * Apply density to document without saving
   */
  apply(compact: boolean): void {
    applyToDocument(compact);
  },

  /**
   * Initialize density on page load
   */
  init(): void {
    const compact = this.get();
    this.apply(compact);

    // Sync localStorage with server-provided settings
    const serverCompact = window.wpeRmData?.settings?.compact_mode === true;
    const storedCompact = loadFromStorage<boolean>(STORAGE_KEY);
    if (storedCompact === null || storedCompact !== serverCompact) {
      saveToStorage(STORAGE_KEY, serverCompact);
    }
  },
};
