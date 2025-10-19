/**
 * WP Easy Role Manager - Svelte 5 Admin App Entry Point
 *
 * @package WP_Easy\RoleManager
 */

import { mount } from 'svelte';
import App from './App.svelte';

// Get the target element
const target = document.getElementById('wpe-rm-app');

// Remove the loading indicator
const loadingEl = target.querySelector('.wpe-rm-loading');
if (loadingEl) {
  loadingEl.remove();
}

// Mount the app to the #wpe-rm-app element
const app = mount(App, {
  target,
  props: {
    // Pass WordPress localized data to the app
    wpData: window.wpeRmAdmin || {},
  },
});

export default app;
