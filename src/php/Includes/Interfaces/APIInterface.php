<?php

namespace Arts\LicensePro\Includes\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * APIInterface
 *
 * Interface for license API implementations.
 */
interface APIInterface {

	/**
	 * Activate license
	 *
	 * @param string $license_key License key to activate
	 * @return array|\WP_Error API response with license data or WP_Error on failure
	 */
	public function activate( string $license_key );

	/**
	 * Deactivate license
	 *
	 * @return array|\WP_Error Empty array on success or WP_Error on failure
	 */
	public function deactivate();

	/**
	 * Check license status
	 *
	 * @return array|\WP_Error|null License data, WP_Error on failure, or null if no license
	 */
	public function check();

	/**
	 * Fetch update information from update server
	 *
	 * @return object|\WP_Error Update data or WP_Error on failure
	 */
	public function fetch_update_info();
}
