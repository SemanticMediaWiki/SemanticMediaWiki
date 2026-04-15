<?php

namespace SMW;

use SMW\Services\ServicesFactory;
use UnexpectedValueException;

/**
 * @codeCoverageIgnore
 *
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * https://doc.semantic-mediawiki.org/ is assumed to be easier this way.
 */
class SemanticMediaWiki {

	/**
	 * @since 2.4
	 */
	public static function initExtension( array $credits = [] ): void {
		if ( !defined( 'SMW_VERSION' ) && isset( $credits['version'] ) ) {
			define( 'SMW_VERSION', $credits['version'] );
			self::setupDefines();
			self::setupGlobals();
			require_once __DIR__ . "/GlobalFunctions.php";
		}

		// enableSemantics is deprecated; SMW_EXTENSION_LOADED is set here.
		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			define( 'SMW_EXTENSION_LOADED', true );
		}

		// Registration point for required early registration
		Globals::replace(
			Setup::initExtension( $GLOBALS )
		);

		// Apparently this is required (1.28+) as the earliest possible execution
		// point in order for settings that refer to the SMW_NS_PROPERTY namespace
		// to be available in LocalSettings
		Globals::replace(
			NamespaceManager::initCustomNamespace( $GLOBALS )['newVars']
		);
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
	public static function onExtensionFunction(): void {
		$namespace = new NamespaceManager();
		Globals::replace(
			$namespace->init( $GLOBALS )
		);

		$setup = new Setup();

		$setup->setHookDispatcher(
			ServicesFactory::getInstance()->getHookDispatcher()
		);

		Globals::replace(
			$setup->init( $GLOBALS, __DIR__ )
		);
	}

	/**
	 * Load constants relevant to Semantic MediaWiki.
	 *
	 * Constants are defined in src/Defines.php, which is also registered in
	 * Composer's autoload.files so that they are available during
	 * LocalSettings.php (before the extension callback fires).
	 *
	 * @ingroup Constants
	 * @ingroup SMW
	 */
	public static function setupDefines(): void {
		require_once __DIR__ . '/Defines.php';
	}

	/**
	 * Get the array that DefaultSettings.php is supposed to return.  We did not put it inline here
	 * because there are references to that file online for documentation.
	 *
	 * @return array
	 * @throws UnexpectedValueException
	 */
	public static function getDefaultSettings(): array {
		static $settings = null;
		if ( $settings === null ) {
			$settings = include __DIR__ . '/DefaultSettings.php';
			if ( !is_array( $settings ) ) {
				throw new UnexpectedValueException( "Including DefaultSettings.php did not return an array." );
			}
		}
		return $settings;
	}

	/**
	 * Set up $GLOBALS according to what is found in DefaultSettings.php
	 *
	 * @return void
	 */
	public static function setupGlobals(): void {
		$defaultSettings = self::getDefaultSettings();
		foreach ( $defaultSettings as $key => $value ) {
			if ( !isset( $GLOBALS[$key] ) ) {
				$GLOBALS[$key] = $value;
			}
		}
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( SemanticMediaWiki::class, 'SemanticMediaWiki' );
