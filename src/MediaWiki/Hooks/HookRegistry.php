<?php

namespace SMW\MediaWiki\Hooks;

use Hooks;
use Onoi\HttpRequest\HttpRequestFactory;
use Parser;
use ParserHooks\HookRegistrant;
use SMW\ApplicationFactory;
use SMW\DeferredRequestDispatchManager;
use SMW\NamespaceManager;
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
			$httpRequestFactory->newSocketRequest()
		);

		$deferredRequestDispatchManager->setEnabledHttpDeferredJobRequestState(
			$applicationFactory->getSettings()->get( 'smwgEnabledHttpDeferredJobRequest' )
		);

		$permissionPthValidator = new PermissionPthValidator();

		/**
		 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
		 */
		$this->handlers['ParserAfterTidy'] = function ( &$parser, &$text ) {

			$parserAfterTidy = new ParserAfterTidy(
				$parser,
				$text
			);

			return $parserAfterTidy->process();
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
				$data,
				$skin
			);

			return $skinAfterContent->process();
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
		$this->handlers['InternalParseBeforeLinks'] = function ( &$parser, &$text ) {

			$internalParseBeforeLinks = new InternalParseBeforeLinks(
				$parser,
				$text
			);

			return $internalParseBeforeLinks->process();
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
		$this->handlers['ArticleDelete'] = function ( &$wikiPage, &$user, &$reason, &$error ) {

			$articleDelete = new ArticleDelete(
				$wikiPage,
				$user,
				$reason,
				$error
			);

			return $articleDelete->process();
		};

		/**
		 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
		 */
		$this->handlers['LinksUpdateConstructed'] = function ( $linksUpdate ) {

			$linksUpdateConstructed = new LinksUpdateConstructed(
				$linksUpdate
			);

			return $linksUpdateConstructed->process();
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
		 * Hook: For extensions adding their own namespaces or altering the defaults
		 *
		 * @Bug 34383
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
		 */
		$this->handlers['CanonicalNamespaces'] = function ( &$list ) {
			$list = $list + NamespaceManager::getCanonicalNames();
			return true;
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
				$title,
				$isMovable
			);

			return $titleIsMovable->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
		 */
		$this->handlers['EditPage::showEditForm:initial'] = function ( $editPage, $output = null ) use ( $applicationFactory ) {

			// 1.19 hook interface is missing the output object
			if ( !$output instanceof \OutputPage ) {
				$output = $GLOBALS['wgOut'];
			}

			$htmlFormRenderer = $applicationFactory->newMwCollaboratorFactory()->newHtmlFormRenderer(
				$editPage->getTitle(),
				$output->getLanguage()
			);

			$editPageForm = new EditPageForm(
				$editPage,
				$htmlFormRenderer
			);

			return $editPageForm->process();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/userCan
		 */
		$this->handlers['userCan'] = function ( &$title, &$user, $action, &$result ) use ( $permissionPthValidator ) {
			return $permissionPthValidator->checkUserCanPermissionFor( $title, $user, $action, $result );
		};

		$this->registerHooksForInternalUse( $applicationFactory, $deferredRequestDispatchManager );
		$this->registerParserFunctionHooks( $applicationFactory );
	}

	private function registerHooksForInternalUse( ApplicationFactory $applicationFactory, DeferredRequestDispatchManager $deferredRequestDispatchManager ) {

		$queryDependencyLinksStoreFactory = new QueryDependencyLinksStoreFactory();

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
		 */
		$this->handlers['SMW::SQLStore::AfterDataUpdateComplete'] = function ( $store, $semanticData, $compositePropertyTableDiffIterator ) use ( $applicationFactory, $queryDependencyLinksStoreFactory, $deferredRequestDispatchManager ) {

			$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
				$store
			);

			$queryDependencyLinksStore->pruneOutdatedTargetLinks(
				$compositePropertyTableDiffIterator
			);

			$entityIdListRelevanceDetectionFilter = $queryDependencyLinksStoreFactory->newEntityIdListRelevanceDetectionFilter(
				$store,
				$compositePropertyTableDiffIterator
			);

			$jobParameters = $queryDependencyLinksStore->buildParserCachePurgeJobParametersFrom(
				$entityIdListRelevanceDetectionFilter
			);

			$deferredRequestDispatchManager->dispatchParserCachePurgeJobFor(
				$semanticData->getSubject()->getTitle(),
				$jobParameters
			);

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
		 */
		$this->handlers['SMW::Store::AfterQueryResultLookupComplete'] = function ( $store, &$result ) use ( $queryDependencyLinksStoreFactory ) {

			$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
				$store
			);

			$queryDependencyLinksStore->doUpdateDependenciesBy(
				$queryDependencyLinksStoreFactory->newQueryResultDependencyListResolver( $result )
			);

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
