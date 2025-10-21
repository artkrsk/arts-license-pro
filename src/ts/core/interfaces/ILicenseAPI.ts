import type { ILicenseLicenseData } from './ILicenseLicenseData'

/** License API abstraction interface */
export interface ILicenseAPI {
	/** Activate license with given key */
	activate: (key: string) => Promise<ILicenseLicenseData>
	/** Deactivate current license */
	deactivate: () => Promise<void>
	/** Refresh license status */
	check: () => Promise<ILicenseLicenseData | null>
}
