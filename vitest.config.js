import { defineConfig } from 'vitest/config'
import { dirname, resolve } from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

export default defineConfig({
  test: {
    environment: 'jsdom',

    // Setup files (equivalent to setupFilesAfterEnv in Jest)
    setupFiles: ['__tests__/ts/setup.ts'],

    // Module resolution
    resolve: {
      alias: {
        '@interfaces': resolve(__dirname, 'src/ts/core/interfaces'),
        '@types': resolve(__dirname, 'src/ts/core/types'),
        '@constants': resolve(__dirname, 'src/ts/core/constants')
      }
    },

    // Coverage configuration
    coverage: {
      include: ['src/ts/**/*.ts'],
      exclude: [
        'src/ts/**/*.cy.ts',
        'src/ts/**/interfaces/*.ts',
        'src/ts/**/types/*.ts',
        'src/ts/**/constants/*.ts',
        'src/ts/www/**/*',
        'node_modules/',
        '**/*.d.ts'
      ]
    }
  }
})
