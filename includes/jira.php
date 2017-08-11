<?php
/**
 * Custom Jira API end point for Xeno Dashboard.
 *
 * @package xeno_dashboard
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

function create_Xeno_Dashboard_Jira() {
	new Xeno_Dashboard_Jira();
}
add_action( 'init', 'create_Xeno_Dashboard_Jira' );

/**
 * Establish Jira communication.
 */
class Xeno_Dashboard_Jira {

	/**
	 * Holds Jira settions.
	 *
	 * @access  private
	 * @since   1.0.0
	 */
	private $settings;

	/**
	 * Holds Jira task description.
	 *
	 * @access  private
	 * @since   1.0.0
	 */
	private $description = '';

	/**
	 * Constructor
	 *
	 * Set class variables.
	 *
	 * @param void
	 * @return void
	 * @access  public
	 */
	public function __construct() {

		// Jira transition ID for Start Progress, default 4.
		$transition = xdb_get_settings( $setting = 'j_trans', $defined = 'XDB_JIRA_TRANSITION' );

		$this->settings = array(
			'enable' => ! empty( $user ),
			'progress_transition_id' => empty( $transition ) ? 4 : $transition,
			'user' => xdb_get_settings( $setting = 'j_user', $defined = 'XDB_JIRA_USER' ),
			'pwd' => xdb_get_settings( $setting = 'j_pwd', $defined = 'XDB_JIRA_PWD' ),
			'assignee' => xdb_get_settings( $setting = 'j_assign', $defined = 'XDB_JIRA_ASSIGNEE' ),
			'server' => xdb_get_settings( $setting = 'j_server', $defined = 'XDB_JIRA_SERVER' ),
			'project' => xdb_get_settings( $setting = 'j_proj', $defined = 'XDB_JIRA_PROJECT' ),
			'labels' => xdb_get_settings( $setting = 'j_labels', $defined = 'XDB_JIRA_LABELS' ),
		);

		add_action( 'xdb_rest_notify_jira', array( $this, 'rest_notify_jira' ) );

		// Cron to create Jira.
		if ( ! wp_next_scheduled( 'xdb_rest_notify_jira' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'xdb_rest_notify_jira' );
		}
	}

	/**
	 * Calls Jira to open ticket in case there are any updates.
	 * Only for prod environment.
	 *
	 * @param void
	 * @return void
	 */
	public function rest_notify_jira() {

		$env = xdb_get_settings( $setting = 'r_env', $defined = 'XDB_ENV' );

		// Verify if production.
		if ( 'prod' != $env ) {
			return;
		}

		$this->open_task();

		sendTestEmail( $msg = 'Jira for ' . $this->settings['site_id'] ); // TODO: REMOVE AFTER PLUGIN IS TESTED AND ACTIVE.
	}

	/**
	 * Open Jira task and start progress.
	 *
	 * @param   void.
	 * @return  boolean  $vulnerable - of the updates has a vulnerability.
	 * @access   private
	 */
	private function get_description() {

		// Class Xeno_Dashboard_Updates.
		require_once plugin_dir_path( __FILE__ ) . 'updates.php';

		$site_data = array();

		$Xeno_Dashboard_Updates = new Xeno_Dashboard_Updates();

		// Core.
		$Xeno_Dashboard_Updates->prepare_core_response( $site_data );

		// Plugins.
		$Xeno_Dashboard_Updates->prepare_plugins_response( $site_data );

		// Themes.
		$Xeno_Dashboard_Updates->prepare_themes_response( $site_data );

		$vulnerable = false;

		$this->description = '';
		foreach ( $site_data as $data => $d ) {

			if ( false !== strpos( strtolower( $d['description'] ), 'up to date' ) ) {
					unset( $d );
			} else {
				// Mix field with defaults.
				$field = array(
					'title' => $d['name'],
					'value' => $d['description'],
					'short' => true,
				);

				$this->description .= $d['type'] . "\tName: " . $d['name'] . ",\t" . $d['description'] . "\n";

				if ( 'warning' == $d['alert_level'] && $vulnerable ) {
					$vulnerable = true;
				}
			}
		}

		return $vulnerable;
	}

