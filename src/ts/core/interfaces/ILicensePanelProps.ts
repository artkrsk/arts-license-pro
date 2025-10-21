import type { ILicenseAPI } from './ILicenseAPI'
import type { ILicenseConfig } from './ILicenseConfig'
import type { ILicenseLicenseData } from './ILicenseLicenseData'

/** License panel component props */
export interface ILicensePanelProps {
	initialLicense: ILicenseLicenseData | null
	licenseConfig: ILicenseConfig
	licenseAPI: ILicenseAPI
	shouldRefreshPageOnLicenseChange?: boolean
	shouldAutoRefreshLicenseOnMount?: boolean
}

