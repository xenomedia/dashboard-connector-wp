<?php
/**
 * Custom REST API end point for Dashboard Connector WP.
 *
 * @package  Dashboard_Connector_WP
 *
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


function create_Dashboard_Connector_WP_REST_Controller() {
	new Dashboard_Connector_WP_REST_Controller();
}

add_action( 'init', 'create_Dashboard_Connector_WP_REST_Controller' );


/**
 * Class for handling Links in the REST API.
 *
 * @since 1.0.0
 */
class Dashboard_Connector_WP_REST_Controller extends WP_REST_Controller {

	/**
	 * Holds settings.
	 */
	protected $settings;

	/**
	 * Holds the namespace for these routes.
	 *
	 * @var string
	 * @access  private
	 * @since   1.0.0
	 */
	protected $namespace = 'wp/v2';

	/**
	 * Initialize rest api.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function __construct() {
		$this->init_settings();
		$this->init_hooks();
	}

	/**
	 * Initialize settings.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function init_settings() {
		$this->settings = array(
			'client_id' => xdb_get_settings( $setting = 'r_client_id', $defined = 'XDB_CLIENT_ID' ),
			'site_id'   => xdb_get_settings( $setting = 'r_site_id', $defined = 'XDB_SITE_ID' ),
			'url'       => xdb_get_settings( $setting = 'r_url', $defined = 'XDB_URL' ),
			'env'       => xdb_get_settings( $setting = 'r_env', $defined = 'XDB_ENV' ),
			'username'  => xdb_get_settings( $setting = 'r_user', $defined = 'XDB_USER' ),
			'pwd'       => xdb_get_settings( $setting = 'r_pwd', $defined = 'XDB_PWD' ),
		);
	}

	/**
	 * Initialize hooks.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'xdb_rest_notify_dashboard', array( $this, 'rest_notify_dashboard' ) );


		// Cron to post in Dashboard Connector WP.
		if ( ! wp_next_scheduled( 'xdb_rest_notify_dashboard' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'xdb_rest_notify_dashboard' );
		}


	}

	/**
	 * Post Dashboard Connector WP site information.
	 * Only for prod environment.
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function rest_notify_dashboard() {

		$env = xdb_get_settings( $setting = 'r_env', $defined = 'XDB_ENV' );

		// Verify if production
		if ( 'prod' != $env ) {
			return false;
		}

		$this->post_to_xeno();

	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function register_routes() {

		// Register the updates check endpoint.
		register_rest_route( $this->namespace, '/site-info', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_site_info' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );

		// Register the updates check endpoint.
		register_rest_route( $this->namespace, '/slack-talk', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_slack_talk' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request request data
	 *
	 * @return WP_Error or WP_REST_Response
	 *
	 * @access  public
	 */
	public function get_slack_talk( $request ) {

		$list_types = array(
			'core',
			'plugins',
			'themes',
		);

		// Gets $_GET parameter.
		// t can be core, plugins or themes, if not then all.
		$type = $request->get_param( 't' );
		$type = in_array( $type, $list_types ) ? $type : null;

		// If all doestn exists will return only the updates.
		$all = $request->get_param( 'all' );
		$all = ! isset( $all ) ? true : false;

		// Class Dashboard_Connector_WP_Slack.
		require_once plugin_dir_path( __FILE__ ) . 'slack.php';
		$Dashboard_Connector_WP_Slack = new Dashboard_Connector_WP_Slack();
		$Dashboard_Connector_WP_Slack->talk( $type, $all );

		$response = array(
			'response' => __( 'I am slack and I talk', 'xdb' ),
		);

		$response = rest_ensure_response( $response );
		$response->header( 'X-WP-Total', count( $response ) );

		return $response;
	}

