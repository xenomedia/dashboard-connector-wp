<?php
/**
 * Creates and checks for tokens.
 *
 * @package xeno-dashboard
 */

/**
 * Creates Json Web Token
 *
 * @param void
 *
 * @return string
 *
 * @since 1.0.0
 */
function xdb_get_supertoken() {
	$supersecret = '';

	// To be save, check if function exits to get the secreat from the db.
	if ( function_exists( 'xdb_get_option' ) ) {
		$supersecret = xdb_get_option( $setting = 'secret' );
	}
	// If secreat is not set in db, then look into wp-config.php.
	if ( empty( $supersecret ) ) {
		if ( defined( 'XDB_REST_SECRET' ) ) {
			$supersecret = XDB_REST_SECRET;
		}
	}

	// If there is secret then rest won't work.
	if ( empty( $supersecret ) ) {
		 return false;
	}

	// JWT header.
	$encoded_header = base64_encode(
		wp_json_encode(
			array(
				'alg' => 'HS256',
				'typ' => 'JWT',
			)
		)
	);

	// JWT payload.
	$encoded_payload = base64_encode(
		wp_json_encode(
			array(
				'score' => '12',
				'name'  => 'Crille',
			)
		)
	);

	// JWT combined.
	$header_and_payload_combined = $encoded_header . '.' . $encoded_payload;

	// JWT Signature.
	$signature = base64_encode(
		hash_hmac(
			'sha256',
			$header_and_payload_combined,
			$supersecret,
			true
		)
	);

	return $header_and_payload_combined . '.' . $signature;

}

/**
 * Checks for super token.
 *
 * @param string $recieved_jwt json web token received.
 *
 * @return bool true if $signature is equal $recieved_signature otherwise false.
 *
 * @since 1.0.0
 */
function xdb_check_supertoken( $recieved_jwt ) {

	$supersecret = '';

	$jwt_values = explode( '.', $recieved_jwt );

	if ( count( $jwt_values ) != 3 ) {
		return false;
	}

	if ( function_exists( 'xdb_get_option' ) ) {
		$supersecret = xdb_get_option( $setting = 'secret' );
	}
	if ( empty( $supersecret ) ) {
		if ( defined( 'XDB_REST_SECRET' ) ) {
			$supersecret = XDB_REST_SECRET;
		}
	}

	// JWT Payload.
	$recieved_header_and_payload = $jwt_values[0] . '.' . $jwt_values[1];

	// JWT Signature received.
	$recieved_signature = $jwt_values[2];

	// JWT Signature.
	$signature = base64_encode(
		hash_hmac(
			'sha256',
			$recieved_header_and_payload,
			$supersecret,
			true
		)
	);

	return ( $signature == $recieved_signature );
}

/**
 * TODO for admin settings page, hides strings in input.
 */
function xdb_get_starred( $str ) {
	return substr( $str, 0, 1 ) . str_repeat( '*', $len = strlen( $str ) - 2 ) . substr( $str, $len - 1, 1 );
}
