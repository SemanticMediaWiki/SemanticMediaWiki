<?php

use SMW\NamespaceManager;
use SMW\Services\ServicesFactory;
use SMW\Setup;
use SMW\SetupCheck;

/**
 * @codeCoverageIgnore
 *
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * https://doc.semantic-mediawiki.org/ is assumed to be easier this way.
 *
 * @defgroup SMW Semantic MediaWiki
 */
class SemanticMediaWiki {

	/**
	 * @since 2.4
	 */
	public static function initExtension( $credits = [] ) {

		if ( !defined( 'SMW_VERSION' ) && isset( $credits['version'] ) ) {
			define( 'SMW_VERSION', $credits['version'] );
		}

		// https://phabricator.wikimedia.org/T212738
		if ( !defined( 'MW_VERSION' ) ) {
			define( 'MW_VERSION', $GLOBALS['wgVersion'] );
		}

		// Only allow to set the loading state while being part of the test
		// environment
		if ( defined( 'MW_PHPUNIT_TEST' )  && !defined( 'SMW_EXTENSION_LOADED' ) ) {
			define( 'SMW_EXTENSION_LOADED', true );
		}

		$defaultSettings = include_once __DIR__ . '/DefaultSettings.php';
		if ( is_array( $defaultSettings ) ) {
			foreach ( $defaultSettings as $key => $value ) {
				if ( !isset( $GLOBALS[$key] ) ) {
					$GLOBALS[$key] = $value;
				}
			}
		}
		// Registration point for required early registration
		Setup::initExtension( $GLOBALS );
	}

	/**
	 * Setup and initialization
	 *
	 * @note $wgExtensionFunctions variable is an array that stores
	 * functions to be called after most of MediaWiki initialization
	 * has finalized
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions
	 *
	 * @since  1.9
	 */
	public static function onExtensionFunction() {

		$namespace = new NamespaceManager();
		$namespace->init( $GLOBALS );

		$setup = new Setup();

		$setup->setHookDispatcher(
			ServicesFactory::getInstance()->getHookDispatcher()
		);

		$setup->init( $GLOBALS, __DIR__ );
	}

}
