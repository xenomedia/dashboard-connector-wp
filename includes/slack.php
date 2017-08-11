<?php
/**
 * Comunicates with Slack.
 *
 * @package xeno_dashboard
 *
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Slack webhook url.
 *
 * @since 1.0.0
 */
define( 'SLACK_WH', 'https://hooks.slack.com/services/' );

/**
 * Stablish Slack communication.
 *
 * @since 1.0.0
 */
class Xeno_Dashboard_Slack {

	/**
	 * Holds settings.
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Slack Webhook.
		$setting_webhook = xdb_get_settings( $setting = 's_webhook', $defined = 'XDB_SLACK_WEBHOOK' );

		// Slack connections.
		$this->settings = array(
			'enable' => ! empty( $setting_webhook ),
			'end_point' => SLACK_WH . $setting_webhook,
			'bot_name' => __( 'Xeno Dashboard', 'xdb' ),
			'bot_icon' => '',
			'channels' => xdb_get_settings( $setting = 's_channels', $defined = 'XDB_SLACK_CHANNELS' ),
			'notify' => xdb_get_settings( $setting = 's_notify', $defined = 'XDB_SLACK_NOTIFY' ),
		);
	}

	/**
	 * Set admin error notices
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access   public
	 */
	public function xdb_admin_notice() {
		?>
		<div class="notice error my-acf-notice is-dismissible" >
			<p><?php _e( 'Xeno dashboard needs to be configurated!', 'xdb' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get updates notifications
	 *
	 * @param void
	 *
	 * @return void
	 *
	 * @access   public
	 */
	public function talk( $type = 'all', $updates_only = false ) {
		// Class Xeno_Dashboard_Updates.
		require_once plugin_dir_path( __FILE__ ) . 'updates.php';

		// Array $data will hold all plugins, theme and core data.
		$data = array();

		$Xeno_Dashboard_Updates = new Xeno_Dashboard_Updates();

		// Core.
		if ( empty( $type ) || 'all' == $type || 'core' == $type ) {
			$Xeno_Dashboard_Updates->prepare_core_response( $data );
		}

		// Plugins.
		if ( empty( $type ) || 'all' == $type || 'plugins' == $type ) {
			$Xeno_Dashboard_Updates->prepare_plugins_response( $data );
		}

		// Themes.
		if ( empty( $type ) || 'all' == $type || 'themes' == $type ) {
			$Xeno_Dashboard_Updates->prepare_themes_response( $data );
		}

		$vulnerable = false;
		// Build fields.
		$the_fields = array();

		if ( ! empty( $data ) ) {

			// Setup each attachment.
			foreach ( $data as $attachments => $attachment ) {
				if ( true === $updates_only && false !== strpos( strtolower( $attachment['description'] ), 'up to date' ) ) {
					unset( $attachment );
				} else {

					$field = array(
						'title' => $attachment['name'],
						'value' => $attachment['description'],
						'short' => true,
					);

					if ( 'notice' == $attachment['alert_level'] && false === $vulnerable ) {
						$vulnerable = true;
					}

					$the_fields[] = $field;
				}
			}
		}

		$this->send_reponse_to_slack( $the_fields, $vulnerable );
	}

	/**
	 * Send the notification thought Slack API.
	 *
	 * @param   array $args - slack notifications settings.
	 *
	 * @return  boolean $string - API response.
	 *
	 * @access   private
	 */
	private function send_reponse_to_slack( $the_fields, $vulnerable ) {
		// Checks if slack notifications are enabled.
		if ( false === $this->settings['enable'] ) {
			return false;
		}

		if ( empty( $the_fields ) ) {
			return false;
		}

		$webhook_url = $this->settings['end_point'] ;

		// Set defaults.
		$payload = array(
			'channel'       => 'jenkins-ci',
			'username'      => get_bloginfo( 'name' ),
			'text'          => sprintf( '*<%1$s|%2$s>*' . "\n" . '%3$s', get_bloginfo( 'url' ), get_bloginfo( 'name' ), 'Xeno vulnerabilities tests' ),
			'icon_emoji'    => ( $vulnerable ) ? ':fire' : ':mega:',
			'icon_url'      => trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) ) . 'assets/images/xeno.png',
			'attachments'   => array(),
		);

		// Set field defaults.
		$field_defaults = array(
			'title' => null,
			'value' => null,
			'short' => false,
		);

		$payload['attachments'][] = array(
			'color'         => ( $vulnerable ) ? '#d52121' : '#21759b', // Default color.
			'fields'        => $the_fields,
		);

		// Channels.
		$channels = $this->settings['channels'];

		// Make sure its an array.
		if ( ! empty( $channels ) && ! is_array( $channels ) ) {
			$channels = explode( ',', str_replace( ' ', '', $channels ) );
		}

		// If channel is empty, add a blank one so it sends to the default channel.
		if ( empty( $channels ) ) {
			$channels[] = '';
		}

		// Will hold any errors.
		$xdb_errors = new WP_Error();

		// Talks to channels.
		foreach ( $channels as $channel ) {

			// Add channel to the payload.
			$payload['channel'] = $channel;

			// Send to Slack.
			$slack_response = wp_remote_post(
				$webhook_url, array(
					'sslverify' => false, // for old versions.
				'body'      => json_encode( $payload ),
				'headers'   => array(
					'Content-Type' => 'application/json',
				 ),
				)
			);

			// Handle errors.
			if ( is_wp_error( $slack_response ) ) {

				// Set an error.
				$xdb_errors->add( 'xdb_slack_api_error', $slack_response->get_error_message() );

			} elseif ( ! empty( $slack_response['response'] )
				&& ! empty( $slack_response['response']['code'] )
				&& '200' != $slack_response['response']['code'] ) {

				// Set an error.
				$xdb_errors->add( 'xdb_slack_api_error',  __( 'Error: The payload did not send to Slack', 'xdb' ) );

			}
		}

		// Returns error in case there is any.
		$error_messages = $xdb_errors->get_error_messages();
		if ( ! empty( $error_messages ) ) {
			return $xdb_errors;
		}

		return true;
	}
}
