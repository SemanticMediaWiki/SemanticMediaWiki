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

}
