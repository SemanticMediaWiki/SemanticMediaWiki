<?php

namespace SMW;

use Hooks;
use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Hooks\HookRegistry;
use SMW\SQLStore\Installer;

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
	 * @since 1.9
	 *
	 * @param ApplicationFactory $applicationFactory
	 */
	public function __construct( ApplicationFactory $applicationFactory ) {
		$this->applicationFactory = $applicationFactory;
	}

	/**
	 * Runs at the earliest possible event to initialize functions or hooks that
	 * are otherwise too late for the hook system to be recognized.
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

		$specials = [
			'Ask' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialAsk'
			],
			'Browse' => [
				'page' =>  'SMW\MediaWiki\Specials\SpecialBrowse'
			],
			'PageProperty' => [
				'page' =>  'SMW\MediaWiki\Specials\SpecialPageProperty'
			],
			'SearchByProperty' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialSearchByProperty'
			],
			'ProcessingErrorList' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialProcessingErrorList'
			],
			'PropertyLabelSimilarity' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity'
			],
			'SMWAdmin' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialAdmin'
			],
			'Concepts' => [
				'page' => 'SMW\SpecialConcepts'
			],
			'ExportRDF' => [
				'page' => 'SMWSpecialOWLExport'
			],
			'Types' => [
				'page' => 'SMWSpecialTypes'
			],
			'URIResolver' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialURIResolver'
			],
			'Properties' => [
				'page' => 'SMW\SpecialProperties'
			],
			'UnusedProperties' => [
				'page' => 'SMW\SpecialUnusedProperties'
			],
			'WantedProperties' => [
				'page' => 'SMW\SpecialWantedProperties'
			],
			'DeferredRequestDispatcher' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher'
			],
		];

		// Register data
		foreach ( $specials as $special => $page ) {
			$specialPages[$special] = $page['page'];
		}
	}

	/**
	 * @since 3.0
	 */
	public static function isEnabled() {
		return defined( 'SMW_VERSION' ) && $GLOBALS['smwgSemanticsEnabled'];
	}

	/**
	 * @since 3.0
	 */
	public static function isValid( $isCli = false ) {
		return Installer::isGoodSchema( $isCli );
	}

	/**
	 * @since 1.9
	 *
	 * @param array &$vars
	 * @param string $directory
	 */
	public function init( &$vars, $directory ) {

		if ( $this->isValid() === false ) {

			$text = 'Semantic MediaWiki was installed and enabled but is missing an appropriate ';
			$text .= '<a href="https://www.semantic-mediawiki.org/wiki/Help:Upgrade">upgrade key</a>. ';
			$text .= 'Please run MediaWiki\'s <a href="https://www.mediawiki.org/wiki/Manual:Update.php">update.php</a> ';
			$text .= 'or Semantic MediaWiki\'s <a href="https://www.semantic-mediawiki.org/wiki/Help:SetupStore.php">setupStore.php</a> maintenance script first. ';
			$text .= 'You may also consult the following pages:';
			$text .= '<ul><li><a href="https://www.semantic-mediawiki.org/wiki/Help:Installation">Installation</a></li>';
			$text .= '<li><a href="https://www.semantic-mediawiki.org/wiki/Help:Installation/Troubleshooting">Troubleshooting</a></li></ul>';

			smwfAbort( $text );
		}

		$this->addDefaultConfigurations( $vars );

		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::disableSemantics();
		}

		$this->initConnectionProviders( );
		$this->initMessageCallbackHandler();

		$this->registerJobClasses( $vars );
		$this->registerPermissions( $vars );

		$this->registerParamDefinitions( $vars );
		$this->registerFooterIcon( $vars, $directory );
		$this->registerHooks( $vars, $directory );

		Hooks::run( 'SMW::Setup::AfterInitializationComplete', [ &$vars ] );
	}

	private function addDefaultConfigurations( &$vars ) {

		$vars['wgLogTypes'][] = 'smw';
		$vars['wgFilterLogTypes']['smw'] = true;

		$vars['smwgMasterStore'] = null;
		$vars['smwgIQRunningNumber'] = 0;

		if ( !isset( $vars['smwgNamespace'] ) ) {
			$vars['smwgNamespace'] = parse_url( $vars['wgServer'], PHP_URL_HOST );
		}

		foreach ( $vars['smwgResourceLoaderDefFiles'] as $key => $file ) {
			if ( is_readable( $file ) ) {
				$vars['wgResourceModules'] = array_merge( $vars['wgResourceModules'], include ( $file ) );
			}
		}
	}

	private function initConnectionProviders() {

		$mwCollaboratorFactory = $this->applicationFactory->newMwCollaboratorFactory();
		$connectionManager = $this->applicationFactory->getConnectionManager();

		$connectionManager->registerConnectionProvider(
			DB_MASTER,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_MASTER )
		);

		$connectionManager->registerConnectionProvider(
			DB_SLAVE,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_SLAVE )
		);

		$connectionManager->registerConnectionProvider(
			'mw.db',
			$mwCollaboratorFactory->newConnectionProvider( 'mw.db' )
		);

		// Connection can be used to redirect queries to another DB cluster
		$connectionManager->registerConnectionProvider(
			'mw.db.queryengine',
			$mwCollaboratorFactory->newConnectionProvider( 'mw.db.queryengine' )
		);

		$connectionManager->registerConnectionProvider(
			'elastic',
			$this->applicationFactory->singleton( 'ElasticFactory' )->newConnectionProvider()
		);
	}

	private function initMessageCallbackHandler() {

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
	private function registerJobClasses( &$vars ) {

		$jobClasses = [

			'smw.update' => 'SMW\MediaWiki\Jobs\UpdateJob',
			'smw.refresh' => 'SMW\MediaWiki\Jobs\RefreshJob',
			'smw.updateDispatcher' => 'SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			'smw.parserCachePurge' => 'SMW\MediaWiki\Jobs\ParserCachePurgeJob',
			'smw.fulltextSearchTableUpdate' => 'SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob',
			'smw.entityIdDisposer' => 'SMW\MediaWiki\Jobs\EntityIdDisposerJob',
			'smw.propertyStatisticsRebuild' => 'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			'smw.fulltextSearchTableRebuild' => 'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			'smw.changePropagationDispatch' => 'SMW\MediaWiki\Jobs\ChangePropagationDispatchJob',
			'smw.changePropagationUpdate' => 'SMW\MediaWiki\Jobs\ChangePropagationUpdateJob',
			'smw.changePropagationClassUpdate' => 'SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob',
			'smw.elasticIndexerRecovery' => 'SMW\Elastic\Indexer\IndexerRecoveryJob',
			'smw.elasticFileIngest' => 'SMW\Elastic\Indexer\FileIngestJob',

			// Legacy 3.0-
			'SMW\UpdateJob' => 'SMW\MediaWiki\Jobs\UpdateJob',
			'SMW\RefreshJob' => 'SMW\MediaWiki\Jobs\RefreshJob',
			'SMW\UpdateDispatcherJob' => 'SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			'SMW\ParserCachePurgeJob' => 'SMW\MediaWiki\Jobs\ParserCachePurgeJob',
			'SMW\FulltextSearchTableUpdateJob' => 'SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob',
			'SMW\EntityIdDisposerJob' => 'SMW\MediaWiki\Jobs\EntityIdDisposerJob',
			'SMW\PropertyStatisticsRebuildJob' => 'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			'SMW\FulltextSearchTableRebuildJob' => 'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			'SMW\ChangePropagationDispatchJob' => 'SMW\MediaWiki\Jobs\ChangePropagationDispatchJob',
			'SMW\ChangePropagationUpdateJob' => 'SMW\MediaWiki\Jobs\ChangePropagationUpdateJob',
			'SMW\ChangePropagationClassUpdateJob' => 'SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob',

			// Legacy 2.0-
			'SMWUpdateJob'  => 'SMW\MediaWiki\Jobs\UpdateJob',
			'SMWRefreshJob' => 'SMW\MediaWiki\Jobs\RefreshJob'
		];

		foreach ( $jobClasses as $job => $class ) {
			$vars['wgJobClasses'][$job] = $class;
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 */
	private function registerPermissions( &$vars ) {

		if ( !$this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		$rights = [
			'smw-admin' => [
				'sysop',
				'smwadministrator'
			],
			'smw-patternedit' => [
				'smwcurator'
			],
			'smw-schemaedit' => [
				'smwcurator'
			],
			'smw-pageedit' => [
				'smwcurator'
			],
		//	'smw-watchlist' => [
		//		'smwcurator'
		//	],
		];

		foreach ( $rights as $right => $roles ) {

			// Rights
			$vars['wgAvailableRights'][] = $right;

			// User group rights
			foreach ( $roles as $role ) {
				if ( !isset( $vars['wgGroupPermissions'][$role][$right] ) ) {
					$vars['wgGroupPermissions'][$role][$right] = true;
				}
			}
		}

		// Add an additional protection level restricting edit/move/etc
		if ( ( $editProtectionRight = $this->applicationFactory->getSettings()->get( 'smwgEditProtectionRight' ) ) !== false ) {
			$vars['wgRestrictionLevels'][] = $editProtectionRight;
		}
	}

	private function registerParamDefinitions( &$vars ) {
		$vars['wgParamDefinitions']['smwformat'] = [
			'definition'=> 'SMWParamFormat',
		];
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgFooterIcons
	 */
	private function registerFooterIcon( &$vars, $path ) {

		if ( !$this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		if( isset( $vars['wgFooterIcons']['poweredby']['semanticmediawiki'] ) ) {
			return;
		}

		$src = '';

		if ( is_file( $path . '/res/DataURI.php' ) && ( $dataURI = include $path . '/res/DataURI.php' ) !== [] ) {
			$src = $dataURI['footer'];
		}

		$vars['wgFooterIcons']['poweredby']['semanticmediawiki'] = [
			'src' => $src,
			'url' => 'https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
			'alt' => 'Powered by Semantic MediaWiki'
		];
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgHooks
	 *
	 * @note $wgHooks contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 */
	private function registerHooks( &$vars, $directory ) {

		$hookRegistry = new HookRegistry( $vars, $directory );
		$hookRegistry->register();

		if ( !$this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		// Old-style registration
		$vars['wgHooks']['AdminLinks'][] = 'SMWExternalHooks::addToAdminLinks';
		$vars['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWExternalHooks::onPageSchemasRegistration';
	}

}
