<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Site {

	/**
	 * Check whether the wiki is in read-only mode.
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public static function isReadOnly() {

		// MediaWiki\Services\ServiceDisabledException from line 340 of
		// ...\ServiceContainer.php: Service disabled: DBLoadBalancer
		try {
			$isReadOnly = wfReadOnly();
		} catch( \MediaWiki\Services\ServiceDisabledException $e ) {
			$isReadOnly = true;
		}

		return $isReadOnly;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public static function isCommandLineMode() {
		return $GLOBALS['wgCommandLineMode'];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $typeFilter
	 *
	 * @return array
	 */
	public static function getJobClasses( $typeFilter = '' ) {

		$jobList = $GLOBALS['wgJobClasses'];

		foreach ( $jobList as $type => $class ) {

			if ( $typeFilter === '' ) {
				continue;
			}

			if ( strpos( $type, $typeFilter ) === false ) {
				unset( $jobList[$type] );
			}
		}

		return $jobList;
	}

}
