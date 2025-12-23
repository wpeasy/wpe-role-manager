import { defineConfig } from 'vite';
import { resolve } from 'path';

/**
 * Vite config for shared module
 * Builds as ES module for wp_enqueue_script_module compatibility
 */
export default defineConfig({
  build: {
    outDir: 'assets/dist',
    emptyOutDir: false, // Don't clear - other builds add here
    lib: {
      entry: resolve(__dirname, 'src-svelte/shared/index.ts'),
      formats: ['es'],
      fileName: () => 'shared.js',
    },
    rollupOptions: {
      output: {
        // Ensure it's a proper ES module
        format: 'es',
      },
    },
    sourcemap: true,
    minify: 'esbuild',
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, './src-svelte'),
      '@shared': resolve(__dirname, './src-svelte/shared'),
    },
  },
});