	/**
	 * Creates transient to avoid Jira tasks duplicated.
	 *
	 * @param $string string Jira body content
	 *
	 * @return  string clean body content
	 */
	private function clean_transiten( $string ) {
		return crypt(
			json_encode(
				str_replace(
					array( "\r\n", "\n", "\r", "\t", 'plugin', 'theme', 'core' ),
					'',
					$string
				)
			),
			'rl'
		);
	}

	/**
	 * Open Jira task and start progress.
	 *
	 * @param   boolean $vulnerable - If the site is vulnerable then the priority
	 *          will be the highest oneotherwise medium.
	 * @return  string  $response - APi response.
	 * @access   public
	 */
	public function open_task() {

		$vulnerable = $this->get_description();

		// Checks if slack notifications are enabled.
		if ( empty( $this->description ) || empty( $this->settings ) ) {
			return false;
		}
		if ( empty( $this->settings['user'] ) || empty( $this->settings['pwd'] ) || empty( $this->settings['server'] ) ) {
			echo sprintf( "\nJira information incomplete." );
			return false;
		}
		// Checks transition.
		$xdb_updates_transient = get_transient( 'xdb_updates_transient' );
		if ( false !== $xdb_updates_transient ) {

			// If transitien is not refreshed then means it is duplicated and return false.
			if ( $xdb_updates_transient == $this->clean_transiten( $this->description ) ) {
				return false;
			}
		}

		// Verify if there are vulnerabilities.
		$data = array(
			'fields' => array(
				'priority' => array(
					'id' => ( true === $vulnerable ) ? '1' : '3',
				),
				'assignee' => array(
					'name' => ( isset( $this->settings['assignee'] ) ? $this->settings['assignee'] : 'admin' ),
				),
				'project' => array(
					'key' => $this->settings['project'],
				),
				'labels' => explode( ',', $this->settings['labels'] ),
				'summary' => sprintf( 'WPSITE UPDATES -- %s', get_option( 'blogname' ) ),
				'description' => $this->description,
				'issuetype' => array(
					'name' => ( true === $vulnerable ) ? 'Bug' : 'Task',
				),
			),
		);

		// To be safety.
		$server = trailingslashit( $this->settings['server'] );
		$url = $server . 'rest/api/latest/issue';
		$response = $this->curl( $url, json_encode( $data ) );

		if ( false !== $response ) {
			$jira_id = json_decode( $response );
			if ( isset( $jira_id->key ) ) {
				$data = '{"update": {"comment": [{"add": {"body": "Starts progress automatically"}}]},"transition": {"id": "' . $this->settings['progress_transition_id'] . '"}}';
				$url = $server . 'rest/api/latest/issue/' . $jira_id->key . '/transitions?expand=transitions.fields';
				$jira_id = $this->curl( $url, $data );
			}
		}

		// Generates new transitent.
		$transiten = $this->clean_transiten( $this->description );

		// Store description in a transition, so it only open a jira when there are new updates.
		set_transient( 'xdb_updates_transient', $transiten, 7 * DAY_IN_SECONDS );

		return $response;
	}

	/**
	 * The cURL function to all Jira rest API.
	 * TODO conver to wp.
	 *
	 * @param $url string - jira rest api.
	 * @param $data json - string with fields.
	 * @return $result json - string or boolean.
	 * @access   private
	 */
	private function curl( $url, $data ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_USERPWD, $this->settings['user'] . ':' . $this->settings['pwd'] );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); // for old versions. TODO: verify ssl
		// curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		$result = curl_exec( $ch );
		$ch_error = curl_error( $ch );

		if ( $ch_error ) {
			// Will hold any errors.
			$xdb_errors = new WP_Error();
			$xdb_errors->add( 'xdb_jir_api_error',  __( 'Error: The payload did not send to Jira', 'xdb' ) );
			return false;
		}
		curl_close( $ch );
		// echo "\n" . $result;
		return $result;
	}
}

/**
 * Run cron when plugin is activated crons.
 *
 * @param void
 * @return void
 */
function xdb_run_jira_on_activate() {
	do_action( 'xdb_rest_notify_jira' );
}
register_activation_hook( __FILE__, 'xdb_run_jira_on_activate' );

/**
 * De-register crons.
 *
 * @param void
 * @return void
 */
function xdb_run_jira_on_deactivate() {
	wp_clear_scheduled_hook( 'xdb_rest_notify_jira' );
}
register_deactivation_hook( __FILE__, 'xdb_run_jira_on_deactivate' );
