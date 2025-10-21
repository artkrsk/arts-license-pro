/** Pro feature teaser badge props */
export interface IProFeatureTeaserProps {
	/** Feature name to display */
	featureName?: string
	/** Dashicon class */
	icon?: string
	/** Show wrapper container */
	showWrapper?: boolean
	/** Render as link or span */
	renderAsLink?: boolean
	/** Link href */
	href?: string
	/** Link text */
	linkText?: string
	/** Badge status for styling */
	status?: 'default' | 'success' | 'warning'
}

