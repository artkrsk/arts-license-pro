import type { ILicenseFormProps } from '../interfaces'
import { ProBadge } from './ProBadge'

const { useState, useEffect } = wp.element
const { Button, TextControl } = wp.components
const { dateI18n } = wp.date
const { __ } = wp.i18n

/** License activation form component */
export function LicenseForm({
  onActivate,
  onDeactivate,
  onRefresh,
  isLoading,
  licenseKey: initialKey,
  isActivated,
  license,
  apiError,
  licenseConfig,
  shouldRefreshPageOnLicenseChange = false,
  shouldAutoRefreshLicenseOnMount = false
}: ILicenseFormProps): JSX.Element {
  const [licenseKey, setLicenseKey] = useState(initialKey || '')
  const [validationError, setValidationError] = useState<string | null>(null)
  const [showingError, setShowingError] = useState(false)
  const [isRefreshing, setIsRefreshing] = useState(false)

  useEffect(() => {
    if (shouldAutoRefreshLicenseOnMount && isActivated) {
      handleRefreshClick()
    }
  }, [])

  const validateKey = (key: string): string | null => {
    if (!key || key.trim().length === 0) {
      return __('License key is required', 'arts-license-pro')
    }

    return null
  }

  const handleRefreshClick = async () => {
    setIsRefreshing(true)
    try {
      await onRefresh()
    } catch (err) {
      setShowingError(true)
    } finally {
      setIsRefreshing(false)
    }
  }

  const handleActivateClick = async () => {
    const error = validateKey(licenseKey)
    if (error) {
      setValidationError(error)
      return
    }

    setValidationError(null)

    try {
      await onActivate(licenseKey)
      if (shouldRefreshPageOnLicenseChange) {
        window.location.reload()
      }
    } catch (err) {
      /** Error handled by parent */
    }
  }

  const handleDeactivateClick = async () => {
    if (!confirm(__('Are you sure you want to deactivate this license?', 'arts-license-pro'))) {
      return
    }

    try {
      await onDeactivate()
      setLicenseKey('')
      if (shouldRefreshPageOnLicenseChange) {
        window.location.reload()
      }
    } catch (err) {
      /** Error handled by parent */
    }
  }

  const renderError = (message: string) => (
    <span className="arts-license-pro-error">
      <span className="arts-license-pro-error__icon">✕</span>
      {message}
    </span>
  )

  let helpContent: React.ReactNode = null

  if (validationError) {
    helpContent = renderError(validationError)
  } else if (showingError && apiError) {
    helpContent = renderError(apiError)
  } else if (isActivated && license) {
    const listItems: React.ReactNode[] = []

    /** Use API is_local flag to determine environment label */
    const statusText = license.is_local
      ? __('Activated (staging domain)', 'arts-license-pro')
      : __('Activated', 'arts-license-pro')

    /** Clickable status badge */
    listItems.push(
      <li key="status">
        <div
          className={`arts-license-pro-status-badge arts-license-pro-status-badge_valid ${isRefreshing ? 'arts-license-pro-status-badge_refreshing' : 'arts-license-pro-status-badge_clickable'}`}
          onClick={isRefreshing ? undefined : handleRefreshClick}
          title={isRefreshing ? '' : __('Click to refresh', 'arts-license-pro')}
        >
          <span className="arts-license-pro-status-badge__icon">✓</span>
          <span className="arts-license-pro-status-badge__text">{statusText}</span>
        </div>
      </li>
    )

    /** Purchase date */
    if (license.date_purchased) {
      const dateString = license.date_purchased.split(' ')[0] || license.date_purchased
      const formattedPurchaseDate = dateI18n('M d, Y', dateString)
      listItems.push(
        <li key="purchased">
          ✓ {__('Purchased on', 'arts-license-pro')} {formattedPurchaseDate}
        </li>
      )
    }

    /** Support - show as list item with inline badge */
    if (license.date_supported_until) {
      const dateString = license.date_supported_until.split(' ')[0] || license.date_supported_until
      const supportDate = new Date(dateString)
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const isExpired = supportDate < today

      const formattedDate = dateI18n('M d, Y', dateString)
      const supportStatus = isExpired ? 'warning' : 'success'
      const supportUrl = isExpired ? licenseConfig.renewSupportUrl : licenseConfig.supportUrl
      const supportText = isExpired
        ? __('Renew Support', 'arts-license-pro')
        : __('Get Support', 'arts-license-pro')

      if (isExpired) {
        listItems.push(
          <li key="support" className="arts-license-pro-error">
            <span className="arts-license-pro-error__icon">✕</span>
            {__('Support Expired Since', 'arts-license-pro')} {formattedDate}{' '}
            <ProBadge
              showWrapper={false}
              renderAsLink={true}
              href={supportUrl}
              text={supportText}
              status={supportStatus}
            />
          </li>
        )
      } else {
        listItems.push(
          <li key="support">
            ✓ {__('Supported Until', 'arts-license-pro')} {formattedDate}{' '}
            <ProBadge
              showWrapper={false}
              renderAsLink={true}
              href={supportUrl}
              text={supportText}
              status={supportStatus}
            />
          </li>
        )
      }
    }

    /** Updates - show lifetime or expiration date */
    if (license.date_updates_provided_until) {
      if (license.date_updates_provided_until === 'lifetime') {
        listItems.push(<li key="updates">✓ {__('Lifetime Updates', 'arts-license-pro')}</li>)
      } else {
        const dateString =
          license.date_updates_provided_until.split(' ')[0] || license.date_updates_provided_until
        const updatesDate = new Date(dateString)
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        const isExpired = updatesDate < today
        const formattedUpdatesDate = dateI18n('M d, Y', dateString)

        if (isExpired) {
          listItems.push(
            <li key="updates" className="arts-license-pro-error">
              <span className="arts-license-pro-error__icon">✕</span>
              {__('Updates Expired on', 'arts-license-pro')} {formattedUpdatesDate}
            </li>
          )
        } else {
          listItems.push(
            <li key="updates">
              ✓ {__('Updates Provided Till', 'arts-license-pro')} {formattedUpdatesDate}
            </li>
          )
        }
      }
    }

    /** Expiration */
    if (license.expires) {
      if (license.expires === 'lifetime') {
        listItems.push(<li key="expires">✓ {__('Lifetime License', 'arts-license-pro')}</li>)
      } else {
        listItems.push(
          <li key="expires">
            ✓ {__('Expires', 'arts-license-pro')}: {license.expires}
          </li>
        )
      }
    }

    /** Activations - show unlimited or current usage */
    if (license.site_count !== undefined && license.license_limit !== undefined) {
      const limit = Number(license.license_limit)
      if (limit === 0 || license.activations_left === 'unlimited') {
        listItems.push(
          <li key="activations">✓ {__('Unlimited Activations', 'arts-license-pro')}</li>
        )
      } else {
        listItems.push(
          <li key="activations">
            ✓ {__('Activations', 'arts-license-pro')}: {license.site_count}/{license.license_limit}
          </li>
        )
      }
    }

    /** Render license info list */
    helpContent = (
      <div className="arts-license-pro-info">
        <ul className="arts-license-pro-info__list">{listItems}</ul>
      </div>
    )
  } else {
    helpContent = (
      <span>
        {__('Enter Your License Key', 'arts-license-pro')}.{' '}
        <ProBadge
          showWrapper={false}
          renderAsLink={true}
          href={licenseConfig.purchaseUrl}
          text={__('Get License Key', 'arts-license-pro')}
        />
      </span>
    )
  }

  return (
    <div className="arts-license-pro-form">
      <TextControl
        value={licenseKey}
        onChange={(value: string) => {
          setLicenseKey(value)
          setValidationError(null)
          setShowingError(false)
        }}
        onKeyDown={(e: React.KeyboardEvent) => {
          if (e.key === 'Enter' && !isActivated && licenseKey.trim()) {
            e.preventDefault()
            handleActivateClick()
          }
        }}
        placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX"
        help={helpContent}
        className={validationError || (showingError && apiError) ? 'has-error' : ''}
        disabled={isLoading || isRefreshing || isActivated}
        readOnly={isActivated}
      />
      <Button
        variant={isActivated ? 'secondary' : 'primary'}
        type="button"
        onClick={isActivated ? handleDeactivateClick : handleActivateClick}
        isBusy={isLoading || isRefreshing}
        disabled={isLoading || isRefreshing || (!isActivated && !licenseKey.trim())}
        isDestructive={isActivated && !isRefreshing}
        className="arts-license-pro-form__button"
      >
        {isRefreshing
          ? __('Refreshing...', 'arts-license-pro')
          : isLoading
            ? isActivated
              ? __('Deactivating...', 'arts-license-pro')
              : __('Activating...', 'arts-license-pro')
            : isActivated
              ? __('Deactivate', 'arts-license-pro')
              : __('Activate', 'arts-license-pro')}
      </Button>
    </div>
  )
}
