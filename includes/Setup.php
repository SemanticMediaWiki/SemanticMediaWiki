<?php

namespace SMW;

use ParserHooks\HookDefinition;
use ParserHooks\HookRegistrant;
use SMW\MediaWiki\Hooks\HookRegistry;

/**
 * Extension setup and registration
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
final class Setup {

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var array
	 */
	private $globalVars;

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @since 1.9
	 *
	 * @param ApplicationFactory $applicationFactory
	 * @param array &$globals
	 * @param string $directory
	 */
	public function __construct( ApplicationFactory $applicationFactory, &$globals, $directory ) {
		$this->applicationFactory = $applicationFactory;
		$this->globalVars =& $globals;
		$this->directory = $directory;
	}

	/**
	 * @since 1.9
	 */
	public function run() {
		$this->addSomeDefaultConfigurations();
		$this->registerSettings();

		$this->registerConnectionProviders();

		$this->registerI18n();
		$this->registerWebApi();
		$this->registerJobClasses();
		$this->registerSpecialPages();
		$this->registerPermissions();

		$this->registerParamDefinitions();
		$this->registerFooterIcon();
		$this->registerHooks();
	}

	private function addSomeDefaultConfigurations() {

		$this->globalVars['smwgMasterStore'] = null;
		$this->globalVars['smwgIQRunningNumber'] = 0;

		if ( !isset( $this->globalVars['smwgNamespace'] ) ) {
			$this->globalVars['smwgNamespace'] = parse_url( $this->globalVars['wgServer'], PHP_URL_HOST );
		}

		if ( !isset( $this->globalVars['smwgScriptPath'] ) ) {
			$this->globalVars['smwgScriptPath'] = ( $this->globalVars['wgExtensionAssetsPath'] === false ? $this->globalVars['wgScriptPath'] . '/extensions' : $this->globalVars['wgExtensionAssetsPath'] ) . '/SemanticMediaWiki';
		}

		if ( is_file( $this->directory . "/res/Resources.php" ) ) {
			$this->globalVars['wgResourceModules'] = array_merge( $this->globalVars['wgResourceModules'], include ( $this->directory . "/res/Resources.php" ) );
		}
	}

	private function registerSettings() {
		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromGlobals( $this->globalVars )
		);
	}

	private function registerConnectionProviders() {

		$mwCollaboratorFactory = $this->applicationFactory->newMwCollaboratorFactory();

		$connectionManager = new ConnectionManager();

		$connectionManager->registerConnectionProvider(
			DB_MASTER,
			$mwCollaboratorFactory->newLazyDBConnectionProvider( DB_MASTER )
		);

		$connectionManager->registerConnectionProvider(
			DB_SLAVE,
			$mwCollaboratorFactory->newLazyDBConnectionProvider( DB_SLAVE )
		);

		$connectionManager->registerConnectionProvider(
			'mw.db',
			$mwCollaboratorFactory->newMediaWikiDatabaseConnectionProvider()
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionMessagesFiles
	 */
	private function registerI18n() {

		$smwgIP = $this->applicationFactory->getSettings()->get( 'smwgIP' );

		$this->globalVars['wgMessagesDirs']['SemanticMediaWiki'] = $smwgIP . 'i18n';
		$this->globalVars['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php';
		$this->globalVars['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $smwgIP . 'languages/SMW_Aliases.php';
		$this->globalVars['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $smwgIP . 'languages/SMW_Magic.php';
		$this->globalVars['wgExtensionMessagesFiles']['SemanticMediaWikiNamespaces'] = $smwgIP . 'languages/SemanticMediaWiki.namespaces.php';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAPIModules
	 */
	private function registerWebApi() {
		$this->globalVars['wgAPIModules']['smwinfo'] = '\SMW\MediaWiki\Api\Info';
		$this->globalVars['wgAPIModules']['ask']     = '\SMW\MediaWiki\Api\Ask';
		$this->globalVars['wgAPIModules']['askargs'] = '\SMW\MediaWiki\Api\AskArgs';
		$this->globalVars['wgAPIModules']['browsebysubject']  = '\SMW\MediaWiki\Api\BrowseBySubject';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgJobClasses
	 */
	private function registerJobClasses() {
		$this->globalVars['wgJobClasses']['SMW\UpdateJob']  = 'SMW\MediaWiki\Jobs\UpdateJob';
		$this->globalVars['wgJobClasses']['SMW\RefreshJob'] = 'SMW\MediaWiki\Jobs\RefreshJob';
		$this->globalVars['wgJobClasses']['SMW\UpdateDispatcherJob'] = 'SMW\MediaWiki\Jobs\UpdateDispatcherJob';
		$this->globalVars['wgJobClasses']['SMW\DeleteSubjectJob'] = 'SMW\MediaWiki\Jobs\DeleteSubjectJob';
		$this->globalVars['wgJobClasses']['SMW\ParserCachePurgeJob'] = 'SMW\MediaWiki\Jobs\ParserCachePurgeJob';

		// Legacy definition to be removed with 1.10
		$this->globalVars['wgJobClasses']['SMWUpdateJob']  = 'SMW\MediaWiki\Jobs\UpdateJob';
		$this->globalVars['wgJobClasses']['SMWRefreshJob'] = 'SMW\MediaWiki\Jobs\RefreshJob';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 */
	private function registerPermissions() {

		// Rights
		$this->globalVars['wgAvailableRights'][] = 'smw-admin';

		// User group rights
		if ( !isset( $this->globalVars['wgGroupPermissions']['sysop']['smw-admin'] ) ) {
			$this->globalVars['wgGroupPermissions']['sysop']['smw-admin'] = true;
		}

		if ( !isset( $this->globalVars['wgGroupPermissions']['smwadministrator']['smw-admin'] ) ) {
			$this->globalVars['wgGroupPermissions']['smwadministrator']['smw-admin'] = true;
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgSpecialPages
	 */
	private function registerSpecialPages() {

		$specials = array(
			'Ask' => array(
				'page' => 'SMWAskPage',
				'group' => 'smw_group'
			),
			'Browse' => array(
				'page' =>  'SMWSpecialBrowse',
				'group' => 'smw_group'
			),
			'PageProperty' => array(
				'page' =>  'SMWPageProperty',
				'group' => 'smw_group'
			),
			'SearchByProperty' => array(
				'page' => 'SMW\MediaWiki\Specials\SpecialSearchByProperty',
				'group' => 'smw_group'
			),
			'SMWAdmin' => array(
				'page' => 'SMWAdmin',
				'group' => 'smw_group'
			),
			'SemanticStatistics' => array(
				'page' => 'SMW\SpecialSemanticStatistics',
				'group' => 'wiki'
			),
			'Concepts' => array(
				'page' => 'SMW\SpecialConcepts',
				'group' => 'pages'
			),
			'ExportRDF' => array(
				'page' => 'SMWSpecialOWLExport',
				'group' => 'smw_group'
			),
			'Types' => array(
				'page' => 'SMWSpecialTypes',
				'group' => 'pages'
			),
			'URIResolver' => array(
				'page' => 'SMWURIResolver'
			),
			'Properties' => array(
				'page' => 'SMW\SpecialProperties',
				'group' => 'pages'
			),
			'UnusedProperties' => array(
				'page' => 'SMW\SpecialUnusedProperties',
				'group' => 'maintenance'
			),
			'WantedProperties' => array(
				'page' => 'SMW\SpecialWantedProperties',
				'group' => 'maintenance'
			),
			'DeferredRequestDispatcher' => array(
				'page' => 'SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher',
				'group' => 'maintenance'
			),
		);

		// Register data
		foreach ( $specials as $special => $page ) {
			$this->globalVars['wgSpecialPages'][$special] = $page['page'];

			if ( isset( $page['group'] ) ) {
				$this->globalVars['wgSpecialPageGroups'][$special] = $page['group'];
			}
		}
	}

	private function registerParamDefinitions() {
		$this->globalVars['wgParamDefinitions']['smwformat'] = array(
			'definition'=> 'SMWParamFormat',
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgFooterIcons
	 */
	private function registerFooterIcon() {

		if( isset( $this->globalVars['wgFooterIcons']['poweredby']['semanticmediawiki'] ) ) {
			return;
		}

		$pathParts = ( explode( '/extensions/', str_replace( DIRECTORY_SEPARATOR, '/', __DIR__), 2 ) );

		$this->globalVars['wgFooterIcons']['poweredby']['semanticmediawiki'] = array(
			'src' => $this->globalVars['wgScriptPath'] . '/extensions/'
				. end( $pathParts )
				. '/../res/images/smw_button.png',
			'url' => 'https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
			'alt' => 'Powered by Semantic MediaWiki',
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$this->globalVars['wgHooks']
	 *
	 * @note $this->globalVars['wgHooks'] contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 */
	private function registerHooks() {

		$hookRegistry = new HookRegistry( $this->globalVars, $this->directory );
		$hookRegistry->register();

		// Old-style registration
		$this->globalVars['wgHooks']['AdminLinks'][] = 'SMWHooks::addToAdminLinks';
		$this->globalVars['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWHooks::onPageSchemasRegistration';

		$this->globalVars['wgHooks']['ParserFirstCallInit'][] = function( \Parser &$parser ) {
			$hookRegistrant = new HookRegistrant( $parser );

			$infoFunctionDefinition = InfoParserFunction::getHookDefinition();
			$infoFunctionHandler = new InfoParserFunction();
			$hookRegistrant->registerFunctionHandler( $infoFunctionDefinition, $infoFunctionHandler );
			$hookRegistrant->registerHookHandler( $infoFunctionDefinition, $infoFunctionHandler );

			$docsFunctionDefinition = DocumentationParserFunction::getHookDefinition();
			$docsFunctionHandler = new DocumentationParserFunction();
			$hookRegistrant->registerFunctionHandler( $docsFunctionDefinition, $docsFunctionHandler );
			$hookRegistrant->registerHookHandler( $docsFunctionDefinition, $docsFunctionHandler );

			return true;
		};
	}

}
