import type { ILicenseInstanceData } from '../interfaces'

/**
 * Collection of license instances indexed by product slug
 * Used in window.artsLicenseProInstances
 */
export type TLicenseInstances = Record<string, ILicenseInstanceData>
