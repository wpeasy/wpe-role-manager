import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import { resolve } from 'path';

/**
 * Vite config for main admin app
 * Builds as ES module for wp_enqueue_script_module
 */
export default defineConfig({
  plugins: [svelte()],

  build: {
    outDir: 'assets/dist/main',
    emptyOutDir: true,
    cssCodeSplit: false, // Single CSS file

    rollupOptions: {
      input: resolve(__dirname, 'src-svelte/apps/main/main.ts'),
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name][extname]',
        format: 'es',
      },
      // Don't bundle WPE_RM - it's loaded separately
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
      '@stores': resolve(__dirname, './src-svelte/stores'),
    },
  },
});
