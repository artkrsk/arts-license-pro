<?php

namespace Arts\LicensePro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AJAX
 *
 * Handles AJAX requests for license operations.
 */
class AJAX {

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
	 * Constructor
	 *
	 * @param array   $config  Configuration
	 * @param Manager $manager Manager instance
	 */
	public function __construct( array $config, Manager $manager ) {
		$this->config  = $config;
		$this->manager = $manager;
	}

	/**
	 * Initialize AJAX hooks
	 *
	 * @return void
	 */
	public function init(): void {
		$slug = $this->config['product_slug'];

		add_action( "wp_ajax_{$slug}_license_activate", array( $this, 'activate' ) );
		add_action( "wp_ajax_{$slug}_license_deactivate", array( $this, 'deactivate' ) );
		add_action( "wp_ajax_{$slug}_license_check", array( $this, 'check' ) );
	}

	/**
	 * Handle license activation
	 *
	 * @return void
	 */
	public function activate(): void {
		check_ajax_referer( $this->config['product_slug'] . '_license_nonce', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'arts-license-pro' ) ), 403 );
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'License key is required', 'arts-license-pro' ) ), 400 );
		}

		try {
			$result = $this->manager->get_api()->activate( $license_key );

			/** Add license_key to response */
			$result['license_key'] = $license_key;

			wp_send_json_success( $result );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}

	/**
	 * Handle license deactivation
	 *
	 * @return void
	 */
	public function deactivate(): void {
		check_ajax_referer( $this->config['product_slug'] . '_license_nonce', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'arts-license-pro' ) ), 403 );
		}

		try {
			$this->manager->get_api()->deactivate();
			wp_send_json_success( array() );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}

	/**
	 * Handle license check/refresh
	 *
	 * @return void
	 */
	public function check(): void {
		check_ajax_referer( $this->config['product_slug'] . '_license_nonce', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'arts-license-pro' ) ), 403 );
		}

		try {
			$result = $this->manager->get_api()->check();

			if ( $result ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( array( 'message' => __( 'No license found', 'arts-license-pro' ) ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}
}