<?php

/**
 * @file
 * Contains ${NAMESPACE}\CheckerInterface
 */

/**
 * Provides an interface for status checkers.
 */
interface CheckerInterface {

	/**
	 * Gets the checks.
	 *
	 * @return array
	 *   An array of checks.
	 */
	public function getChecks();
}

