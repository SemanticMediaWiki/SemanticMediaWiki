<?php

use SMW\NamespaceManager;
use SMW\ApplicationFactory;
use SMW\Setup;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

SemanticMediaWiki::initExtension();

$GLOBALS['wgExtensionFunctions'][] = function() {
	SemanticMediaWiki::onExtensionFunction();
};

/**
 * @codeCoverageIgnore
 */
class SemanticMediaWiki {

	/**
	 * @since 2.4
	 */
	public static function initExtension() {

		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}

		define( 'SMW_VERSION', '2.4.5' );

		// Registration of the extension credits, see Special:Version.
		$GLOBALS['wgExtensionCredits']['semantic'][] = array(
			'path' => __FILE__,
			'name' => 'Semantic MediaWiki',
			'version' => SMW_VERSION,
			'author' => array(
				'[http://korrekt.org Markus Krötzsch]',
				'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
				'James Hong Kong',
				'[https://www.semantic-mediawiki.org/wiki/Contributors ...]'
				),
			'url' => 'https://www.semantic-mediawiki.org',
			'descriptionmsg' => 'smw-desc',
			'license-name'   => 'GPL-2.0+'
		);

		// A flag used to indicate SMW defines a semantic extension type for extension credits.
		// @deprecated, removal in SMW 3.0
		define( 'SEMANTIC_EXTENSION_TYPE', true );

		// Load class_alias
		require_once __DIR__ . '/src/Aliases.php';

		// Load global constants
		require_once __DIR__ . '/src/Defines.php';

		// Temporary measure to ease Composer/MW 1.22 migration
		require_once __DIR__ . '/src/NamespaceManager.php';

		// Load global functions
		require_once __DIR__ . '/src/GlobalFunctions.php';

		// Load default settings
		require_once __DIR__ . '/DefaultSettings.php';

		$GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] = $GLOBALS['smwgIP'] . 'i18n';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $GLOBALS['smwgIP'] . 'languages/SMW_Messages.php';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $GLOBALS['smwgIP'] . 'languages/SMW_Aliases.php';
		$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $GLOBALS['smwgIP'] . 'languages/SMW_Magic.php';

		self::onCanonicalNamespaces();
	}

	/**
	 * CanonicalNamespaces initialization
	 *
	 * @note According to T104954 registration via wgExtensionFunctions can be
	 * too late and should happen before that in case RequestContext::getLanguage
	 * invokes Language::getNamespaces before the `wgExtensionFunctions` execution.
	 *
	 * @see https://phabricator.wikimedia.org/T104954#2391291
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
	 * @Bug 34383
	 *
	 * @since 2.5
	 */
	public static function onCanonicalNamespaces() {
		$GLOBALS['wgHooks']['CanonicalNamespaces'][] = 'SMW\NamespaceManager::initCanonicalNamespaces';
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

		// 3.x reverse the order to ensure that smwgMainCacheType is used
		// as main and smwgCacheType being deprecated with 3.x
		$GLOBALS['smwgMainCacheType'] = $GLOBALS['smwgCacheType'];

		$applicationFactory = ApplicationFactory::getInstance();

		$namespace = new NamespaceManager( $GLOBALS );
		$namespace->init();

		$setup = new Setup( $applicationFactory, $GLOBALS, __DIR__ );
		$setup->run();
	}

	/**
	 * @since 2.4
	 *
	 * @return string|null
	 */
	public static function getVersion() {
		return SMW_VERSION;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public static function getEnvironment() {

		$store = '';

		if ( isset( $GLOBALS['smwgDefaultStore'] ) ) {
			$store = $GLOBALS['smwgDefaultStore'];
		};

		if ( strpos( strtolower( $store ), 'sparql' ) ) {
			$store .= '::' . strtolower( $GLOBALS['smwgSparqlDatabaseConnector'] );
		}

		return array(
			'store' => $store,
			'db'    => isset( $GLOBALS['wgDBtype'] ) ? $GLOBALS['wgDBtype'] : 'N/A'
		);
	}

}
