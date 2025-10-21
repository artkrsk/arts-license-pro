<?php

namespace Arts\LicensePro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Updates
 *
 * Manages plugin update checking and provides download URLs.
 */
class Updates {

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
	 * Plugin slug (derived from plugin_file)
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Plugin ID (plugin directory/file.php)
	 *
	 * @var string
	 */
	private string $plugin_id;

	/**
	 * Constructor
	 *
	 * @param array   $config  Configuration
	 * @param Manager $manager Manager instance
	 */
	public function __construct( array $config, Manager $manager ) {
		$this->config  = $config;
		$this->manager = $manager;

		/** Derive plugin slug and ID from plugin_file */
		if ( ! empty( $config['plugin_file'] ) ) {
			$plugin_file       = trailingslashit( WP_PLUGIN_DIR ) . plugin_basename( $config['plugin_file'] );
			$this->plugin_id   = plugin_basename( $config['plugin_file'] );
			$this->plugin_slug = dirname( $this->plugin_id );
		}
	}

	/**
	 * Initialize update hooks
	 *
	 * @return void
	 */
	public function init(): void {
		/** Only init if plugin_file and update_uri are provided */
		if ( empty( $this->config['plugin_file'] ) || empty( $this->config['update_uri'] ) ) {
			return;
		}

		/** Hook into WordPress plugin update system */
		add_filter( 'plugins_api', array( $this, 'set_remote_data' ), 99, 3 );

		/** Check for updates using the Update URI */
		add_filter( 'update_plugins_' . $this->config['update_uri'], array( $this, 'check_update' ), 99, 4 );

		/** Clear cache when license status changes */
		add_action( 'update_option_' . $this->config['product_slug'] . '_license_status', array( $this, 'clear_update_cache' ) );
		add_action( "in_plugin_update_message-{$this->plugin_id}", array( $this, 'modify_update_message' ), 10, 2 );
	}

	/**
	 * Check for plugin updates
	 *
	 * @param object $update      The update object
	 * @param array  $plugin_data The plugin data array
	 * @param string $plugin_file The plugin file path
	 * @param array  $locales     The locales array
	 * @return object The modified update object
	 */
	public function check_update( $update, $plugin_data, $plugin_file, $locales ) {
		/** Only process for our plugin */
		if ( $plugin_file !== $this->plugin_id || ! empty( $update ) ) {
			return $update;
		}

		$remote_plugin_data = $this->fetch_remote_data();

		/** Remote data is not available */
		if ( is_wp_error( $remote_plugin_data ) ) {
			return $update;
		}

		$remote_version = isset( $remote_plugin_data->version ) ? $remote_plugin_data->version : '';

		/** No update needed since the remote version is the same or lower than the local version */
		if ( ! version_compare( $plugin_data['Version'], $remote_version, '<' ) ) {
			return $update;
		}

		/** Modify the update object with the new version and download URL */
		$remote_plugin_data->new_version = $remote_plugin_data->version;
		$remote_plugin_data->package     = $remote_plugin_data->download_url ?? '';

		/** Add icons if configured */
		if ( ! empty( $this->config['icons'] ) && is_array( $this->config['icons'] ) ) {
			$remote_plugin_data->icons = $this->config['icons'];
		}

		/** Add banners if configured */
		if ( ! empty( $this->config['banners'] ) && is_array( $this->config['banners'] ) ) {
			$remote_plugin_data->banners = $this->config['banners'];
		}

		return $remote_plugin_data;
	}

	/**
	 * Set remote plugin data for the plugin information popup
	 *
	 * @param mixed  $res    The current plugin information object
	 * @param string $action The action being performed
	 * @param object $args   The arguments passed to the action
	 * @return mixed The modified plugin information object
	 */
	public function set_remote_data( $res, $action, $args ) {
		/** The action should be 'plugin_information' */
		if ( $action !== 'plugin_information' ) {
			return $res;
		}

		/** The plugin slug should match the current plugin slug */
		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $res;
		}

		$remote_plugin_data = $this->fetch_remote_data();

		/** Remote data is not available */
		if ( is_wp_error( $remote_plugin_data ) ) {
			return $res;
		}

		/** Modify the plugin information object with the remote data */
		$res                 = new \stdClass();
		$res->name           = $remote_plugin_data->name;
		$res->slug           = $remote_plugin_data->slug;
		$res->version        = $remote_plugin_data->version;
		$res->tested         = $remote_plugin_data->tested;
		$res->requires       = $remote_plugin_data->requires;
		$res->author         = $remote_plugin_data->author ?? '';
		$res->author_profile = $remote_plugin_data->author_profile ?? '';
		$res->donate_link    = $remote_plugin_data->donate_link ?? '';
		$res->homepage       = $remote_plugin_data->homepage ?? '';
		$res->download_link  = $remote_plugin_data->download_url ?? '';
		$res->trunk          = $remote_plugin_data->download_url ?? '';
		$res->requires_php   = $remote_plugin_data->requires_php ?? '7.4';
		$res->last_updated   = $remote_plugin_data->last_updated ?? '';
		$res->sections       = $remote_plugin_data->sections ?? array();
		$res->rating         = $remote_plugin_data->rating ?? 0;
		$res->num_ratings    = $remote_plugin_data->num_ratings ?? 0;

		/** Add banners if configured */
		if ( ! empty( $this->config['banners'] ) && is_array( $this->config['banners'] ) ) {
			$res->banners = $this->config['banners'];
		}

		return $res;
	}

	/**
	 * Fetch remote plugin data from update server
	 *
	 * @return object|\WP_Error Remote plugin data or WP_Error on failure
	 */
	private function fetch_remote_data() {
		/** Get stored license key */
		$license_key = $this->manager->get_storage()->get_key();

		/** Construct REST API URL */
		$url = sprintf(
			'%s/update/%s/%s?key=%s&url=%s',
			rtrim( $this->config['api_base_url'], '/' ),
			$this->config['product_slug'],
			$this->config['product_type'],
			urlencode( $license_key ?? '' ),
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
			return $response;
		}

		/** Parse response */
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! is_object( $data ) ) {
			return new \WP_Error( 'invalid_response', __( 'Invalid update server response', 'arts-license-pro' ) );
		}

		return $data;
	}

	/**
	 * Clear the plugin update cache
	 *
	 * @return void
	 */
	public function clear_update_cache(): void {
		wp_clean_plugins_cache( true );
	}

	/**
	 * Modify the update notification row message when package URL is missing.
	 *
	 * Displays a link to purchase the plugin when updates are unavailable due to
	 * invalid or missing license.
	 *
	 * @param array $plugin_data Plugin row update data.
	 * @param array $response    Response array from WordPress.
	 * @return void
	 */
	public function modify_update_message( $plugin_data, $response ): void {
		$no_package      = ! isset( $plugin_data['package'] ) || empty( $plugin_data['package'] );
		$new_version     = $plugin_data['new_version'] ?? '';
		$current_version = $plugin_data['Version'] ?? '';

		if ( ! $no_package ) {
			return;
		}

		// Only show when an update exists.
		if ( version_compare( $current_version, $new_version, '>=' ) ) {
			return;
		}

		$purchase_url = $this->config['purchase_url'] ?? '';

		if ( empty( $purchase_url ) ) {
			return;
		}

		$message = sprintf(
			/* translators: 1: opening <a> tag 2: closing </a> tag */
			__( 'Automatic update is unavailable. %1$sPurchase a license%2$s to enable updates.', 'arts-license-pro' ),
			'<a href="' . esc_url( $purchase_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);

		echo '<br>' . wp_kses_post( $message );
	}
}

