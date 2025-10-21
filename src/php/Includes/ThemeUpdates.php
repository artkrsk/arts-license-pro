<?php

namespace Arts\LicensePro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ThemeUpdates
 *
 * Manages theme update checking and provides download URLs.
 */
class ThemeUpdates {

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
	 * Theme slug
	 *
	 * @var string
	 */
	private string $theme_slug;

	/**
	 * Transient key for storing update response data
	 *
	 * @var string
	 */
	private string $response_key;

	/**
	 * Constructor
	 *
	 * @param array   $config  Configuration
	 * @param Manager $manager Manager instance
	 */
	public function __construct( array $config, Manager $manager ) {
		$this->config  = $config;
		$this->manager = $manager;

		if ( ! empty( $config['theme_slug'] ) ) {
			$this->theme_slug   = $config['theme_slug'];
			$this->response_key = $config['theme_slug'] . '-update-response';
		}
	}

	/**
	 * Initialize update hooks
	 *
	 * @return void
	 */
	public function init(): void {
		if ( empty( $this->config['theme_slug'] ) ) {
			return;
		}

		/** Hook into WordPress theme update system */
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'modify_theme_update_transient' ) );
		add_filter( 'delete_site_transient_update_themes', array( $this, 'delete_theme_update_transient' ) );

		/** Clear cache when license data or key changes */
		add_action( 'update_option_' . $this->config['product_slug'] . '_license_data', array( $this, 'clear_update_cache' ) );
		add_action( 'update_option_' . $this->config['product_slug'] . '_license_key', array( $this, 'clear_update_cache' ) );
		add_action( 'delete_option_' . $this->config['product_slug'] . '_license_data', array( $this, 'clear_update_cache' ) );
		add_action( 'delete_option_' . $this->config['product_slug'] . '_license_key', array( $this, 'clear_update_cache' ) );

		/** Clear cache on update screens and after upgrades */
		add_action( 'load-update-core.php', array( $this, 'delete_theme_update_transient' ) );
		add_action( 'upgrader_process_complete', array( $this, 'clear_update_cache' ), 999 );
	}

	/**
	 * Modify the theme update transient to include custom update data
	 *
	 * @param object $transient The WordPress update transient object
	 * @return object Modified transient with theme update data
	 */
	public function modify_theme_update_transient( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient            = new \stdClass();
			$transient->response  = array();
			$transient->no_update = array();
		}

		$theme_remote_update_data = get_transient( $this->response_key );

		if ( ! $theme_remote_update_data ) {
			$theme_remote_update_data = $this->fetch_remote_data();
			if ( ! is_wp_error( $theme_remote_update_data ) ) {
				set_transient( $this->response_key, $theme_remote_update_data, DAY_IN_SECONDS );
			}
		}

		/** Get current theme version */
		$theme         = wp_get_theme( $this->theme_slug );
		$theme_version = $theme->get( 'Version' );

		if ( $theme_remote_update_data && ! is_wp_error( $theme_remote_update_data ) && isset( $theme_remote_update_data->version ) ) {
			if ( version_compare( $theme_version, $theme_remote_update_data->version, '<' ) ) {
				$transient->response[ $this->theme_slug ] = array(
					'theme'       => $this->theme_slug,
					'new_version' => esc_html( $theme_remote_update_data->version ),
					'package'     => esc_url( $theme_remote_update_data->download_url ?? '' ),
					'url'         => esc_url( $theme_remote_update_data->url ?? '' ),
				);
			} else {
				$item                                      = array(
					'theme'        => $this->theme_slug,
					'new_version'  => $theme_version,
					'url'          => '',
					'package'      => '',
					'requires'     => '',
					'requires_php' => '',
				);
				$transient->no_update[ $this->theme_slug ] = $item;
			}
		}

		return $transient;
	}

	/**
	 * Delete the theme update transient data
	 *
	 * @return void
	 */
	public function delete_theme_update_transient() {
		delete_transient( $this->response_key );
	}

	/**
	 * Fetch remote theme data from update server
	 *
	 * @return object|\WP_Error Remote theme data or WP_Error on failure
	 */
	private function fetch_remote_data() {
		return $this->manager->get_api()->fetch_update_info();
	}

	/**
	 * Clear the theme update cache
	 *
	 * @return void
	 */
	public function clear_update_cache(): void {
		wp_clean_themes_cache( true );
	}
}
