<?php

namespace Arts\LicensePro\Includes;

use Arts\LicensePro\Includes\Interfaces\ManagerInterface;
use Arts\LicensePro\Includes\Interfaces\APIInterface;
use Arts\LicensePro\Includes\Interfaces\StorageInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manager
 *
 * Main class providing license management functionality.
 */
class Manager implements ManagerInterface {

	/**
	 * API instance
	 *
	 * @var APIInterface|null
	 */
	private ?APIInterface $api = null;

	/**
	 * Storage instance
	 *
	 * @var StorageInterface|null
	 */
	private ?StorageInterface $storage = null;

	/**
	 * Configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Initialization error if any
	 *
	 * @var \WP_Error|null
	 */
	private $init_error = null;

	/**
	 * Constructor
	 *
	 * @param array                 $config  Configuration array
	 * @param StorageInterface|null $storage Storage implementation (optional)
	 * @param APIInterface|null     $api     API implementation (optional)
	 */
	public function __construct( array $config, ?StorageInterface $storage = null, ?APIInterface $api = null ) {
		$this->config = wp_parse_args(
			$config,
			array(
				'product_slug' => '',
				'product_type' => 'plugin',
				'api_base_url' => 'https://artemsemkin.com/wp-json',
			)
		);

		/** Validate required config */
		if ( empty( $this->config['product_slug'] ) ) {
			$this->init_error = new \WP_Error(
				'invalid_config',
				__( 'License Manager requires product_slug configuration.', 'arts-license-pro' )
			);
			return;
		}

		/** Initialize storage and API (use injected or create new) */
		$this->storage = $storage ?? new Storage( $this->config['product_slug'] );
		$this->api     = $api ?? new API( $this->config, $this->storage );
	}

	/**
	 * Check if license is valid
	 */
	public function is_valid(): bool {
		if ( $this->init_error || ! $this->storage ) {
			return false;
		}

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
	 */
	public function get_status(): ?array {
		if ( $this->init_error || ! $this->storage ) {
			return null;
		}

		return $this->storage->get_data();
	}

	/**
	 * Get API instance
	 */
	public function get_api(): APIInterface {
		if ( ! $this->api ) {
			throw new \RuntimeException( 'Manager not properly initialized. Check get_errors() for details.' );
		}
		return $this->api;
	}

	/**
	 * Get storage instance
	 */
	public function get_storage(): StorageInterface {
		if ( ! $this->storage ) {
			throw new \RuntimeException( 'Manager not properly initialized. Check get_errors() for details.' );
		}
		return $this->storage;
	}

	/**
	 * Get initialization errors if any
	 */
	public function get_errors() {
		return $this->init_error;
	}
}
