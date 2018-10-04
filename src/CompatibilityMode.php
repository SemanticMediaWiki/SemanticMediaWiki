<?php

namespace SMW;

/**
 * Internal benchmarks (XDebug) have shown that some extensions may affect the
 * performance to a greater degree than expected and can impose a performance
 * penalty to the overall system (templates, queries etc.).
 *
 * If a user is willing to incur those potential disadvantages by setting the
 * `CompatibilityMode`, s(he) is to understand the latent possibility of those
 * disadvantages.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CompatibilityMode {

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public static function extensionNotEnabled() {

		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			return !$GLOBALS['smwgSemanticsEnabled'];
		}

		$GLOBALS['smwgSemanticsEnabled'] = true;
		ApplicationFactory::getInstance()->getSettings()->set( 'smwgSemanticsEnabled', true );

		return false;
	}

	/**
	 * Allows to run `update.php` with a bare-bone setup in cases where enabledSemantics
	 * has not yet been enabled.
	 *
	 * @since 2.4
	 */
	public static function enableTemporaryCliUpdateMode() {
		$GLOBALS['smwgSemanticsEnabled'] = true;
		ApplicationFactory::getInstance()->getSettings()->set( 'smwgSemanticsEnabled', true );
		ApplicationFactory::getInstance()->getSettings()->set( 'smwgPageSpecialProperties', [ '_MDAT' ] );
	}

	/**
	 * @since 2.4
	 */
	public static function disableSemantics() {

		$disabledSettings = [
			'smwgSemanticsEnabled' => false,
			'smwgNamespacesWithSemanticLinks' => [],
			'smwgQEnabled' => false,
			'smwgAutoRefreshOnPurge' => false,
			'smwgAutoRefreshOnPageMove' => false,
			'smwgFactboxCacheRefreshOnPurge' => false,
			'smwgAdminFeatures' => false,
			'smwgPageSpecialProperties' => [],
			'smwgEnableUpdateJobs' => false,
			'smwgEnabledEditPageHelp' => false,
			'smwgParserFeatures' => SMW_PARSER_NONE,
		];

		foreach ( $disabledSettings as $key => $value) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
			$GLOBALS[$key] = $value;
		}
	}

}
