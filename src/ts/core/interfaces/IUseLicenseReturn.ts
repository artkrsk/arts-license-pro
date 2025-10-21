import type { ILicenseLicenseData } from './ILicenseLicenseData'

/** useLicense hook return value */
export interface IUseLicenseReturn {
	license: ILicenseLicenseData | null
	isLoading: boolean
	error: string | null
	activateLicense: (key: string) => Promise<void>
	deactivateLicense: () => Promise<void>
	refreshLicense: () => Promise<void>
}

