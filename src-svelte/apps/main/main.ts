/**
 * WP Easy Role Manager - Main Admin App Entry Point
 *
 * @package WP_Easy\RoleManager
 */

import { mount } from 'svelte';
import App from './App.svelte';

// Ensure shared module is loaded
if (!window.WPE_RM) {
  console.error('[WPE_RM] Shared module not loaded. Ensure shared.js is enqueued before this script.');
}

// Get the target element
const target = document.getElementById('wpe-rm-app');

if (!target) {
  console.error('[WPE_RM] Target element #wpe-rm-app not found');
} else {
  // Remove the loading indicator
  const loadingEl = target.querySelector('.wpe-rm-loading');
  if (loadingEl) {
    loadingEl.remove();
  }

  // Mount the app
  const app = mount(App, {
    target,
    props: {},
  });

  // Expose app instance for debugging
  if (import.meta.env.DEV) {
    (window as unknown as Record<string, unknown>).wpeRmApp = app;
  }
}
