import { createElement } from '@wordpress/element'
import { render } from '@wordpress/element'
import { LicensePanel } from './core/components'
import { ProBadge } from './core/components'
import { LicenseAPIAdapter } from './core/api/LicenseAPIAdapter'

/** Auto-mount license panels and pro badges on page load */
function autoMountComponents(): void {
	// Mount license panels
	const licensePanels = document.querySelectorAll('[id$="-license-panel"]')
	licensePanels.forEach((element) => {
		const productSlug = element.id.replace('-license-panel', '')
		const instanceData = window.artsLicenseProInstances[productSlug]
		
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
				licenseAPI: api,
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
