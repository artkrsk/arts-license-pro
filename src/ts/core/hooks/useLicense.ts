import type { ILicenseLicenseData, IUseLicenseConfig, IUseLicenseReturn } from '../interfaces'

const { useState } = wp.element

/** License management hook */
export function useLicense(config: IUseLicenseConfig): IUseLicenseReturn {
	const [license, setLicense] = useState<ILicenseLicenseData | null>(config.initialLicense)
	const [isLoading, setIsLoading] = useState(false)
	const [error, setError] = useState<string | null>(null)

	const activateLicense = async (key: string): Promise<void> => {
		setIsLoading(true)
		setError(null)

		try {
			const result = await config.api.activate(key)
			setLicense(result)
		} catch (err) {
			const message = err instanceof Error ? err.message : 'Activation failed'
			setError(message)
			throw err
		} finally {
			setIsLoading(false)
		}
	}

	const deactivateLicense = async (): Promise<void> => {
		setIsLoading(true)
		setError(null)

		try {
			await config.api.deactivate()
			setLicense(null)
		} catch (err) {
			const message = err instanceof Error ? err.message : 'Deactivation failed'
			setError(message)
			throw err
		} finally {
			setIsLoading(false)
		}
	}

	const refreshLicense = async (): Promise<void> => {
		setIsLoading(true)
		setError(null)

		try {
			const result = await config.api.check()
			setLicense(result)
		} catch (err) {
			const message = err instanceof Error ? err.message : 'Failed to refresh license'
			setError(message)
			throw err
		} finally {
			setIsLoading(false)
		}
	}

	return {
		license,
		isLoading,
		error,
		activateLicense,
		deactivateLicense,
		refreshLicense,
	}
}