	/**
	 * Get a collection of items.
	 *
	 * @param void
	 *
	 * @return array $data site information.
	 *
	 * @access  private
	 */
	private function prepare_data() {
		// Data arrays holds all the theme, plugin and core information.
		$data = array();

		// Class Dashboard_Connector_WP_Updates.
		require_once plugin_dir_path( __FILE__ ) . 'updates.php';
		require_once plugin_dir_path( __FILE__ ) . 'phpChecker.php';

		$Dashboard_Connector_WP_Updates    = new Dashboard_Connector_WP_Updates();
		$Dashboard_Connector_WP_PHPChecker = new PHPChecker();

		// Core.
		$Dashboard_Connector_WP_Updates->prepare_core_response( $data );

		// Plugins
		$Dashboard_Connector_WP_Updates->prepare_plugins_response( $data );

		// Themes.
		$Dashboard_Connector_WP_Updates->prepare_themes_response( $data );

		// PHP.
		$data = array_merge( $data, $Dashboard_Connector_WP_PHPChecker->getChecks() );

		$response = array(
			'timestamp' => $Dashboard_Connector_WP_Updates->prepare_date_response( current_time( 'mysql', 1 ) ),
			'client_id' => $this->settings['client_id'],
			'site_id'   => $this->settings['site_id'],
			'env'       => $this->settings['env'],
			'checks'    => $data,
		);

		return $response;

	}

	/**
	 * Json reponse of site information.
	 *
	 * @param WP_REST_Request $request request data
	 *
	 * @return WP_Error or WP_REST_Response
	 *
	 * @access  public
	 */
	public function get_site_info( $request ) {

		$response = $this->prepare_data();

		$response = rest_ensure_response( $response );
		$response->header( 'X-WP-Total', count( $response ) );

		return $response;
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request request data
	 *
	 * @return WP_Error or bool
	 *
	 * @access  public
	 */
	public function permissions_check( $request ) {

		// Gets $_GET parameter.
		$recieved_jwt = $request->get_param( 'token' );

		if ( empty( $recieved_jwt ) ) {

			// Empty token.
			return new WP_Error(
				'forbidden_context',
				__( 'Invalid token.', 'xdb' ),
				array( 'status' => 403 )
			);
		}

		if ( function_exists( 'xdb_check_supertoken' ) ) {

			// In case a plus symbol is received in the url.
			$recieved_jwt = urlencode( $recieved_jwt );
			$recieved_jwt = str_replace( '+', '%2B', $recieved_jwt );
			$recieved_jwt = urldecode( $recieved_jwt );

			if ( ! xdb_check_supertoken( $recieved_jwt ) ) {

				// Invalid token.
				return new WP_Error(
					'forbidden_context',
					__( 'Invalid token.', 'xdb' ),
					array( 'status' => 403 )
				);
			}

		} else {

			// In case there is not a way to check for the super token.
			return new WP_Error(
				'forbidden_context',
				__( 'Something went terribly wrong.', 'xdb' ),
				array( 'status' => 503 )
			);
		}

		return true;
	}

	/**
	 * cURL function to talk to Dashboard Connector WP in Drupal
	 * TODO conver to wp.
	 *
	 * @param $url string - jira rest api.
	 * @param $data json - string with fields.
	 *
	 * @return $result json - string or boolean.
	 * @access   public
	 */
	public function post_to_xeno() {
		$data = $this->prepare_data();

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_URL, $this->settings['url'] );
		curl_setopt( $ch, CURLOPT_USERPWD, $this->settings['username'] . ":" . $this->settings['pwd'] );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); // for old versions. TODO: verify ssl

		$result   = curl_exec( $ch );
		$ch_error = curl_error( $ch );
		if ( $ch_error ) {
			//echo sprintf( 'cURL Error: %s', $ch_error );
			return false;
		}
		curl_close( $ch );
		//echo "\n" . $result;
	}

}

/**
 * Run cron when plugin is activated crons.
 *
 * @param void
 *
 * @return void
 */
function xdb_run_dashboard_on_activate() {
	do_action( 'xdb_rest_notify_dashboard' );
}

register_activation_hook( __FILE__, 'xdb_run_dashboard_on_activate' );


/**
 * De-register crons.
 *
 * @param void
 *
 * @return void
 */
function xdb_run_on_deactivate() {
	wp_clear_scheduled_hook( 'xdb_rest_notify_dashboard' );
}

register_deactivation_hook( __FILE__, 'xdb_run_on_deactivate' );
