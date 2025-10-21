import type { ILicenseAPI, ILicenseLicenseData } from '../interfaces'

/** WordPress AJAX response format */
interface IAjaxResponse {
  success: boolean
  data?: any
}

/** AJAX adapter implementing ILicenseAPI */
export class LicenseAPIAdapter implements ILicenseAPI {
  constructor(
    private productSlug: string,
    private ajaxUrl: string,
    private nonce: string
  ) {}

  /** Activate license with given key */
  async activate(key: string): Promise<ILicenseLicenseData> {
    const formData = new URLSearchParams({
      action: `${this.productSlug}_license_activate`,
      _wpnonce: this.nonce,
      license_key: key
    })

    const response = await fetch(this.ajaxUrl, {
      method: 'POST',
      body: formData
    })

    const result = (await response.json()) as IAjaxResponse

    if (!result.success) {
      throw new Error(result.data?.message || 'Activation failed')
    }

    return result.data as ILicenseLicenseData
  }

  /** Deactivate current license */
  async deactivate(): Promise<void> {
    const formData = new URLSearchParams({
      action: `${this.productSlug}_license_deactivate`,
      _wpnonce: this.nonce
    })

    const response = await fetch(this.ajaxUrl, {
      method: 'POST',
      body: formData
    })

    const result = (await response.json()) as IAjaxResponse

    if (!result.success) {
      throw new Error(result.data?.message || 'Deactivation failed')
    }
  }

  /** Refresh license status */
  async check(): Promise<ILicenseLicenseData | null> {
    const formData = new URLSearchParams({
      action: `${this.productSlug}_license_check`,
      _wpnonce: this.nonce
    })

    const response = await fetch(this.ajaxUrl, {
      method: 'POST',
      body: formData
    })

    const result = (await response.json()) as IAjaxResponse

    if (!result.success) {
      if (result.data?.message === 'No license found') {
        return null
      }
      throw new Error(result.data?.message || 'Check failed')
    }

    return result.data as ILicenseLicenseData
  }
}
