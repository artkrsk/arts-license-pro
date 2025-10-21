import type { ILicenseLicenseData, ILicenseConfig } from './index'

/**
 * License instance data structure passed from PHP to JavaScript
 * Used by auto-mount and stored in window.artsLicenseProInstances
 */
export interface ILicenseInstanceData {
	/** Product slug identifier */
	productSlug: string
	/** WordPress AJAX URL */
	ajaxUrl: string
	/** WordPress nonce for security */
	nonce: string
	/** Initial license data from storage */
	initialLicense: ILicenseLicenseData | null
	/** License panel configuration */
	config: ILicenseConfig
}

