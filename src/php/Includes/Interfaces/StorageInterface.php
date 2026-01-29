<?php

namespace Arts\LicensePro\Includes\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * StorageInterface
 *
 * Interface for license data storage implementations.
 */
interface StorageInterface {

	/**
	 * Get stored license key
	 *
	 * @return string|null
	 */
	public function get_key(): ?string;

	/**
	 * Store license key
	 *
	 * @param string $key License key
	 * @return void
	 */
	public function set_key( string $key ): void;

	/**
	 * Delete stored license key
	 *
	 * @return void
	 */
	public function delete_key(): void;

	/**
	 * Get all license data as array
	 *
	 * @return array|null
	 */
	public function get_data(): ?array;

	/**
	 * Store license data
	 *
	 * @param array $data License data
	 * @return void
	 */
	public function set_data( array $data ): void;

	/**
	 * Delete all license data
	 *
	 * @return void
	 */
	public function delete_data(): void;
}
