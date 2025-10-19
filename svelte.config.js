import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

export default {
  // Consult https://svelte.dev/docs#compile-time-svelte-preprocess
  // for more information about preprocessors
  preprocess: vitePreprocess(),

  compilerOptions: {
    // Svelte 5 uses runes by default
    runes: true,
  },
};
