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
	 * @return boolean
	 */
	public static function isBlocked() {
		return defined( 'MEDIAWIKI_INSTALL' ) && MEDIAWIKI_INSTALL;
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
	 * @since 3.1
	 *
	 * @return string
	 */
	public static function searchType() {
		return $GLOBALS['wgSearchType'];
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function wikiurl() {
		return $GLOBALS['wgServer'] . str_replace( '$1', '', $GLOBALS['wgArticlePath'] );
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

		// MW 1.27 wgCommandLineMode isn't set correctly
		if ( ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) ) {
			return true;
		}

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
	 * @since 3.1
	 *
	 * @return int
	 */
	public static function getCacheExpireTime( $key ) {

		if ( $key === 'parser' ) {
			return $GLOBALS['wgParserCacheExpireTime'];
		}

		return 0;
	}

	/**
	 * @since 3.0
	 *
	 * @param $affix string
	 *
	 * @return string
	 */
	public static function id( $affix = '' ) {

		if ( $affix !== '' && $affix[0] !== ':' ) {
			$affix = ':' . $affix;
		}

		return wfWikiID() . $affix;
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

		if ( $typeFilter === 'SMW' ) {
			$typeFilter = 'smw.';
		}

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
