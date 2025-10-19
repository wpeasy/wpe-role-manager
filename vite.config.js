import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import { resolve } from 'path';

export default defineConfig({
  plugins: [svelte()],

  build: {
    // Output to assets/dist
    outDir: 'assets/dist',
    emptyOutDir: true,

    // Generate manifest for WordPress
    manifest: true,

    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'src-svelte/main.js'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
      },
    },

    // Don't minify for easier debugging in alpha
    minify: false,

    // Generate sourcemaps
    sourcemap: true,
  },

  // Configure dev server
  server: {
    port: 3000,
    strictPort: false,
  },

  // Resolve configuration
  resolve: {
    alias: {
      '@': resolve(__dirname, './src-svelte'),
    },
  },
});
