<?php

namespace SMW;

use SiteStats;

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
	 * @return string
	 */
	public static function name() {
		return $GLOBALS['wgSitename'];
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function languageCode() {
		return $GLOBALS['wgLanguageCode'];
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
	 * @return boolean
	 */
	public static function isCapitalLinks() {
		return $GLOBALS['wgCapitalLinks'];
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public static function stats() {
		return [
			'pageCount' => SiteStats::pages(),
			'contentPageCount' => SiteStats::articles(),
			'mediaCount' => SiteStats::images(),
			'editCount' => SiteStats::edits(),
			'userCount' => SiteStats::users(),
			'adminCount' => SiteStats::numberingroup( 'sysop' )
		];
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
