<?php

namespace Arts\LicensePro\Includes;

use Arts\LicensePro\Includes\Exceptions\LicenseValidationException;
use Arts\LicensePro\Includes\Exceptions\LicenseServerException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * API
 *
 * Handles communication with the REST API for license operations.
 */
class API {

	/**
	 * API configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Storage instance
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Constructor
	 *
	 * @param array   $config  API configuration
	 * @param Storage $storage Storage instance
	 */
	public function __construct( array $config, Storage $storage ) {
		$this->config  = $config;
		$this->storage = $storage;
	}

	/**
	 * Activate license
	 *
	 * @param string $license_key License key to activate
	 * @return array API response with license data
	 * @throws \Exception If activation fails
	 */
	public function activate( string $license_key ): array {
		$result = $this->call_api( 'activate', $license_key );

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
	 *
	 * @return array Empty array on success
	 * @throws \Exception If no license key found
	 */
	public function deactivate(): array {
		$license_key = $this->storage->get_key();

		if ( ! $license_key ) {
			throw new LicenseValidationException( __( 'No license key found', 'arts-license-pro' ) );
		}

		/** Try to deactivate via API (don't throw on failure) */
		try {
			$this->call_api( 'deactivate', $license_key );
		} catch ( \Exception $e ) {
			/** API deactivation failed, but still clear local data */
		}

		/** Always clear local data (even if API failed) */
		$this->storage->delete_data();

		return array();
	}

	/**
	 * Check license status
	 *
	 * @return array|null License data or null if no license
	 */
	public function check(): ?array {
		/** Get stored license key */
		$license_key = $this->storage->get_key();
		if ( ! $license_key ) {
			return null;
		}

		try {
			$result = $this->call_api( 'check', $license_key );

			/** Map API response to our format */
			$result = $this->map_api_response( $result, $license_key );

			/** Update stored data */
			$this->storage->set_data( $result );

			return $result;
		} catch ( \Exception $e ) {
			/** Return stored data as fallback if API call fails */
			$data = $this->storage->get_data();
			if ( $data ) {
				return $data;
			}

			throw $e;
		}
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
	 * @return array API response
	 * @throws \Exception If API call fails
	 */
	private function call_api( string $action, string $license_key ): array {
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
			throw new LicenseServerException( $response->get_error_message() );
		}

		/** Parse response */
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			throw new LicenseServerException( __( 'Invalid API response', 'arts-license-pro' ) );
		}

		/** Check for API-level errors */
		if ( isset( $data['success'] ) && ! $data['success'] ) {
			$message = $data['message'] ?? __( 'License activation failed', 'arts-license-pro' );
			throw new LicenseValidationException( $message );
		}

		return $data;
	}
}
