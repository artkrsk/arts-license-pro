import { LicensePanel } from './components'
import { ProBadge } from './components'
import { LicenseAPIAdapter } from './api/LicenseAPIAdapter'

const { createElement, render } = wp.element

/** Auto-mount license panels and pro badges on page load */
export function autoMountComponents(): void {
  // Check if artsLicenseProInstances exists
  if (!window.artsLicenseProInstances) {
    return
  }

  // Mount license panels
  const licensePanels = document.querySelectorAll('[id$="-license-panel"]')
  licensePanels.forEach((element) => {
    const productSlug = element.id.replace('-license-panel', '')
    const instanceData = window.artsLicenseProInstances![productSlug]

    if (!instanceData) {
      console.warn(`No instance data found for product: ${productSlug}`)
      return
    }

    const api = new LicenseAPIAdapter(
      instanceData.productSlug,
      instanceData.ajaxUrl,
      instanceData.nonce
    )

    render(
      createElement(LicensePanel, {
        initialLicense: instanceData.initialLicense,
        licenseConfig: instanceData.config,
        licenseAPI: api
      }),
      element
    )
  })

  // Mount pro badges
  const proBadges = document.querySelectorAll('[id$="-pro-badge-"]')
  proBadges.forEach((element) => {
    const configData = element.getAttribute('data-config')
    if (!configData) return

    try {
      const config = JSON.parse(configData)
      render(createElement(ProBadge, config), element)
    } catch (err) {
      console.error('Failed to parse pro badge config:', err)
    }
  })
}
