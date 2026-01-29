<?php

namespace Arts\LicensePro\Includes\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ManagerInterface
 *
 * Interface for license manager implementations.
 */
interface ManagerInterface {

	/**
	 * Check if license is valid
	 *
	 * @return bool True if license is valid
	 */
	public function is_valid(): bool;

	/**
	 * Get license status data
	 *
	 * @return array|null License data or null if no license
	 */
	public function get_status(): ?array;

	/**
	 * Get API instance
	 *
	 * @return APIInterface
	 */
	public function get_api(): APIInterface;

	/**
	 * Get storage instance
	 *
	 * @return StorageInterface
	 */
	public function get_storage(): StorageInterface;

	/**
	 * Get initialization errors if any
	 *
	 * @return \WP_Error|null
	 */
	public function get_errors();
}
