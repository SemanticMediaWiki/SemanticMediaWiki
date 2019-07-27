<?php

use SMW\NamespaceManager;
use SMW\Setup;

/**
 * @codeCoverageIgnore
 *
 * ExtensionRegistry only maps classes and functions after all extensions have
 * been queued from the LocalSettings.php resulting in DefaultSettings not being
 * loaded in-time.
 *
 * When changing the load order, please ensure that this function is run either
 * via Composer's autoloading or as part of your internal registration.
 */
SemanticMediaWiki::load();

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
	 * @since 2.5
	 *
	 * @note It is expected that this function is loaded before LocalSettings.php
	 * to ensure that settings and global functions are available by the time
	 * the extension is activated.
	 */
	public static function load() {

		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}

		include_once __DIR__ . '/src/Aliases.php';
		include_once __DIR__ . '/src/Defines.php';
		include_once __DIR__ . '/src/GlobalFunctions.php';

		// If the function is called more than once then this will fail on
		// purpose
		foreach ( include __DIR__ . '/DefaultSettings.php' as $key => $value ) {
			if ( !isset( $GLOBALS[$key] ) ) {
				$GLOBALS[$key] = $value;
			}
		}

		// Registration point before any `extension.json` invocation
		// takes place
		Setup::checkExtensionRegistration( $GLOBALS );
	}

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

		/**
		 * @see https://www.mediawiki.org/wiki/Localisation#Localising_namespaces_and_special_page_aliases
		 */
		$GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] = __DIR__ . '/i18n';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = __DIR__ . '/i18n/extra/SemanticMediaWiki.alias.php';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = __DIR__ . '/i18n/extra/SemanticMediaWiki.magic.php';

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
		$setup->init( $GLOBALS, __DIR__ );
	}

}
