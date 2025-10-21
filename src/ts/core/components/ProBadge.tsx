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
  status = 'default'
}: IProBadgeProps) => {
  const badgeText = text || __('Get Pro', 'arts-license-pro')

  /** Determine CSS class based on status */
  const statusClass = status !== 'default' ? ` arts-license-pro-badge_${status}` : ''
  const badgeClassName = `arts-license-pro-badge${statusClass}`

  const badge = renderAsLink ? (
    <a href={href} target="_blank" rel="noopener noreferrer" className={badgeClassName}>
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
