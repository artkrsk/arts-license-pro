<?php

namespace Arts\LicensePro\Includes;

use Arts\LicensePro\Includes\Interfaces\StorageInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Storage
 *
 * Handles persistence of license data using wp_options.
 * Stores all license data in a single serialized option for performance.
 */
class Storage implements StorageInterface {

	/**
	 * Option key prefix
	 *
	 * @var string
	 */
	private string $option_prefix;

	/**
	 * Constructor
	 *
	 * @param string $product_id Product identifier
	 */
	public function __construct( string $product_id ) {
		$this->option_prefix = $product_id . '_license';
	}

	/**
	 * Get stored license key
	 *
	 * @return string|null
	 */
	public function get_key(): ?string {
		$key = get_option( $this->option_prefix . '_key' );
		return $key ? (string) $key : null;
	}

	/**
	 * Store license key
	 *
	 * @param string $key License key
	 * @return void
	 */
	public function set_key( string $key ): void {
		update_option( $this->option_prefix . '_key', $key );
	}

	/**
	 * Delete stored license key
	 *
	 * @return void
	 */
	public function delete_key(): void {
		delete_option( $this->option_prefix . '_key' );
	}

	/**
	 * Get all license data as array
	 *
	 * @return array|null
	 */
	public function get_data(): ?array {
		$key = $this->get_key();
		if ( ! $key ) {
			return null;
		}

		$data = get_option( $this->option_prefix . '_data' );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return null;
		}

		/** Ensure license_key is always included */
		$data['license_key'] = $key;

		return $data;
	}

	/**
	 * Store license data from API response
	 *
	 * @param array $data License data from API
	 * @return void
	 */
	public function set_data( array $data ): void {
		/** Remove license_key from data since it's stored separately */
		unset( $data['license_key'] );

		update_option( $this->option_prefix . '_data', $data );
	}

	/**
	 * Delete all license data
	 *
	 * @return void
	 */
	public function delete_data(): void {
		$this->delete_key();
		delete_option( $this->option_prefix . '_data' );
	}
}

