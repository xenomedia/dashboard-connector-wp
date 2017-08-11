<?php
/**
 * Plugin Name:     Xeno Dashboard
 * Plugin URI:      xeno-dashboard
 * Description:     Xeno Dashboard
 * Author:          Xeno Staff <carolina@xenomedia.com>
 * Author URI:      Xeno Staff
 * Text Domain:     xeno-dashboard
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Xeno_Dashboard
 */

// Loads admin settings page.
require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';

// Buils Xeno Dashboard REST api.
require_once plugin_dir_path( __FILE__ ) . 'includes/rest.php';

// Connect and talks to Slack.
require_once plugin_dir_path( __FILE__ ) . 'includes/slack.php';

// Connects and creates Jiras.
require_once plugin_dir_path( __FILE__ ) . 'includes/jira.php';

// Security.
require_once plugin_dir_path( __FILE__ ) . 'includes/secure.php';

/**
 * Get settings from table wp_options, if not there then in wp-config.php
 */
function xdb_get_settings( $setting, $denifed ) {
	$return = false;
	if ( function_exists( 'xdb_get_option' ) ) {
		$return = xdb_get_option( $setting );
	}

	if ( defined( $denifed ) ) {
		$return = constant( $denifed );
	}
	return $return;
}

/**
 * Enviroment indicator.
 */
function xdb_env_indicator() {
	$colors = array(
		'dev' => '#aa3333',
		'test' => '#ceaf01',
		'prod' => 'rgb(0, 0, 187)',
	);
	$env = xdb_get_settings( $setting = 'r_env', $defined = 'XDB_ENV' );
	?>
	<style>
	#wpadminbar{background: <?php echo isset( $colors[ $env ] ) ? $colors[ $env ] : 'black' ; ?>}
	</style>
	<?php
}
add_action( 'admin_head', 'xdb_env_indicator' );


// TODO: REMOVE AFTER PLUGIN IS TESTED AND ACTIVE.
function sendTestEmail() {
	$to = 'carolina@xenomedia.com';
	$subject = 'Xeno vulnerabilities';
	$body = get_bloginfo( 'url' ) . "\n" . get_bloginfo( 'name' );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$send = wp_mail( $to, $subject, $body, $headers );
}
