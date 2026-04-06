import { fileURLToPath } from 'node:url'
import path from 'node:path'
import { defineConfig } from 'vite'
import pkg from './package.json';

export default defineConfig(() => {
  const runtimeTier = process.env.XPRESSUI_RUNTIME_TIER === 'light' ? 'light' : 'standard';
  const entryName = runtimeTier === 'light' ? 'xpressui-light' : 'xpressui';
  const entryPath = runtimeTier === 'light' ? './src/index-light.ts' : './src/index.ts';

  return {
    define: {
      __XPRESSUI_VERSION__: JSON.stringify(pkg.version),
    },
    resolve: {
      alias: runtimeTier === 'light'
        ? {
            './ui/form-ui.document-qr-runtime': path.resolve(
              fileURLToPath(new URL('.', import.meta.url)),
              'src/ui/form-ui.document-qr-runtime.light.ts',
            ),
            './ui/form-ui.quiz-runtime': path.resolve(
              fileURLToPath(new URL('.', import.meta.url)),
              'src/ui/form-ui.quiz-runtime.light.ts',
            ),
            './ui/form-ui.commerce-runtime': path.resolve(
              fileURLToPath(new URL('.', import.meta.url)),
              'src/ui/form-ui.commerce-runtime.light.ts',
            ),
            './ui/form-ui.overlay-runtime': path.resolve(
              fileURLToPath(new URL('.', import.meta.url)),
              'src/ui/form-ui.overlay-runtime.light.ts',
            ),
          }
        : undefined,
    },
    build: {
      copyPublicDir: false,
      emptyOutDir: false,
      sourcemap: true,
      lib: {
        entry: {
          [entryName]: fileURLToPath(new URL(entryPath, import.meta.url)),
        },
        formats: ['es', 'umd'],
        name: 'xpressui',
        fileName: (format, currentEntryName) => format === 'es'
          ? `${currentEntryName}-${pkg.version}.mjs`
          : `${currentEntryName}-${pkg.version}.umd.js`,
      },
    },
  };
})
