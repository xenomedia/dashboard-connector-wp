<?php
/**
 * Admin settings for plugin dashboard-connector-wp
 *
 * @package  dashboard-connector-wp
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
  die;
}

/**
 * Class to check for updates.
 *
 * @since 1.0.0
 */
class Dashboard_Connector_WP_Updates {

	/**
	 * Constructor()
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access   public
	 */
	public function __construct() {

		// Force an updates check.
		set_site_transient( 'update_plugins', null );
	}

	/**
	 * Gets WP core information.
	 *
	 * @param WP_REST_Request $request Full data about the site.
	 *
	 * @return void
	 *
	 * @access   public
	 */
	public function prepare_core_response( &$data, $only_available = false ) {

		// WordPress update check.
		if ( ! function_exists( 'get_core_updates' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/update.php' );
		}

		// Some plugins maybe hidding core version, so let's check it in version.php.
		require_once( ABSPATH . WPINC . '/version.php' );

		global $wp_version; // Gets current version of wp core.

		// See if there's an update before moving forward.
		$the_core = get_core_updates( array( 'dismissed' => false ) );

		// Removes first element.
		$the_core = array_shift( $the_core );

		// Get core version.
		$the_core_version = ! empty( $the_core->version ) ? $the_core->version : false;

		// Default info.
		$default = array(
			'type' 	=> __( 'Core', 'xdb' ),
			'name' 	=> __( 'WP', 'xdb' ),
		);

		if ( $wp_version !=  $the_core_version ) {
			// If core needs update.
			$core_info = array(
				'alert_level' => ( $this->check_with_wpvulndb( 'core', null, $wp_version ) ) ? __('alert', 'xdb') : __('warning', 'xdb'),
				'description' => sprintf( __( 'Not current ( %1$s => %2$s )', 'xdb' ), $wp_version, $the_core_version),
			);

		} else {
			// Core is up to date.
			$core_info = array(
				'alert_level' => 'notice',
				'description' => sprintf( __( 'Up to date %1$s', 'xdb' ), $wp_version ),
			);
		}

		// Important: This is the return, $data is edited.
		$data[] = array_merge( $default, $core_info );

	}

	/**
	 * Gets Plugins information.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return void
	 *
	 * @access   public
	 */
	public function prepare_plugins_response( &$data, $only_available = false ) {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		// Force check for plugins updates.
		do_action( 'wp_update_plugins' );

		// Checks if there are updates.
		$the_plugins = get_site_transient( 'update_plugins' );

		// Gets only data we need.
		$updates_a = array();
		if ( isset( $the_plugins->response) && is_array( $the_plugins->response ) ) {
			foreach ( $the_plugins->response as $p=>$h ) {
				$updates_a[$h->slug] = array(
					'ver' => $h->new_version,
					'warning' => ( isset( $h->upgrade_notice ) ) ? $h->upgrade_notice : '',
				);
			}
		}

		foreach ($all_plugins as $plugin => $plugin_response_data ) {
			// Get plugin data.
			$plugin_data = get_plugin_data( WP_CONTENT_DIR . '/plugins/' . $plugin );

			// Set plugin title.
			$the_plugin_title = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : false;

			// Set plugin version.
			$the_plugin_version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : false;

			$default = array(
				'type' 		=> 'Plugin',
				'name' 		=> $the_plugin_title,
				'description' => sprintf( __( 'Up to date %1$s', 'xdb' ), $the_plugin_version ),
				'alert_level' => __( 'notice', 'xdb' ),
			);

			$plugin_info = array();
			if ( array_key_exists( $plugin_response_data['TextDomain'], $updates_a ) ) {

				// Gets next version to update.
				$the_update_version = $updates_a[$plugin_response_data['TextDomain']]['ver'];

				// Gets the description to verify if there is any notice of any vulnerability.
				$notice = $updates_a[$plugin_response_data['TextDomain']]['warning'];

				// Checks for vulnerabilities in description.
				$vulnerable_in_description = $this->check_in_description( $notice );

				// Checks for vulnerabilties in wpvulndb database.
				$vulnerable_in_wpvulndb = $this->check_with_wpvulndb( 'plugin', $plugin_response_data['TextDomain'], $the_update_version );

				$plugin_info = array(
					'alert_level' => ( $vulnerable_in_description || $vulnerable_in_wpvulndb ) ? __( 'alert', 'xdb' ) : __( 'warning', 'xdb' ),
					'description' => sprintf( __( 'Not current (%1$s => %2$s)', 'xdb' ), $the_plugin_version, $the_update_version ),
				);
			}

			// Editing $data that is passed as reference.
			array_push( $data, array_merge( $default, $plugin_info ) );
		}
	}

	/**
	 * Gets Themes information.
	 *
	 * @param WP_REST_Request $request request data.
	 *
	 * @return void
	 *
	 * @access   public
	 */
	public function prepare_themes_response( &$data, $only_available = false, $active_only = false ) {

		// Forces WP to check for theme updates.
		do_action( 'wp_update_themes' );

		// Gets information of updates.
		$update_themes = get_site_transient( 'update_themes' );

		$all_themes = wp_get_themes();

		// Gets only data we need.
		$updates_a = array();

		if ( isset( $update_themes->response) && is_array( $update_themes->response ) ) {
			foreach ($update_themes->response as $p=>$h) {
				$theme_data = wp_get_theme( $p );
				$the_theme_name = $theme_data['Name'];
				$updates_a[ $the_theme_name ] = array(
						'ver' => $h['new_version'],
						'slug' => $p,
					);
				}
		}

		foreach ($all_themes as $theme  ) {
			$the_theme_version = $theme->get( 'Version' );
			$the_theme_name = $theme->get('Name');
			$default = array(
				'type' 		=> 'Theme',
				'name' 		=> $the_theme_name,
				'description' => sprintf( __( 'Up to date %1$s', 'xdb' ), $the_theme_version ),
				'alert_level' => __( 'notice', 'xdb' ),
			);

			// Build final array.
			$theme_info = array();
			if ( array_key_exists( $the_theme_name, $updates_a ) ) {

				// Gets next version to update.
				$the_update_version = $updates_a[$the_theme_name]['ver'];

				// Checks for vulnerabilties in wpvulndb database.
				$vulnerable_in_wpvulndb = $this->check_with_wpvulndb( 'theme', $updates_a[$the_theme_name]['slug'], $updates_a[$the_theme_name]['ver'] );

				$theme_info = array(
					'alert_level' => ( $vulnerable_in_wpvulndb ) ? __( 'alert', 'xdb' ) : __( 'warning', 'xdb' ),
					'description' => sprintf( __( 'Not current (%1$s => %2$s)', 'xdb' ), $the_theme_version, $the_update_version ),
				);
			}

			// Editing $data that is passed as reference.
			array_push( $data, array_merge( $default, $theme_info ) );
		}
	}

	/**
	 * Gets formated data
	 *
	 * @param string $date date
	 *
	 * @return date $date ISO8601/RFC3339 formatted datetime.
	 *
	 * @access   public
	 */
	public function prepare_date_response( $date ) {
		if ( '0000-00-00 00:00:00' === $date ) {
		  return null;
		}

		return mysql_to_rfc3339( $date );
	}

	/**
	 * Check if there is any vulnerability described in the plugin notes
	 *
	 * @param string $description the plugin description.
	 *
	 * @return bool $found true if any vulnerabilities found in description, false otherwise.
	 *
	 * @access   private
	 */
	private function check_in_description( $description ) {
		$array = array(
			'Vulnerability',
			'SQL Injection',
			'Cross-Site',
			'CSRF',
			'XSS',
			'Unvalidated Redirects',
			'Inject',
			'Insecure',
			'Unvalidated Input',
			'Malicious',
			'Risk',
			'Hack',
			'Hijacking',
		);

		$string = strtolower( $description );
		$found = false;

		// Loops in array of words to see if they exists in the plugin update description.
		foreach ( $array as $words => $word ) {
			if ( false !== strpos( $string, strtolower( $word ) ) ) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	/**
	 * Check for possible vulnerabilities in wpvulndb db
	 * see wpvulndb.org
	 *
	 * @param string $type theme|plugin|core
	 *
	 * @param string $slug theme|plugin slug
	 *
	 * @param string $ver theme|plugin version
	 *
	 * @return bool
	 *
	 * @access   private
	 */
	private function check_with_wpvulndb( $type, $slug = null, $ver) {

		// Will hold any errors.
		$xdb_errors = new WP_Error();
		// wpvulndb API urls.
		$wpvulndb_url = array(
			'core'    => 'https://wpvulndb.com/api/v2/wordpresses/',
			'plugin' => 'https://wpvulndb.com/api/v2/plugins/',
			'theme' => 'https://wpvulndb.com/api/v2/themes/',
		);

		if ( 'plugin' == $type || 'theme' == $type ) {
			// Plugin and themes do not have the version in the url.
			$url = $wpvulndb_url[$type] . $slug;
		} else {
			// Get data in case wp is not up to date.
			$url = $wpvulndb_url[$type] . filter_var( $ver, FILTER_SANITIZE_NUMBER_INT );
		}

		// Remote get.
		$response = wp_remote_get(
			$url,
			array( 'sslverify'=>false )
		); // ssl verify false for old versions.

		$vulnerable = false;

		// Handle errors.
		if ( is_wp_error( $response ) ) {

			// Set an error.
			$xdb_errors->add( 'xdb_slack_api_error', $response->get_error_message() );

		} elseif ( ! empty( $response['response'] )
			&& ! empty( $response['response']['code'] )
			&& '200' != $response['response']['code'] ) {

			// Set an error.
			$xdb_errors->add( 'xdb_slack_api_error',  __( 'Error: Couldn\'t connect to wpvulndb', 'xdb' ) );

			// If the response is an array, it's coming from wp_remote_get.
			if ( is_array( $response ) && isset( $response['body'] ) ) {

				$json = json_decode( $response['body'] );
				if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
					return $vulnerable;
				}

				/**
				 * Loops in API response to check for vulnerabilities reported.
				 * Compares the current version of the item (plugin, theme or core)
				 * with all the versions reported as vulnerables
				 * return true if the current version is not greater than all the
				 * versions reported as fixed.
				 **/
				$cur_ver = filter_var( $ver, FILTER_SANITIZE_NUMBER_INT );
				foreach ( $json as $vul => $key ) {
					$count = count( $key->vulnerabilities );
					$i = 1;
					foreach ( $key->vulnerabilities as $v ) {
						$fixed_in = filter_var( $v->fixed_in, FILTER_SANITIZE_NUMBER_INT );

						if ( $fixed_in <= $cur_ver ) {
							$i++;
						}
					}

					$vulnerable = ( $i < $count );

					if ( true == $vulnerable ) break; // if vulnerable then breaks;
				}
			} // Good reponse.

			return $vulnerable;
		} // Handle errors ends.
	}
}
