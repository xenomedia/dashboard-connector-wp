<?php
/**
 * Admin settings for Dashboard Connector WP plugin
 *
 * TODO: Add style to admin page
 * hide data in inputs
 *
 * @package  Dashboard_Connector_WP
 *
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Calls the class only in admin.
 */
function create_Dashboard_Connector_WP_Admin () {
	if ( is_admin() ) {
		new Dashboard_Connector_WP_Admin;
	}
}
add_action( 'init', 'create_Dashboard_Connector_WP_Admin' );

/**
 * Class that holds settings
 *
 * @package Dashboard_Connector_WP_Admin
 *
 * @since   1.0.0
 */
class Dashboard_Connector_WP_Admin {

	/**
	 * Holds settings group.
	 *
	 * @access  private
	 * @var     string
	 */
	private $group = 'xdb';

	/**
	 * Holds settings settings page slug.
	 *
	 * @access  private
	 * @var     string
	 */
	private $slug = 'dashboard-connector-wp';

	/**
	 * Holds settings id.
	 *
	 * @access  private
	 * @var     string
	 */
	private $settings = 'xdb_options';

	/**
	 * Holds the permission to access settings page.
	 *
	 * @access  private
	 * @var     string
	 */
	private $capability;

	/**
	 * Constructor of class.
	 *
	 * @access  public
	 *
	 * @since   1.0.0
	 */
	public function __construct() {
		// Sets capability.
		$this->capability = apply_filters( 'xdb_capability', 'manage_options' );

		// Creates admin page.
		add_action( 'admin_menu', array( $this, 'xdb_add_admin_menu' ) );

		// Inits Settings.
		add_action( 'admin_init', array( $this, 'xdb_options_init' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	public function activation() {}
	public function deactivation() {}

	/**
	 * Loads text domain.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'xdb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Creates settings page.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function xdb_add_admin_menu() {
		add_options_page( __( 'Dashboard Connector WP', 'xdb' ), __( 'Dashboard Connector WP', 'xdb' ), $this->capability, $this->slug, array( $this, 'xdb_options_page' ) );
	}

	/**
	 * Plugin settings.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function xdb_options_init() {
		register_setting( $this->group, $this->settings, array( &$this, 'sanitize_settings' ) );

		add_settings_section( 'xdb_options_section', '', '', $this->group );

		add_settings_field( 'xdb_rest', __( 'Dashboard Connector WP', 'xdb' ), array( $this, 'xdb_rest_render' ), $this->group, 'xdb_options_section'
		);

		add_settings_field( 'xdb_slack', __( 'Slack', 'xdb' ), array( $this, 'xdb_slack_render' ), $this->group, 'xdb_options_section'
		);

		add_settings_field( 'xdb_jira', __( 'Jira', 'xdb' ), array( $this, 'xdb_jira_render' ), $this->group, 'xdb_options_section'
		);
	}


	/**
	 * Renders original url input.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function xdb_rest_render() {
		$options = get_option( $this->settings );

		$setting_options = array(
			'r_client_id',
			'r_site_id',
			'r_url',
			'r_env',
			'r_user',
			'r_pwd',
		);

		foreach($setting_options as $list=>$o) {
			$opt = empty( $options[$o] ) ? '' : $options[$o];
			?>
			<label><?php echo ltrim($o, 'r_'); ?>: </label>
			<input type="<?php echo ( 'r_env' == $o || 'r_client_id' == $o || 'r_site_id' == $o) ? 'text' : 'password'; ?>" name='xdb_options[<?php echo $o; ?>]' value="<?php echo esc_attr( $opt, null );?>">
			<br />
			<?php
		}
	}

	/**
	 * Renders original url input.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function xdb_jira_render() {
		$options = get_option( $this->settings );

		$setting_options = array(
			'j_trans',
			'j_user',
			'j_pwd',
			'j_assign',
			'j_server',
			'j_proj',
			'j_labels',
		);

		foreach($setting_options as $list=>$o) {
			$opt = empty( $options[$o] ) ? '' : $options[$o];
			?>
			<label><?php echo ltrim($o, 'j_'); ?>: </label>
			<input type="<?php echo ( 'j_trans' == $o || 'j_labels' == $o || 'j_assign' == $o || 'j_proj' == $o) ? 'text' : 'password'; ?>" name='xdb_options[<?php echo $o; ?>]' value="<?php echo esc_attr( $opt, null );?>">
			<br />
			<?php
		}
	}

	/**
	 * Renders Slack input.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function xdb_slack_render() {
		$options = get_option( $this->settings );

		$setting_options = array(
			's_webhook',
			's_channels',
			's_notify',

		);

		foreach($setting_options as $list=>$o) {
			$opt = empty( $options[$o] ) ? '' : $options[$o];
			?>
			<label><?php echo ltrim($o, 's_'); ?>: </label>
			<input type="<?php echo ( 's_channels' == $o || 's_notify' == $o) ? 'text' : 'password'; ?>" name='xdb_options[<?php echo $o; ?>]' value="<?php echo esc_attr( $opt, null );?>">
			<br />
			<?php
		}
	}

	/**
	 * Sanitizes form values.
	 *
	 * @param   array - $args - from $_REQUESTS.
	 *
	 * @return  array - $input - sanitized values.
	 *
	 * @access  public
	 */
	public function sanitize_settings( $args ) {

		$input = array();

		// Check for our nonce name.
		$nonce = ! empty( $_REQUEST['_xdb_nonce'] ) ? $_REQUEST['_xdb_nonce'] : false;
		if ( ! $nonce ) {
			wp_die( __( 'Sorry, your nonce did not verify.', 'xdb' ) );
		}

		if ( ! wp_verify_nonce( $nonce, '_xdb_nonce' ) ) {
			wp_die( __( 'Sorry, your nonce did not verify.', 'xdb' ) );
		}

		// Check uset capability.
		if ( ! current_user_can( $this->capability ) ) {
			 wp_die( __( 'You do not have sufficient permissions to access this page.', 'xdb' ) );
		}

		$list_options = array(
			'j_trans',
			'j_user',
			'j_pwd',
			'j_assign',
			'j_server',
			'j_proj',
			'j_labels',

			's_webhook',
			's_channels',
			's_notify',

			'r_client_id',
			'r_site_id',
			'r_url',
			'r_env',
			'r_user',
			'r_pwd',

		);

		foreach ( $list_options as $option => $value) {

			if ( ! empty( $args[$value] ) ) {
				$input[$value] = sanitize_text_field( $args[$value] );
			}
		}
		return $input;
	}

	/**
	 * Renders settings page.
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access  public
	 */
	public function xdb_options_page() {
		?>
		<form action='options.php' method='post'>
			<h2><?php echo __( 'Dashboard Connector WP' ,'xdb' ); ?></h2>
			<?php
			wp_nonce_field( '_xdb_nonce', '_xdb_nonce' );
			settings_fields( $this->group );
			do_settings_sections( $this->group );
			submit_button();
			?>
		</form>
		<?php
	}
}

/**
 * Get options data from db.
 */
if ( ! function_exists( 'xdb_get_option' ) ) {
	function xdb_get_option( $option ) {
		$options = get_option( 'xdb_options' );

		return empty( $options[$option] ) ? false : $options[$option];
	}
}
