import type { TLicenseInstances } from './core/types'

declare global {
  interface Window {
    wp: {
      element: typeof import('@wordpress/element')
      components: typeof import('@wordpress/components')
      i18n: typeof import('@wordpress/i18n')
      date: typeof import('@wordpress/date')
      apiFetch: typeof import('@wordpress/api-fetch').default
    }
    artsLicenseProInstances: TLicenseInstances
  }

  const wp: Window['wp']
}

export {}
