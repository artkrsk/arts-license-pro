import { autoMountComponents } from './core/auto-mount'

/** Initialize on DOM ready */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoMountComponents)
} else {
  autoMountComponents()
}

/** Export components for programmatic use */
export { LicensePanel, LicenseForm, ProBadge } from './core/components'
export { LicenseAPIAdapter } from './core/api/LicenseAPIAdapter'
export { useLicense } from './core/hooks/useLicense'
export * from './core/interfaces'
export * from './core/types'
