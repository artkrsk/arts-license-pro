<?php

namespace Arts\LicensePro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manager
 *
 * Main class providing license management functionality.
 */
class Manager {

	/**
	 * API instance
	 *
	 * @var API
	 */
	private API $api;

	/**
	 * Storage instance
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor
	 *
	 * @param array $config Configuration array
	 */
	public function __construct( array $config ) {
		$this->config = wp_parse_args(
			$config,
			array(
				'product_slug' => '',
				'product_type' => 'plugin',
				'api_base_url' => '',
			)
		);

		/** Validate required config */
		if ( empty( $this->config['product_slug'] ) || empty( $this->config['api_base_url'] ) ) {
			wp_die( 'License Manager requires product_slug and api_base_url configuration.' );
		}

		/** Initialize storage and API */
		$this->storage = new Storage( $this->config['product_slug'] );
		$this->api     = new API( $this->config, $this->storage );
	}

	/**
	 * Check if license is valid
	 *
	 * @return bool True if license is valid
	 */
	public function is_valid(): bool {
		$data = $this->storage->get_data();

		if ( ! $data ) {
			return false;
		}

		/** Check status field */
		if ( ! isset( $data['status'] ) || $data['status'] !== 'valid' ) {
			return false;
		}

		return true;
	}

	/**
	 * Get license status data
	 *
	 * @return array|null License data or null if no license
	 */
	public function get_status(): ?array {
		return $this->storage->get_data();
	}

	/**
	 * Get API instance
	 *
	 * @return API
	 */
	public function get_api(): API {
		return $this->api;
	}

	/**
	 * Get storage instance
	 *
	 * @return Storage
	 */
	public function get_storage(): Storage {
		return $this->storage;
	}
}

