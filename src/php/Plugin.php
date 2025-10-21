<?php

namespace Arts\LicensePro;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Arts\LicensePro\Includes\Manager;
use Arts\LicensePro\Includes\Updates;
use Arts\LicensePro\Includes\Frontend;
use Arts\LicensePro\Includes\AJAX;

/**
 * Plugin
 *
 * Main entry point for Arts License Pro library.
 * Provides license management and plugin update functionality.
 */
class Plugin {

	/**
	 * License manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Updates manager instance
	 *
	 * @var Updates|null
	 */
	private ?Updates $updates = null;

	/**
	 * Frontend manager instance
	 *
	 * @var Frontend
	 */
	private Frontend $frontend;

	/**
	 * AJAX manager instance
	 *
	 * @var AJAX
	 */
	private AJAX $ajax;

	/**
	 * Configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor
	 *
	 * @param array $config Configuration array with keys:
	 *                      - product_slug: Product identifier (required)
	 *                      - product_type: 'plugin' or 'theme' (default: 'plugin')
	 *                      - api_base_url: Base URL for REST API (required)
	 *                      - purchase_url: URL to purchase license (optional)
	 *                      - support_url: URL for support (optional)
	 *                      - renew_support_url: URL to renew support (optional)
	 *                      - plugin_file: Main plugin file path (optional, for updates)
	 *                      - update_uri: Update URI for plugin header (optional)
	 */
	public function __construct( array $config ) {
		$this->config = wp_parse_args(
			$config,
			array(
				'product_slug'      => '',
				'product_type'      => 'plugin',
				'api_base_url'      => '',
				'purchase_url'      => '',
				'support_url'       => '',
				'renew_support_url' => '',
				'plugin_file'       => '',
				'update_uri'        => '',
				'icons'             => array(),
				'banners'           => array(),
			)
		);

		/** Validate required config */
		if ( empty( $this->config['product_slug'] ) || empty( $this->config['api_base_url'] ) ) {
			wp_die( 'Arts License Pro requires product_slug and api_base_url configuration.' );
		}

		/** Initialize license manager */
		$this->manager = new Manager( $this->config );

		/** Initialize updates manager if plugin_file provided */
		if ( ! empty( $this->config['plugin_file'] ) ) {
			$this->updates = new Updates( $this->config, $this->manager );
			$this->updates->init();
		}

		/** Initialize frontend manager */
		$this->frontend = new Frontend( $this->config, $this->manager );
		$this->frontend->init();

		/** Initialize AJAX manager */
		$this->ajax = new AJAX( $this->config, $this->manager );
		$this->ajax->init();
	}

	/**
	 * Check if license is active
	 *
	 * @return bool True if license is valid
	 */
	public function is_license_active(): bool {
		return $this->manager->is_valid();
	}

	/**
	 * Render license panel
	 *
	 * @param array $args   Panel configuration
	 * @param bool  $return Whether to return HTML instead of outputting
	 * @return string|void HTML if $return is true, otherwise outputs directly
	 */
	public function render_license_panel( array $args = array(), bool $return = false ) {
		$root_id = $this->config['product_slug'] . '-license-panel';
		$classes = array( 'arts-license-pro-license-panel-mount' );

		// Add custom classes if provided
		if ( ! empty( $args['class'] ) ) {
			$custom_classes = is_array( $args['class'] ) ? $args['class'] : explode( ' ', $args['class'] );
			$classes = array_merge( $classes, $custom_classes );
		}

		$html = sprintf(
			'<div id="%s" class="%s" data-product="%s"></div>',
			esc_attr( $root_id ),
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $this->config['product_slug'] )
		);

		if ( $return ) {
			return $html;
		}

		echo $html;
	}

	/**
	 * Render pro feature badge
	 *
	 * @param array $args   Badge configuration
	 * @param bool  $return Whether to return HTML instead of outputting
	 * @return string|void HTML if $return is true, otherwise outputs directly
	 */
	public function render_pro_badge( array $args = array(), bool $return = false ) {
		static $badge_counter = 0;
		$badge_id             = $this->config['product_slug'] . '-pro-badge-' . ( ++$badge_counter );
		$classes = array( 'arts-license-pro-badge-mount' );

		// Add custom classes if provided
		if ( ! empty( $args['class'] ) ) {
			$custom_classes = is_array( $args['class'] ) ? $args['class'] : explode( ' ', $args['class'] );
			$classes = array_merge( $classes, $custom_classes );
		}

		$html = sprintf(
			'<span id="%s" class="%s" data-product="%s" data-config="%s"></span>',
			esc_attr( $badge_id ),
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $this->config['product_slug'] ),
			esc_attr( wp_json_encode( $args ) )
		);

		if ( $return ) {
			return $html;
		}

		echo $html;
	}

	/**
	 * Get license manager instance
	 *
	 * @return Manager
	 */
	public function get_manager(): Manager {
		return $this->manager;
	}
}