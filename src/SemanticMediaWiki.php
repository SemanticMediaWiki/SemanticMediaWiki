<?php

namespace SMW;

use MediaWiki\MediaWikiServices;
use SMW\Exception\RemovedNamespaceIndexException;
use SMW\Setup\ConfigBootstrap;

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
		if ( isset( $GLOBALS['smwgNamespaceIndex'] ) ) {
			throw new RemovedNamespaceIndexException( (int)$GLOBALS['smwgNamespaceIndex'] );
		}

		if ( !defined( 'SMW_VERSION' ) && isset( $credits['version'] ) ) {
			define( 'SMW_VERSION', $credits['version'] );
			self::setupDefines();
			ConfigBootstrap::seedComputedDefaults();
			require_once __DIR__ . "/GlobalFunctions.php";
			self::applyConfigProfiles();
		}

		// enableSemantics is deprecated; SMW_EXTENSION_LOADED is set here.
		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			define( 'SMW_EXTENSION_LOADED', true );
		}

		// Registration point for required early registration
		Globals::replace(
			Setup::initExtension( $GLOBALS )
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
		$setup = new Setup();

		$setup->setHookContainer(
			MediaWikiServices::getInstance()->getHookContainer()
		);

		Globals::replace(
			$setup->init( $GLOBALS, __DIR__ )
		);
	}

	/**
	 * Applies the config profiles named in `$smwgConfigProfiles`.
	 *
	 * Profiles live in `data/config` and return a `setting => value` map that is
	 * assigned into `$GLOBALS`. Applied here, and not from LocalSettings.php,
	 * because a profile may extend a default that is only seeded once the
	 * extension is registered. An unknown name is ignored.
	 *
	 */
	private static function applyConfigProfiles(): void {
		foreach ( $GLOBALS['smwgConfigProfiles'] as $profile ) {
			$file = __DIR__ . '/../data/config/' . $profile . '.php';

			if ( !is_readable( $file ) ) {
				continue;
			}

			$config = require $file;

			if ( !is_array( $config ) ) {
				continue;
			}

			foreach ( $config as $key => $value ) {
				$GLOBALS[$key] = $value;
			}
		}
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

}

/**
 * @deprecated since 7.0.0
 */
class_alias( SemanticMediaWiki::class, 'SemanticMediaWiki' );
