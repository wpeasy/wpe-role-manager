/**
 * WP Easy Role Manager - Settings App Entry Point
 *
 * Standalone settings page app.
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
const target = document.getElementById('wpe-rm-settings-app');

if (!target) {
  console.error('[WPE_RM] Target element #wpe-rm-settings-app not found');
} else {
  // Remove loading indicator if present
  const loadingEl = target.querySelector('.wpe-rm-loading');
  if (loadingEl) {
    loadingEl.remove();
  }

  // Mount the app
  mount(App, {
    target,
    props: {},
  });
}
