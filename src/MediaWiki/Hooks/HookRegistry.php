<?php

namespace SMW\MediaWiki\Hooks;

use Hooks;
use Onoi\HttpRequest\HttpRequestFactory;
use Parser;
use ParserHooks\HookRegistrant;
use SMW\ApplicationFactory;
use SMW\DeferredRequestDispatchManager;
use SMW\NamespaceManager;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\ParserFunctions\DocumentationParserFunction;
use SMW\ParserFunctions\InfoParserFunction;
use SMW\PermissionPthValidator;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;

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
	 * @since 2.1
	 *
	 * @param array &$globalVars
	 * @param string $directory
	 */
	public function __construct( &$globalVars = array(), $directory = '' ) {
		$this->globalVars =& $globalVars;

		$this->addCallbackHandlers( $directory, $globalVars );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	public function isRegistered( $name ) {
	//	return Hooks::isRegistered( $name );
		return isset( $this->handlers[$name] );
	}

	/**
	 * @since 2.3
	 */
	public function clear() {
		foreach ( $this->getHandlerList() as $name ) {
			Hooks::clear( $name );
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
			//Hooks::register( $name, $callback );
			$this->globalVars['wgHooks'][$name][] = $callback;
		}
	}

	private function addCallbackHandlers( $basePath, $globalVars ) {

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
			$GLOBALS['wgCommandLineMode']
		);

		$permissionPthValidator = new PermissionPthValidator(
			$applicationFactory->singleton( 'EditProtectionValidator' )
		);

		$permissionPthValidator->setEditProtectionRight(
			$applicationFactory->getSettings()->get( 'smwgEditProtectionRight' )
		);

		/**
		 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
		 */
		$this->handlers['ParserAfterTidy'] = function ( &$parser, &$text ) {

			// MediaWiki\Services\ServiceDisabledException from line 340 of
			// ...\ServiceContainer.php: Service disabled: DBLoadBalancer
			try {
				$isReadOnly = wfReadOnly();
			} catch( \MediaWiki\Services\ServiceDisabledException $e ) {
				$isReadOnly = true;
			}

			$parserAfterTidy = new ParserAfterTidy(
				$parser
			);

			$parserAfterTidy->isCommandLineMode(
				$GLOBALS['wgCommandLineMode']
			);

			$parserAfterTidy->isReadOnly(
				$isReadOnly
			);

			return $parserAfterTidy->process( $text );
		};

		/**
		 * Hook: Called by BaseTemplate when building the toolbox array and
		 * returning it for the skin to output.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
		 */
		$this->handlers['BaseTemplateToolbox'] = function ( $skinTemplate, &$toolbox ) {

			$baseTemplateToolbox = new BaseTemplateToolbox(
				$skinTemplate,
				$toolbox
			);

			return $baseTemplateToolbox->process();
		};

		/**
		 * Hook: Allows extensions to add text after the page content and article
		 * metadata.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
		 */
		$this->handlers['SkinAfterContent'] = function ( &$data, $skin = null ) {

			$skinAfterContent = new SkinAfterContent(
				$skin
			);

			return $skinAfterContent->performUpdate( $data );
		};

		/**
		 * Hook: Called after parse, before the HTML is added to the output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
		 */
		$this->handlers['OutputPageParserOutput'] = function ( &$outputPage, $parserOutput ) {

			$outputPageParserOutput = new OutputPageParserOutput(
				$outputPage,
				$parserOutput
			);

			return $outputPageParserOutput->process();
		};

		/**
		 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
		 */
		$this->handlers['BeforePageDisplay'] = function ( &$outputPage, &$skin ) {

			$beforePageDisplay = new BeforePageDisplay(
				$outputPage,
				$skin
			);

			return $beforePageDisplay->process();
		};

		/**
		 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
		 * code after <nowiki>, HTML-comments, and templates have been treated.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
		 */
		$this->handlers['InternalParseBeforeLinks'] = function ( &$parser, &$text ) use ( $applicationFactory ) {

			$internalParseBeforeLinks = new InternalParseBeforeLinks(
				$parser
			);

			$internalParseBeforeLinks->setEnabledSpecialPage(
				$applicationFactory->getSettings()->get( 'smwgEnabledSpecialPage' )
			);

			return $internalParseBeforeLinks->process( $text );
		};

		/**
		 * Hook: NewRevisionFromEditComplete called when a revision was inserted
		 * due to an edit
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
		 */
		$this->handlers['NewRevisionFromEditComplete'] = function ( $wikiPage, $revision, $baseId, $user ) {

			$newRevisionFromEditComplete = new NewRevisionFromEditComplete(
				$wikiPage,
				$revision,
				$baseId,
				$user
			);

			return $newRevisionFromEditComplete->process();
		};

		/**
		 * Hook: Occurs after the protect article request has been processed
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
		 */
		$this->handlers['ArticleProtectComplete'] = function ( &$wikiPage, &$user, $protections, $reason ) use ( $applicationFactory ) {

			$editInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newEditInfoProvider(
				$wikiPage,
				$wikiPage->getRevision(),
				$user
			);

			$articleProtectComplete = new ArticleProtectComplete(
				$wikiPage->getTitle(),
				$editInfoProvider
			);

			$articleProtectComplete->setEditProtectionRight(
				$applicationFactory->getSettings()->get( 'smwgEditProtectionRight' )
			);

			$articleProtectComplete->setLogger(
				$applicationFactory->getMediaWikiLogger()
			);

			$articleProtectComplete->process( $protections, $reason );

			return true;
		};

		/**
		 * Hook: TitleMoveComplete occurs whenever a request to move an article
		 * is completed
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
		 */
		$this->handlers['TitleMoveComplete'] = function ( $oldTitle, $newTitle, $user, $oldId, $newId ) {

			$titleMoveComplete = new TitleMoveComplete(
				$oldTitle,
				$newTitle,
				$user,
				$oldId,
				$newId
			);

			return $titleMoveComplete->process();
		};

		/**
		 * Hook: ArticlePurge executes before running "&action=purge"
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
		 */
		$this->handlers['ArticlePurge']= function ( &$wikiPage ) {

			$articlePurge = new ArticlePurge();

			return $articlePurge->process( $wikiPage );
		};

		/**
		 * Hook: ArticleDelete occurs whenever the software receives a request
		 * to delete an article
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
		 */
		$this->handlers['ArticleDelete'] = function ( &$wikiPage, &$user, &$reason, &$error ) use( $applicationFactory ) {

			$articleDelete = new ArticleDelete();

			$articleDelete->setLogger(
				$applicationFactory->getMediaWikiLogger()
			);

			return $articleDelete->process( $wikiPage );
		};

		/**
		 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
		 */
		$this->handlers['LinksUpdateConstructed'] = function ( $linksUpdate ) use( $applicationFactory ) {

			$linksUpdateConstructed = new LinksUpdateConstructed();

			$linksUpdateConstructed->setLogger(
				$applicationFactory->getMediaWikiLogger()
			);

			return $linksUpdateConstructed->process( $linksUpdate );
		};

		/**
		 * Hook: Add extra statistic at the end of Special:Statistics
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
		 */
		$this->handlers['SpecialStatsAddExtra'] = function ( &$extraStats ) use( $globalVars ) {

			$specialStatsAddExtra = new SpecialStatsAddExtra(
				$extraStats,
				$globalVars['wgVersion'],
				$globalVars['wgLang']
			);

			return $specialStatsAddExtra->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
		 *
		 * @since 1.9.1
		 */
		$this->handlers['FileUpload'] = function ( $file, $reupload ) {

			$fileUpload = new FileUpload(
				$file,
				$reupload
			);

			return $fileUpload->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
		 */
		$this->handlers['ResourceLoaderGetConfigVars'] = function ( &$vars ) {

			$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars(
				$vars
			);

			return $resourceLoaderGetConfigVars->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
		 */
		$this->handlers['GetPreferences'] = function ( $user, &$preferences ) {

			$getPreferences = new GetPreferences(
				$user,
				$preferences
			);

			return $getPreferences->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
		 */
		$this->handlers['SkinTemplateNavigation'] = function ( &$skinTemplate, &$links ) {

			$skinTemplateNavigation = new SkinTemplateNavigation(
				$skinTemplate,
				$links
			);

			return $skinTemplateNavigation->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
		 */
		$this->handlers['LoadExtensionSchemaUpdates'] = function ( $databaseUpdater ) {

			$extensionSchemaUpdates = new ExtensionSchemaUpdates(
				$databaseUpdater
			);

			return $extensionSchemaUpdates->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
		 */
		$this->handlers['ResourceLoaderTestModules'] = function ( &$testModules, &$resourceLoader ) use ( $basePath, $globalVars ) {

			$resourceLoaderTestModules = new ResourceLoaderTestModules(
				$resourceLoader,
				$testModules,
				$basePath,
				$globalVars['IP']
			);

			return $resourceLoaderTestModules->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
		 */
		$this->handlers['ExtensionTypes'] = function ( &$extTypes ) {

			$extensionTypes = new ExtensionTypes(
				$extTypes
			);

			return $extensionTypes->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
		 */
		$this->handlers['TitleIsAlwaysKnown'] = function ( $title, &$result ) {

			$titleIsAlwaysKnown = new TitleIsAlwaysKnown(
				$title,
				$result
			);

			return $titleIsAlwaysKnown->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
		 */
		$this->handlers['BeforeDisplayNoArticleText'] = function ( $article ) {

			$beforeDisplayNoArticleText = new BeforeDisplayNoArticleText(
				$article
			);

			return $beforeDisplayNoArticleText->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
		 */
		$this->handlers['ArticleFromTitle'] = function ( &$title, &$article ) {

			$articleFromTitle = new ArticleFromTitle(
				$title,
				$article
			);

			return $articleFromTitle->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
		 */
		$this->handlers['TitleIsMovable'] = function ( $title, &$isMovable ) {

			$titleIsMovable = new TitleIsMovable(
				$title
			);

			return $titleIsMovable->process( $isMovable );
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
		 */
		$this->handlers['EditPage::showEditForm:initial'] = function ( $editPage, $output = null ) use ( $applicationFactory ) {

			$editPageForm = new EditPageForm(
				$applicationFactory->getNamespaceExaminer()
			);

			$editPageForm->isEnabledEditPageHelp(
				$applicationFactory->getSettings()->get( 'smwgEnabledEditPageHelp' )
			);

			return $editPageForm->process( $editPage );
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
		 *
		 * "...Quick permissions are checked first in the Title::checkQuickPermissions
		 * function. Quick permissions are the most basic of permissions needed
		 * to perform an action ..."
		 */
		$this->handlers['TitleQuickPermissions'] = function ( $title, $user, $action, &$errors, $rigor, $short ) use ( $permissionPthValidator ) {
			return $permissionPthValidator->checkQuickPermissionOn( $title, $user, $action, $errors );
		};

		$this->registerHooksForInternalUse( $applicationFactory, $deferredRequestDispatchManager );
		$this->registerParserFunctionHooks( $applicationFactory );
	}

	private function registerHooksForInternalUse( ApplicationFactory $applicationFactory, DeferredRequestDispatchManager $deferredRequestDispatchManager ) {

		$queryDependencyLinksStoreFactory = new QueryDependencyLinksStoreFactory();

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$applicationFactory->getStore()
		);

		$tempChangeOpStore = $applicationFactory->singleton( 'TempChangeOpStore' );

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
		 */
		$this->handlers['SMW::SQLStore::AfterDataUpdateComplete'] = function ( $store, $semanticData, $compositePropertyTableDiffIterator ) use ( $queryDependencyLinksStoreFactory, $queryDependencyLinksStore, $deferredRequestDispatchManager, $tempChangeOpStore ) {

			$queryDependencyLinksStore->setStore( $store );
			$subject = $semanticData->getSubject();

			$queryDependencyLinksStore->pruneOutdatedTargetLinks(
				$subject,
				$compositePropertyTableDiffIterator
			);

			$entityIdListRelevanceDetectionFilter = $queryDependencyLinksStoreFactory->newEntityIdListRelevanceDetectionFilter(
				$store,
				$compositePropertyTableDiffIterator
			);

			$jobParameters = $queryDependencyLinksStore->buildParserCachePurgeJobParametersFrom(
				$entityIdListRelevanceDetectionFilter
			);

			$deferredRequestDispatchManager->dispatchParserCachePurgeJobWith(
				$subject->getTitle(),
				$jobParameters
			);

			$fulltextSearchTableFactory = new FulltextSearchTableFactory();

			$textByChangeUpdater = $fulltextSearchTableFactory->newTextByChangeUpdater(
				$store
			);

			$textByChangeUpdater->pushUpdates(
				$compositePropertyTableDiffIterator,
				$deferredRequestDispatchManager
			);

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::BeforeQueryResultLookupComplete
		 */
		$this->handlers['SMW::Store::BeforeQueryResultLookupComplete'] = function ( $store, $query, &$result, $queryEngine ) use ( $applicationFactory ) {

			$cachedQueryResultPrefetcher = $applicationFactory->singleton( 'CachedQueryResultPrefetcher' );

			$cachedQueryResultPrefetcher->setQueryEngine(
				$queryEngine
			);

			if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
				return true;
			}

			$result = $cachedQueryResultPrefetcher->getQueryResult( $query );

			return false;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
		 */
		$this->handlers['SMW::Store::AfterQueryResultLookupComplete'] = function ( $store, &$result ) use ( $queryDependencyLinksStore, $applicationFactory ) {

			$queryDependencyLinksStore->setStore( $store );
			$queryDependencyLinksStore->doUpdateDependenciesFrom( $result );

			$applicationFactory->singleton( 'CachedQueryResultPrefetcher' )->recordStats();

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::AfterIncomingPropertiesLookupComplete
		 */
		$this->handlers['SMW::Browse::AfterIncomingPropertiesLookupComplete'] = function ( $store, $semanticData, $requestOptions ) use ( $queryDependencyLinksStoreFactory ) {

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
		$this->handlers['SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate'] = function ( $property, $subject, &$html ) use ( $queryDependencyLinksStoreFactory, $applicationFactory ) {

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
		$this->handlers['SMW::SQLStore::Installer::AfterCreateTablesComplete'] = function ( $tableBuilder, $messageReporter ) use ( $applicationFactory ) {

			$importerServiceFactory = $applicationFactory->create( 'ImporterServiceFactory' );

			$importer = $importerServiceFactory->newImporter(
				$importerServiceFactory->newJsonContentIterator( $applicationFactory->getSettings()->get( 'smwgImportFileDir' ) )
			);

			$importer->setMessageReporter( $messageReporter );
			$importer->doImport();

			return true;
		};
	}

	private function registerParserFunctionHooks( ApplicationFactory $applicationFactory ) {

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
		 */
		$this->handlers['ParserFirstCallInit'] = function ( &$parser ) use( $applicationFactory ) {

			$parserFunctionFactory = $applicationFactory->newParserFunctionFactory( $parser );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newAskParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newShowParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newSubobjectParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newRecurringEventsParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newSetParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newConceptParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

			list( $name, $definition, $flag ) = $parserFunctionFactory->newDeclareParserFunctionDefinition();

			$parser->setFunctionHook( $name, $definition, $flag );

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
