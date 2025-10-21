<?php

namespace Arts\LicensePro;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Arts\LicensePro\Includes\Manager;
use Arts\LicensePro\Includes\PluginUpdates;
use Arts\LicensePro\Includes\ThemeUpdates;
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
	 * @var Manager|null
	 */
	private ?Manager $manager = null;

	/**
	 * Updates manager instance
	 *
	 * @var PluginUpdates|ThemeUpdates|null
	 */
	private $updates = null;

	/**
	 * Frontend manager instance
	 *
	 * @var Frontend|null
	 */
	private ?Frontend $frontend = null;

	/**
	 * AJAX manager instance
	 *
	 * @var AJAX|null
	 */
	private ?AJAX $ajax = null;

	/**
	 * Configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Initialization error if any
	 *
	 * @var \WP_Error|null
	 */
	private $init_error = null;

	/**
	 * Constructor
	 *
	 * @param array $config Configuration array with keys:
	 *                      - product_slug: Product identifier (required)
	 *                      - product_type: 'plugin' or 'theme' (default: 'plugin')
	 *                      - api_base_url: Base URL for REST API (default: 'https://artemsemkin.com/wp-json')
	 *                      - purchase_url: URL to purchase license (optional)
	 *                      - support_url: URL for support (optional)
	 *                      - renew_support_url: URL to renew support (optional)
	 *                      - plugin_file: Main plugin file path (optional, for plugin updates)
	 *                      - update_uri: Update URI for plugin header (optional, for plugin updates)
	 *                      - theme_slug: Theme directory slug (optional, for theme updates)
	 */
	public function __construct( array $config ) {
		$this->config = wp_parse_args(
			$config,
			array(
				'product_slug'      => '',
				'product_type'      => 'plugin',
				'api_base_url'      => 'https://artemsemkin.com/wp-json',
				'purchase_url'      => '',
				'support_url'       => '',
				'renew_support_url' => '',
				'plugin_file'       => '',
				'update_uri'        => '',
				'theme_slug'        => '',
				'icons'             => array(),
				'banners'           => array(),
			)
		);

		/** Validate required config */
		if ( empty( $this->config['product_slug'] ) ) {
			$this->init_error = new \WP_Error(
				'invalid_config',
				__( 'Arts License Pro requires product_slug configuration.', 'arts-license-pro' )
			);
			return;
		}

		/** Initialize license manager */
		$this->manager = new Manager( $this->config );

		/** Check for manager initialization errors */
		if ( $this->manager->get_errors() ) {
			$this->init_error = $this->manager->get_errors();
			return;
		}

		/** Initialize updates manager if plugin_file or theme_slug provided */
		if ( ! empty( $this->config['plugin_file'] ) ) {
			$this->updates = new PluginUpdates( $this->config, $this->manager );
			$this->updates->init();
		} elseif ( ! empty( $this->config['theme_slug'] ) ) {
			$this->updates = new ThemeUpdates( $this->config, $this->manager );
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
		if ( $this->init_error || ! $this->manager ) {
			return false;
		}

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
		$classes = array( 'arts-license-pro-license-panel-root' );

		// Add custom classes if provided
		if ( ! empty( $args['class'] ) ) {
			$custom_classes = is_array( $args['class'] ) ? $args['class'] : explode( ' ', $args['class'] );
			$classes        = array_merge( $classes, $custom_classes );
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
		$classes              = array( 'arts-license-pro-badge-root' );

		// Add custom classes if provided
		if ( ! empty( $args['class'] ) ) {
			$custom_classes = is_array( $args['class'] ) ? $args['class'] : explode( ' ', $args['class'] );
			$classes        = array_merge( $classes, $custom_classes );
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
	 * @throws \RuntimeException If plugin not properly initialized
	 */
	public function get_manager(): Manager {
		if ( ! $this->manager ) {
			throw new \RuntimeException( 'Plugin not properly initialized. Check get_errors() for details.' );
		}
		return $this->manager;
	}

	/**
	 * Get initialization errors if any
	 *
	 * @return \WP_Error|null
	 */
	public function get_errors() {
		return $this->init_error;
	}
}
