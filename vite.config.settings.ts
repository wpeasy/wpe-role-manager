import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import { resolve } from 'path';

/**
 * Vite config for settings app
 * Builds as ES module for wp_enqueue_script_module
 */
export default defineConfig({
  plugins: [svelte()],

  build: {
    outDir: 'assets/dist/settings',
    emptyOutDir: true,
    cssCodeSplit: false,

    rollupOptions: {
      input: resolve(__dirname, 'src-svelte/apps/settings/main.ts'),
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name][extname]',
        format: 'es',
      },
      external: (id) => id === 'WPE_RM',
    },

    sourcemap: true,
    minify: 'esbuild',
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, './src-svelte'),
      '@shared': resolve(__dirname, './src-svelte/shared'),
      '@components': resolve(__dirname, './src-svelte/components'),
    },
  },
});
