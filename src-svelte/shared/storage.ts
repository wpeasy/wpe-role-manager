/**
 * localStorage helpers for persistent state
 *
 * @package WP_Easy\RoleManager
 */

const PREFIX = 'wpe_rm_';

/**
 * Save a value to localStorage with plugin prefix
 */
export function saveToStorage<T>(key: string, value: T): void {
  try {
    localStorage.setItem(`${PREFIX}${key}`, JSON.stringify(value));
  } catch {
    // localStorage not available or quota exceeded
    console.warn('[WPE_RM] Failed to save to localStorage:', key);
  }
}

/**
 * Load a value from localStorage with plugin prefix
 */
export function loadFromStorage<T>(key: string): T | null {
  try {
    const stored = localStorage.getItem(`${PREFIX}${key}`);
    return stored ? JSON.parse(stored) : null;
  } catch {
    // localStorage not available or invalid JSON
    return null;
  }
}

/**
 * Remove a value from localStorage with plugin prefix
 */
export function removeFromStorage(key: string): void {
  try {
    localStorage.removeItem(`${PREFIX}${key}`);
  } catch {
    // localStorage not available
  }
}

/**
 * Clear all plugin-related localStorage items
 */
export function clearPluginStorage(): void {
  try {
    const keysToRemove: string[] = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key?.startsWith(PREFIX)) {
        keysToRemove.push(key);
      }
    }
    keysToRemove.forEach((key) => localStorage.removeItem(key));
  } catch {
    // localStorage not available
  }
}
