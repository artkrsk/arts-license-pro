import type { ILicensePanelProps } from '../interfaces'
import { LicenseForm } from './LicenseForm'
import { useLicense } from '../hooks/useLicense'

/** License panel container component */
export function LicensePanel({
	initialLicense,
	licenseConfig,
	licenseAPI,
	shouldRefreshPageOnLicenseChange = true,
	shouldAutoRefreshLicenseOnMount = false,
}: ILicensePanelProps): JSX.Element {
	const { license, isLoading, error, activateLicense, deactivateLicense, refreshLicense } = useLicense({
		initialLicense,
		api: licenseAPI,
	})

	const isActivated = license && license.status === 'valid'
	const licenseKey = license?.license_key || null

	return (
		<div className="arts-license-pro-panel">
			<LicenseForm
				onActivate={activateLicense}
				onDeactivate={deactivateLicense}
				onRefresh={refreshLicense}
				isLoading={isLoading}
				licenseKey={licenseKey}
				isActivated={!!isActivated}
				license={license}
				apiError={error}
				licenseConfig={licenseConfig}
				shouldRefreshPageOnLicenseChange={shouldRefreshPageOnLicenseChange}
				shouldAutoRefreshLicenseOnMount={shouldAutoRefreshLicenseOnMount}
			/>
		</div>
	)
}

