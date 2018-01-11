<?php

/**
 * @file
 * Contains PHPChecker
 */


require_once plugin_dir_path( __FILE__ ) . 'CheckerInterface.php';

/**
 * Checks whether the current PHP version is supported.
 *
 * End of Life Dates
 * The most recent branches to reach end of life status are:
 *
 * 7.0:  3 Dec 2018 (1543795200)
 * 5.6: 28 Aug 2017 (1503878400)
 * 5.5: 20 Jul 2016 (1468108800)
 * 5.4:  3 Sep 2015
 * 5.3: 14 Aug 2014
 *
 * @See http://php.net/releases/index.php
 */
class PHPChecker implements CheckerInterface {

	/**
	 * {@inheritdoc}
	 */
	public function getChecks() {
		$eol     = false;
		$checks  = array();
		$version = $this->getVersion();
		$time    = $this->getTime();

		// Anything older than 5.5 has been end-of-lifed already.
		if ( $version < 50500 ) {
			$eol = true;
		} // 5.5 will be EOL 10 Jul 2016.
		elseif ( $version < 50600 && $time > 1468108800 ) {
			$eol = true;
		} // 5.6 will be EOL 28 Aug 2017.
		elseif ( $version < 70000 && $time > 1503878400 ) {
			$eol = true;
		} // Assuming the next is 7.1, 7.0 will be EOL 3 Dec 2018.
		elseif ( $version < 70100 && $time > 1543795200 ) {
			$eol = true;
		}

		if ( $eol ) {
			$checks[] = array(
				'name'        => 'version',
				'description' => $this->t( 'PHP !version is no longer maintained.', array( '!version' => PHP_VERSION ) ),
				'type'        => 'php',
				'alert_level' => 'error',
			);
		} else {
			$checks[] = array(
				'name'        => 'version',
				'description' => $this->t( 'Running on PHP !version.', array( '!version' => PHP_VERSION ) ),
				'type'        => 'php',
				'alert_level' => 'notice',
			);
		}

		return $checks;
	}

	/**
	 * Proxy for PHP_VERSION_ID to help unit testing.
	 */
	protected function getVersion() {
		// Ensure we have all the defines we're looking for, even if running
		// on a PHP from the stone age.
		if ( ! defined( 'PHP_VERSION_ID' ) ) {
			$version = explode( '.', PHP_VERSION );
			return ( $version[0] * 10000 + $version[1] * 100 + $version[2] );
		}
		return PHP_VERSION_ID;
	}

	/**
	 * Proxy for time() to help unit testing.
	 */
	protected function getTime() {
		return time();
	}

	/**
	 * Proxy for t() to help unit testing.
	 *
	 * @param string $string
	 *   A string containing the English string to translate.
	 * @param array  $args
	 *   An associative array of replacements to make after translation.
	 * @param array  $options
	 *   An associative array of additional options
	 *
	 * @return string
	 *   The translated string.
	 */
	protected function t( $string, array $args = array() ) {
		// @codingStandardsIgnoreStart
		return strtr($string, $args);
		// @codingStandardsIgnoreEnd
	}

}
