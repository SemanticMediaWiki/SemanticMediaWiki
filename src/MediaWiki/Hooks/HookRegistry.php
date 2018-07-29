<?php

namespace SMW\MediaWiki\Hooks;

use Onoi\HttpRequest\HttpRequestFactory;
use Parser;
use SMW\ApplicationFactory;
use SMW\DeferredRequestDispatchManager;
use SMW\MediaWiki\Search\SearchProfileForm;
use SMW\NamespaceManager;
use SMW\Setup;
use SMW\Site;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HookRegistry {

	/**
	 * @var array
	 */
	private $handlers = array();

	/**
	 * @var array
	 */
	private $globalVars;

	/**
	 * @var string
	 */
	private $basePath;

	/**
	 * @since 2.1
	 *
	 * @param array &$globalVars
	 * @param string $directory
	 */
	public function __construct( &$globalVars = array(), $directory = '' ) {
		$this->globalVars =& $globalVars;
		$this->basePath = $directory;
		$this->addCallableHandlers( $directory, $globalVars );
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$vars
	 */
	public static function initExtension( array &$vars ) {

		$vars['wgContentHandlers'][CONTENT_MODEL_RULE] = 'SMW\Rule\RuleContentHandler';

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
		 */
		$vars['wgHooks']['CanonicalNamespaces'][] = function( array &$namespaces ) {

			NamespaceManager::initCanonicalNamespaces(
				$namespaces
			);

			return true;
		};

		/**
		 * To add to or remove pages from the special page list. This array has
		 * the same structure as $wgSpecialPages.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialPage_initList
		 *
		 * #2813
		 */
		$vars['wgHooks']['SpecialPage_initList'][] = function( array &$specialPages ) {

			Setup::initSpecialPageList(
				$specialPages
			);

			return true;
		};

		/**
		 * Called when ApiMain has finished initializing its module manager. Can
		 * be used to conditionally register API modules.
		 *
		 * #2813
		 */
		$vars['wgHooks']['ApiMain::moduleManager'][] = function( $apiModuleManager ) {

			$apiModuleManager->addModules(
				Setup::getAPIModules(),
				'action'
			);

			return true;
		};
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	public function isRegistered( $name ) {
		// return \Hooks::isRegistered( $name );
		return isset( $this->handlers[$name] );
	}

	/**
	 * @since 2.3
	 */
	public function clear() {
		foreach ( $this->getHandlerList() as $name ) {
			\Hooks::clear( $name );
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 *
	 * @return Callable|false
	 */
	public function getHandlerFor( $name ) {
		return isset( $this->handlers[$name] ) ? $this->handlers[$name] : false;
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getHandlerList() {
		return array_keys( $this->handlers );
	}

	/**
	 * @since 2.1
	 */
	public function register() {
		foreach ( $this->handlers as $name => $callback ) {
			//\Hooks::register( $name, $callback );
			$this->globalVars['wgHooks'][$name][] = $callback;
		}
	}

	private function addCallableHandlers( $basePath, $globalVars ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$httpRequestFactory = new HttpRequestFactory();

		$deferredRequestDispatchManager = new DeferredRequestDispatchManager(
			$httpRequestFactory->newSocketRequest(),
			$applicationFactory->newJobFactory()
		);

		$deferredRequestDispatchManager->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$deferredRequestDispatchManager->isEnabledHttpDeferredRequest(
			$applicationFactory->getSettings()->get( 'smwgEnabledHttpDeferredJobRequest' )
		);

		// SQLite has no lock manager making table lock contention very common
		// hence use the JobQueue to enqueue any change request and avoid
		// a rollback due to canceled DB transactions
		$deferredRequestDispatchManager->isPreferredWithJobQueue(
			$GLOBALS['wgDBtype'] === 'sqlite'
		);

		// When in commandLine mode avoid deferred execution and run a process
		// within the same transaction
		$deferredRequestDispatchManager->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$hookListener = new HookListener( $this->globalVars, $this->basePath );

		$hooks = [
			'ParserAfterTidy' => [ $hookListener, 'onParserAfterTidy' ],
			'ParserOptionsRegister' => [ $hookListener, 'onParserOptionsRegister' ],
			'ParserFirstCallInit' => [ $hookListener, 'onParserFirstCallInit' ],
			'InternalParseBeforeLinks' => [ $hookListener, 'onInternalParseBeforeLinks' ],
			'RejectParserCacheValue' => [ $hookListener, 'onRejectParserCacheValue' ],
			'IsFileCacheable' => [ $hookListener, 'onIsFileCacheable' ],

			'BaseTemplateToolbox' => [ $hookListener, 'onBaseTemplateToolbox' ],
			'SkinAfterContent' => [ $hookListener, 'onSkinAfterContent' ],
			'OutputPageParserOutput' => [ $hookListener, 'onOutputPageParserOutput' ],
			'OutputPageCheckLastModified' => [ $hookListener, 'onOutputPageCheckLastModified' ],
			'BeforePageDisplay' => [ $hookListener, 'onBeforePageDisplay' ],
			'BeforeDisplayNoArticleText' => [ $hookListener, 'onBeforeDisplayNoArticleText' ],
			'EditPage::showEditForm:initial' => [ $hookListener, 'onEditPageShowEditFormInitial' ],

			'TitleMoveComplete' => [ $hookListener, 'onTitleMoveComplete' ],
			'TitleIsAlwaysKnown' => [ $hookListener, 'onTitleIsAlwaysKnown' ],
			'TitleQuickPermissions' => [ $hookListener, 'onTitleQuickPermissions' ],
			'TitleIsMovable' => [ $hookListener, 'onTitleIsMovable' ],

			'ArticlePurge' => [ $hookListener, 'onArticlePurge' ],
			'ArticleDelete' => [ $hookListener, 'onArticleDelete' ],
			'ArticleFromTitle' => [ $hookListener, 'onArticleFromTitle' ],
			'ArticleProtectComplete' => [ $hookListener, 'onArticleProtectComplete' ],
			'ArticleViewHeader' => [ $hookListener, 'onArticleViewHeader' ],

			'NewRevisionFromEditComplete' => [ $hookListener, 'onNewRevisionFromEditComplete' ],
			'LinksUpdateConstructed' => [ $hookListener, 'onLinksUpdateConstructed' ],
			'FileUpload' => [ $hookListener, 'onFileUpload' ],

			'ResourceLoaderGetConfigVars' => [ $hookListener, 'onResourceLoaderGetConfigVars' ],
			'ResourceLoaderTestModules' => [ $hookListener, 'onResourceLoaderTestModules' ],
			'GetPreferences' => [ $hookListener, 'onGetPreferences' ],
			'PersonalUrls' => [ $hookListener, 'onPersonalUrls' ],
			'SkinTemplateNavigation' => [ $hookListener, 'onSkinTemplateNavigation' ],
			'LoadExtensionSchemaUpdates' => [ $hookListener, 'onLoadExtensionSchemaUpdates' ],

			'ExtensionTypes' => [ $hookListener, 'onExtensionTypes' ],
			'SpecialStatsAddExtra' => [ $hookListener, 'onSpecialStatsAddExtra' ],
			'SpecialSearchResultsPrepend' => [ $hookListener, 'onSpecialSearchResultsPrepend' ],
			'SpecialSearchProfileForm' => [ $hookListener, 'onSpecialSearchProfileForm' ],
			'SpecialSearchProfiles' => [ $hookListener, 'onSpecialSearchProfiles' ],
			'SoftwareInfo' => [ $hookListener, 'onSoftwareInfo' ],

			'BlockIpComplete' => [ $hookListener, 'onBlockIpComplete' ],
			'UnblockUserComplete' => [ $hookListener, 'onUnblockUserComplete' ],
			'UserGroupsChanged' => [ $hookListener, 'onUserGroupsChanged' ],
		];

		foreach ( $hooks as $hook => $handler ) {
			$this->handlers[$hook] = is_callable( $handler ) ? $handler : [ $this, $handler ];
		}

		$this->registerHooksForInternalUse( $applicationFactory, $deferredRequestDispatchManager );
	}

	private function registerHooksForInternalUse( ApplicationFactory $applicationFactory, DeferredRequestDispatchManager $deferredRequestDispatchManager ) {

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$applicationFactory->getStore()
		);

		$elasticFactory = new \SMW\Elastic\ElasticFactory();
		$indexer = $elasticFactory->newIndexer( $applicationFactory->getStore() );

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::EntityReferenceCleanUpComplete
		 */
		$this->handlers['SMW::SQLStore::EntityReferenceCleanUpComplete'] = function ( $store, $id, $subject, $isRedirect ) use ( $indexer ) {

			$indexer->setOrigin( 'EntityReferenceCleanUpComplete' );
			$indexer->delete( [ $id ] );

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Admin::TaskHandlerFactory
		 */
		$this->handlers['SMW::Admin::TaskHandlerFactory'] = function ( &$taskHandlers, $store, $outputFormatter, $user ) use ( $elasticFactory ) {

			$taskHandlers[] = $elasticFactory->newInfoTaskHandler(
				$store,
				$outputFormatter
			);

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
		 */
		$this->handlers['SMW::SQLStore::AfterDataUpdateComplete'] = function ( $store, $semanticData, $changeOp ) use ( $queryDependencyLinksStore, $deferredRequestDispatchManager ) {

			$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

			$queryDependencyLinksStore->setStore( $store );
			$subject = $semanticData->getSubject();

			$queryDependencyLinksStore->pruneOutdatedTargetLinks(
				$subject,
				$changeOp
			);

			$entityIdListRelevanceDetectionFilter = $queryDependencyLinksStoreFactory->newEntityIdListRelevanceDetectionFilter(
				$store,
				$changeOp
			);

			$jobParameters = $queryDependencyLinksStore->buildParserCachePurgeJobParametersFrom(
				$entityIdListRelevanceDetectionFilter
			);

			$deferredRequestDispatchManager->dispatchParserCachePurgeJobWith(
				$subject->getTitle(),
				$jobParameters
			);

			$fulltextSearchTableFactory = new FulltextSearchTableFactory();

			$textChangeUpdater = $fulltextSearchTableFactory->newTextChangeUpdater(
				$store
			);

			$textChangeUpdater->pushUpdates(
				$changeOp,
				$deferredRequestDispatchManager
			);

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::BeforeQueryResultLookupComplete
		 */
		$this->handlers['SMW::Store::BeforeQueryResultLookupComplete'] = function ( $store, $query, &$result, $queryEngine ) use ( $applicationFactory ) {

			if ( $applicationFactory->getSettings()->get( 'smwgQueryResultCacheType' ) === false ) {
				return true;
			}

			$cachedQueryResultPrefetcher = $applicationFactory->singleton(
				'CachedQueryResultPrefetcher'
			);

			$cachedQueryResultPrefetcher->setQueryEngine(
				$queryEngine
			);

			if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
				return true;
			}

			$result = $cachedQueryResultPrefetcher->getQueryResult(
				$query
			);

			return false;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
		 */
		$this->handlers['SMW::Store::AfterQueryResultLookupComplete'] = function ( $store, &$result ) use ( $queryDependencyLinksStore, $applicationFactory ) {

			$queryDependencyLinksStore->setStore( $store );
			$queryDependencyLinksStore->updateDependencies( $result );

			$applicationFactory->singleton( 'CachedQueryResultPrefetcher' )->recordStats();

			$store->getObjectIds()->warmUpCache( $result );

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::AfterIncomingPropertiesLookupComplete
		 */
		$this->handlers['SMW::Browse::AfterIncomingPropertiesLookupComplete'] = function ( $store, $semanticData, $requestOptions ) {

			$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

			$queryReferenceBacklinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
				$store
			);

			$queryReferenceBacklinks->addReferenceLinksTo(
				$semanticData,
				$requestOptions
			);

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::BeforeIncomingPropertyValuesFurtherLinkCreate
		 */
		$this->handlers['SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate'] = function ( $property, $subject, &$html ) use ( $applicationFactory ) {

			$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

			$queryReferenceBacklinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
				$applicationFactory->getStore()
			);

			$doesRequireFurtherLink = $queryReferenceBacklinks->doesRequireFurtherLink(
				$property,
				$subject,
				$html
			);

			// Return false in order to stop the link creation process to replace the
			// standard link
			return $doesRequireFurtherLink;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
		 */
		$this->handlers['SMW::SQLStore::Installer::AfterCreateTablesComplete'] = function ( $tableBuilder, $messageReporter, $options ) use ( $applicationFactory ) {

			$importerServiceFactory = $applicationFactory->create( 'ImporterServiceFactory' );

			$importer = $importerServiceFactory->newImporter(
				$importerServiceFactory->newJsonContentIterator(
					$applicationFactory->getSettings()->get( 'smwgImportFileDirs' )
				)
			);

			$importer->isEnabled( $options->safeGet( \SMW\SQLStore\Installer::OPT_IMPORT, false ) );
			$importer->setMessageReporter( $messageReporter );
			$importer->doImport();

			return true;
		};
	}

}
