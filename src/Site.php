<?php

namespace SMW;

use MediaWiki\MediaWikiServices;
use SiteStats;

/**
 * @license GPL-2.0-or-later
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
	 * @return bool
	 */
	public static function isReadOnly() {
		// MediaWiki\Services\ServiceDisabledException from line 340 of
		// ...\ServiceContainer.php: Service disabled: DBLoadBalancer
		try {
			$isReadOnly = MediaWikiServices::getInstance()->getReadOnlyMode()->isReadOnly();
		} catch ( \MediaWiki\Services\ServiceDisabledException $e ) {
			$isReadOnly = true;
		}

		return $isReadOnly;
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public static function isReady() {
		// #3341
		// When running as part of the install don't try to access the DB
		// or update the Store
		if ( defined( 'MEDIAWIKI_INSTALL' ) && MEDIAWIKI_INSTALL ) {
			return false;
		}

		// Don't run any parsing or registration when the system isn't full
		// initialized also prevent issues like "... BadMethodCallException from
		// ... SessionManager.php Sessions are disabled for this entry point ..."
		//
		// https://github.com/wikimedia/mediawiki/blob/cdb7d53dcbb5af884d0d475e255730e35760489b/includes/user/User.php#L293-L317
		return !defined( 'MW_NO_SESSION' ) && $GLOBALS['wgFullyInitialised'];
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
	 * @return bool
	 */
	public static function isCommandLineMode() {
		return MW_ENTRY_POINT === 'cli' || defined( 'MEDIAWIKI_JOB_RUNNER' );
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
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
	 * @param string $affix
	 *
	 * @return string
	 */
	public static function id( string $affix = '' ): string {
		if ( $affix !== '' && $affix[0] !== ':' ) {
			$affix = ':' . $affix;
		}

		if ( version_compare( MW_VERSION, '1.40', '<' ) ) {
			return \WikiMap::getCurrentWikiId() . $affix;
		}

		return \MediaWiki\WikiMap\WikiMap::getCurrentWikiId() . $affix;
	}

	/**
	 * @since 3.0
	 *
	 * @return
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
