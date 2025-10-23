import type { IProBadgeProps } from '../interfaces'

const { __ } = wp.i18n

/** Pro feature badge component */
export const ProBadge = ({
  label,
  icon,
  showWrapper = true,
  renderAsLink = true,
  href,
  text,
  status = 'default',
  openInNewWindow = true
}: IProBadgeProps): JSX.Element => {
  const badgeText = text || __('Get Pro', 'arts-license-pro')

  /** Determine CSS class based on status */
  const statusClass = status !== 'default' ? ` arts-license-pro-badge_${status}` : ''
  const badgeClassName = `arts-license-pro-badge${statusClass}`

  // Only render as link if renderAsLink is true AND href is provided
  const shouldRenderAsLink = renderAsLink && href
  
  const badge = shouldRenderAsLink ? (
    <a
      href={href}
      className={badgeClassName}
      {...(openInNewWindow && { target: '_blank', rel: 'noopener noreferrer' })}
    >
      {badgeText}
    </a>
  ) : (
    <span className={badgeClassName}>{badgeText}</span>
  )

  if (!showWrapper) {
    return badge
  }

  return (
    <span className="arts-license-pro-badge-wrapper">
      {icon && <span className={`dashicons ${icon}`}></span>}
      {label && <span className="arts-license-pro-badge-wrapper__label">{label}</span>}
      {badge}
    </span>
  )
}
