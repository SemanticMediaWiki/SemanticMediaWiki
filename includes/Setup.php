<?php

namespace SMW;

use SMW\MediaWiki\Hooks\HookRegistry;
use Hooks;

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
	 * Runs at the earliest possible event to initialize functions or hooks that
	 * are otherwise too late for the hook system to recognized.
	 *
	 * @since 3.0
	 */
	public static function initExtension( &$vars ) {

		/**
		 * @see https://www.mediawiki.org/wiki/Localisation#Localising_namespaces_and_special_page_aliases
		 */
		$vars['wgMessagesDirs']['SemanticMediaWiki'] = $vars['smwgIP'] . 'i18n';
		$vars['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $vars['smwgIP'] . 'i18n/extra/SemanticMediaWiki.alias.php';
		$vars['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $vars['smwgIP'] . 'i18n/extra/SemanticMediaWiki.magic.php';

		HookRegistry::initExtension( $vars );
	}

	/**
	 * @see HookRegistry::initExtension
	 */
	public static function getAPIModules() {

		if ( !ApplicationFactory::getInstance()->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return [];
		}

		return [
			'smwinfo' => '\SMW\MediaWiki\Api\Info',
			'smwtask' => '\SMW\MediaWiki\Api\Task',
			'smwbrowse' => '\SMW\MediaWiki\Api\Browse',
			'ask' => '\SMW\MediaWiki\Api\Ask',
			'askargs' => '\SMW\MediaWiki\Api\AskArgs',
			'browsebysubject' => '\SMW\MediaWiki\Api\BrowseBySubject',
			'browsebyproperty' => '\SMW\MediaWiki\Api\BrowseByProperty'
		];
	}

	/**
	 * @see HookRegistry::initExtension
	 */
	public static function initSpecialPageList( array &$specialPages ) {

		if ( !ApplicationFactory::getInstance()->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		$specials = array(
			'Ask' => array(
				'page' => 'SMW\MediaWiki\Specials\SpecialAsk',
				'group' => 'smw_group'
			),
			'Browse' => array(
				'page' =>  'SMW\MediaWiki\Specials\SpecialBrowse',
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
			'ProcessingErrorList' => array(
				'page' => 'SMW\MediaWiki\Specials\SpecialProcessingErrorList',
				'group' => 'smw_group'
			),
			'PropertyLabelSimilarity' => array(
				'page' => 'SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity',
				'group' => 'smw_group'
			),
			'SMWAdmin' => array(
				'page' => 'SMW\MediaWiki\Specials\SpecialAdmin',
				'group' => 'smw_group'
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
				'page' => 'SMW\MediaWiki\Specials\SpecialURIResolver'
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
			$specialPages[$special] = $page['page'];

			if ( isset( $page['group'] ) ) {
				$GLOBALS['wgSpecialPageGroups'][$special] = $page['group'];
			}
		}
	}

	/**
	 * @since 1.9
	 */
	public function run() {

		$this->addSomeDefaultConfigurations();

		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::disableSemantics();
		}

		$this->registerConnectionProviders();
		$this->registerMessageCallbackHandler();

		$this->registerJobClasses();
		$this->registerPermissions();

		$this->registerParamDefinitions();
		$this->registerFooterIcon();
		$this->registerHooks();

		Hooks::run( 'SMW::Setup::AfterInitializationComplete', [ &$this->globalVars ] );
	}

	private function addSomeDefaultConfigurations() {

		$this->globalVars['wgLogTypes'][] = 'smw';
		$this->globalVars['wgFilterLogTypes']['smw'] = true;

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
			$mwCollaboratorFactory->newMediaWikiDatabaseConnectionProvider( 'mw.db' )
		);

		// Connection can be used to redirect queries to another DB cluster
		$queryengineConnectionProvider = $mwCollaboratorFactory->newMediaWikiDatabaseConnectionProvider( 'mw.db.queryengine' );
		$queryengineConnectionProvider->resetTransactionProfiler();

		$connectionManager->registerConnectionProvider(
			'mw.db.queryengine',
			$queryengineConnectionProvider
		);
	}

	private function registerMessageCallbackHandler() {

		Message::registerCallbackHandler( Message::TEXT, function( $arguments, $language ) {

			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			return call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language )->text();
		} );

		Message::registerCallbackHandler( Message::ESCAPED, function( $arguments, $language ) {

			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			return call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language )->escaped();
		} );

		Message::registerCallbackHandler( Message::PARSE, function( $arguments, $language ) {

			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			$message = call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language );

			// 1.27+
			// [GlobalTitleFail] MessageCache::parse called by ...
			// Message::parseText/MessageCache::parse with no title set.
			//
			// Message::setInterfaceMessageFlag "... used to restore the flag
			// after setting a language"
			return $message->setInterfaceMessageFlag( true )->title( $GLOBALS['wgTitle'] )->parse();
		} );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgJobClasses
	 */
	private function registerJobClasses() {

		$jobClasses = array(
			'SMW\UpdateJob' => 'SMW\MediaWiki\Jobs\UpdateJob',
			'SMW\RefreshJob' => 'SMW\MediaWiki\Jobs\RefreshJob',
			'SMW\UpdateDispatcherJob' => 'SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			'SMW\ParserCachePurgeJob' => 'SMW\MediaWiki\Jobs\ParserCachePurgeJob',
			'SMW\FulltextSearchTableUpdateJob' => 'SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob',
			'SMW\EntityIdDisposerJob' => 'SMW\MediaWiki\Jobs\EntityIdDisposerJob',
			'SMW\TempChangeOpPurgeJob' => 'SMW\MediaWiki\Jobs\TempChangeOpPurgeJob',
			'SMW\PropertyStatisticsRebuildJob' => 'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			'SMW\FulltextSearchTableRebuildJob' => 'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			'SMW\ChangePropagationDispatchJob' => 'SMW\MediaWiki\Jobs\ChangePropagationDispatchJob',
			'SMW\ChangePropagationUpdateJob' => 'SMW\MediaWiki\Jobs\ChangePropagationUpdateJob',
			'SMW\ChangePropagationClassUpdateJob' => 'SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob',

			// Legacy definition to be removed with 1.10
			'SMWUpdateJob'  => 'SMW\MediaWiki\Jobs\UpdateJob',
			'SMWRefreshJob' => 'SMW\MediaWiki\Jobs\RefreshJob'
		);

		foreach ( $jobClasses as $job => $class ) {
			$this->globalVars['wgJobClasses'][$job] = $class;
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 */
	private function registerPermissions() {

		if ( !$this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		// Rights
		$this->globalVars['wgAvailableRights'][] = 'smw-admin';
		$this->globalVars['wgAvailableRights'][] = 'smw-patternedit';
		$this->globalVars['wgAvailableRights'][] = 'smw-pageedit';

		// User group rights
		if ( !isset( $this->globalVars['wgGroupPermissions']['sysop']['smw-admin'] ) ) {
			$this->globalVars['wgGroupPermissions']['sysop']['smw-admin'] = true;
		}

		if ( !isset( $this->globalVars['wgGroupPermissions']['smwcurator']['smw-patternedit'] ) ) {
			$this->globalVars['wgGroupPermissions']['smwcurator']['smw-patternedit'] = true;
		}

		if ( !isset( $this->globalVars['wgGroupPermissions']['smwcurator']['smw-pageedit'] ) ) {
			$this->globalVars['wgGroupPermissions']['smwcurator']['smw-pageedit'] = true;
		}

		if ( !isset( $this->globalVars['wgGroupPermissions']['smwadministrator']['smw-admin'] ) ) {
			$this->globalVars['wgGroupPermissions']['smwadministrator']['smw-admin'] = true;
		}

		// Add an additional protection level restricting edit/move/etc
		if ( ( $editProtectionRight = $this->applicationFactory->getSettings()->get( 'smwgEditProtectionRight' ) ) !== false ) {
			$this->globalVars['wgRestrictionLevels'][] = $editProtectionRight;
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

		if ( !$this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

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
	 * @see https://www.mediawiki.org/wiki/Manual:$wgHooks
	 *
	 * @note $wgHooks contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 */
	private function registerHooks() {

		$hookRegistry = new HookRegistry( $this->globalVars, $this->directory );
		$hookRegistry->register();

		if ( !$this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		// Old-style registration
		$this->globalVars['wgHooks']['AdminLinks'][] = 'SMWExternalHooks::addToAdminLinks';
		$this->globalVars['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWExternalHooks::onPageSchemasRegistration';
	}

}
