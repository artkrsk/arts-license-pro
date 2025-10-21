<?php

namespace Arts\LicensePro\Includes;

use Arts\LicensePro\Includes\Interfaces\APIInterface;
use Arts\LicensePro\Includes\Interfaces\StorageInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * API
 *
 * Handles communication with the REST API for license operations.
 */
class API implements APIInterface {

	/**
	 * API configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Storage instance
	 *
	 * @var StorageInterface
	 */
	private StorageInterface $storage;

	/**
	 * Constructor
	 *
	 * @param array            $config  API configuration
	 * @param StorageInterface $storage Storage instance
	 */
	public function __construct( array $config, StorageInterface $storage ) {
		$this->config  = $config;
		$this->storage = $storage;
	}

	/**
	 * Activate license
	 */
	public function activate( string $license_key ) {
		$result = $this->call_api( 'activate', $license_key );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/** Map API response to our format */
		$result = $this->map_api_response( $result, $license_key );

		/** Only store if license is valid */
		if ( isset( $result['status'] ) && $result['status'] === 'valid' ) {
			$this->storage->set_key( $license_key );
			$this->storage->set_data( $result );
		}

		return $result;
	}

	/**
	 * Deactivate license
	 */
	public function deactivate() {
		$license_key = $this->storage->get_key();

		if ( ! $license_key ) {
			return new \WP_Error(
				'no_license_key',
				__( 'No license key found', 'arts-license-pro' )
			);
		}

		/** Try to deactivate via API (log on failure but don't stop) */
		$result = $this->call_api( 'deactivate', $license_key );
		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'License deactivation API failed: %s', $result->get_error_message() ) );
		}

		/** Always clear local data (even if API failed) */
		$this->storage->delete_data();

		return array();
	}

	/**
	 * Check license status
	 */
	public function check() {
		/** Get stored license key */
		$license_key = $this->storage->get_key();
		if ( ! $license_key ) {
			return null;
		}

		$result = $this->call_api( 'check', $license_key );

		if ( is_wp_error( $result ) ) {
			/** Return stored data as fallback if API call fails */
			$data = $this->storage->get_data();
			if ( $data ) {
				return $data;
			}

			return $result;
		}

		/** Map API response to our format */
		$result = $this->map_api_response( $result, $license_key );

		/** Update stored data */
		$this->storage->set_data( $result );

		return $result;
	}

	/**
	 * Fetch update information from update server
	 *
	 * @return object|\WP_Error Update data or WP_Error on failure
	 */
	public function fetch_update_info() {
		$license_key = $this->storage->get_key();

		$url = sprintf(
			'%s/update/%s/%s?key=%s&url=%s',
			rtrim( $this->config['api_base_url'], '/' ),
			$this->config['product_slug'],
			$this->config['product_type'],
			urlencode( $license_key ?? '' ),
			urlencode( home_url() )
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! is_object( $data ) ) {
			return new \WP_Error( 'invalid_response', __( 'Invalid update server response', 'arts-license-pro' ) );
		}

		return $data;
	}

	/**
	 * Map API response to internal format
	 *
	 * @param array  $response    API response
	 * @param string $license_key License key
	 * @return array Mapped response
	 */
	private function map_api_response( array $response, string $license_key ): array {
		$mapped = $response;

		/** Map 'license' field to 'status' (EDD API uses 'license', we use 'status') */
		if ( isset( $response['license'] ) ) {
			$mapped['status'] = $response['license'];
			unset( $mapped['license'] );
		} elseif ( ! isset( $response['status'] ) ) {
			$mapped['status'] = 'valid';
		}

		/** Ensure license_key is in the response */
		$mapped['license_key'] = $license_key;

		return $mapped;
	}

	/**
	 * Call REST API
	 *
	 * @param string $action      API action (activate|deactivate|check)
	 * @param string $license_key License key
	 * @return array|\WP_Error API response or WP_Error on failure
	 */
	private function call_api( string $action, string $license_key ) {
		/** Construct REST API URL */
		$url = sprintf(
			'%s/%s/%s/%s?key=%s&url=%s',
			rtrim( $this->config['api_base_url'], '/' ),
			$action,
			$this->config['product_slug'],
			$this->config['product_type'],
			urlencode( $license_key ),
			urlencode( home_url() )
		);

		/** Make API request */
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		/** Handle errors */
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'license_server_error',
				$response->get_error_message()
			);
		}

		/** Parse response */
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error(
				'invalid_response',
				__( 'Invalid API response', 'arts-license-pro' )
			);
		}

		/** Check for API-level errors */
		if ( isset( $data['success'] ) && ! $data['success'] ) {
			$message = $data['message'] ?? __( 'License operation failed', 'arts-license-pro' );
			return new \WP_Error(
				'license_validation_error',
				$message
			);
		}

		return $data;
	}
}
