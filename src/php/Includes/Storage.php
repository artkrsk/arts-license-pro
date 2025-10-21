<?php

namespace Arts\LicensePro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Storage
 *
 * Handles persistence of license data using wp_options.
 * Each field is stored as a separate option with product_slug prefix.
 */
class Storage {

	/**
	 * Option key prefix
	 *
	 * @var string
	 */
	private string $option_prefix;

	/**
	 * License data fields to store
	 *
	 * @var array
	 */
	private const LICENSE_FIELDS = array(
		'status',
		'expires',
		'site_count',
		'license_limit',
		'activations_left',
		'is_support_provided',
		'date_purchased',
		'date_supported_until',
		'date_updates_provided_until',
		'is_local',
	);

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

		$data = array( 'license_key' => $key );

		foreach ( self::LICENSE_FIELDS as $field ) {
			$value = $this->get_field( $field );
			if ( $value !== null ) {
				$data[ $field ] = $value;
			}
		}

		return ! empty( $data ) ? $data : null;
	}

	/**
	 * Store license data from API response
	 *
	 * @param array $data License data from API
	 * @return void
	 */
	public function set_data( array $data ): void {
		foreach ( self::LICENSE_FIELDS as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$this->set_field( $field, $data[ $field ] );
			}
		}
	}

	/**
	 * Delete all license data
	 *
	 * @return void
	 */
	public function delete_data(): void {
		$this->delete_key();
		foreach ( self::LICENSE_FIELDS as $field ) {
			$this->delete_field( $field );
		}
	}

	/**
	 * Get individual license field
	 *
	 * @param string $field Field name
	 * @return mixed|null
	 */
	private function get_field( string $field ) {
		$value = get_option( $this->option_prefix . '_' . $field );
		return $value !== false ? $value : null;
	}

	/**
	 * Set individual license field
	 *
	 * @param string $field Field name
	 * @param mixed  $value Field value
	 * @return void
	 */
	private function set_field( string $field, $value ): void {
		update_option( $this->option_prefix . '_' . $field, $value );
	}

	/**
	 * Delete individual license field
	 *
	 * @param string $field Field name
	 * @return void
	 */
	private function delete_field( string $field ): void {
		delete_option( $this->option_prefix . '_' . $field );
	}
}

