<?php

namespace Arts\LicensePro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Frontend
 *
 * Handles frontend asset loading and localization.
 */
class Frontend {

	/**
	 * Configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Manager instance
	 *
	 * @var Manager
	 */
	private Manager $manager;

	/**
	 * Assets base path
	 *
	 * @var string
	 */
	private string $assets_path;

	/**
	 * Assets base URL
	 *
	 * @var string
	 */
	private string $assets_url;

	/**
	 * Constructor
	 *
	 * @param array   $config  Configuration
	 * @param Manager $manager Manager instance
	 */
	public function __construct( array $config, Manager $manager ) {
		$this->config  = $config;
		$this->manager = $manager;

		/** Set assets path and URL */
		$this->assets_path = dirname( __DIR__ ) . '/libraries/arts-license-pro/';
		$this->assets_url  = plugins_url( 'libraries/arts-license-pro/', dirname( __DIR__ ) . '/Plugin.php' );
	}

	/**
	 * Initialize frontend hooks
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/** Add this instance's data to the global filter */
		add_filter( 'arts/license-pro/instances_data', array( $this, 'add_instance_data' ) );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		/** Only enqueue once even if multiple instances exist */
		static $enqueued = false;

		if ( $enqueued ) {
			return;
		}

		$enqueued = true;

		/** Enqueue admin script */
		$admin_js = $this->assets_path . 'index.umd.js';
		if ( file_exists( $admin_js ) ) {
			wp_enqueue_script(
				'arts-license-pro-admin',
				$this->assets_url . 'index.umd.js',
				array( 'react', 'react-dom', 'wp-element', 'wp-components', 'wp-i18n', 'wp-date' ),
				filemtime( $admin_js ),
				true
			);

			/** Localize with all registered instances */
			wp_localize_script(
				'arts-license-pro-admin',
				'artsLicenseProInstances',
				apply_filters( 'arts/license-pro/instances_data', array() )
			);
		}

		/** Enqueue styles */
		$admin_css = $this->assets_path . 'index.css';
		if ( file_exists( $admin_css ) ) {
			wp_enqueue_style(
				'arts-license-pro-admin',
				$this->assets_url . 'index.css',
				array( 'wp-components' ),
				filemtime( $admin_css )
			);
		}
	}

	/**
	 * Add this instance's data to the global instances array
	 *
	 * @param array $instances Existing instances data
	 * @return array Modified instances data
	 */
	public function add_instance_data( array $instances ): array {
		$slug = $this->config['product_slug'];

		$instances[ $slug ] = array(
			'productSlug'     => $slug,
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( $slug . '_license_nonce' ),
			'initialLicense'  => $this->manager->get_status(),
			'config'          => array(
				'purchaseUrl'      => $this->config['purchase_url'] ?? '',
				'supportUrl'       => $this->config['support_url'] ?? '',
				'renewSupportUrl'  => $this->config['renew_support_url'] ?? '',
			),
		);

		return $instances;
	}
}