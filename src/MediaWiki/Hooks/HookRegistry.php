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
use SMW\Site;
use SMW\Setup;

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
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var PermissionPthValidator
	 */
	private $permissionPthValidator;

	/**
	 * @var QueryDependencyLinksStoreFactory
	 */
	private $queryDependencyLinksStoreFactory;

	/**
	 * @since 2.1
	 *
	 * @param array &$globalVars
	 * @param string $directory
	 */
	public function __construct( &$globalVars = array(), $directory = '' ) {
		$this->globalVars =& $globalVars;
		$this->basePath = $directory;
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->queryDependencyLinksStoreFactory = new QueryDependencyLinksStoreFactory();

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

	private function addCallableHandlers( $basePath, $globalVars ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory = $applicationFactory;

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

		$hooks = [
			'ParserAfterTidy' => 'newParserAfterTidy',
			'ParserOptionsRegister' => 'newParserOptionsRegister',
			'ParserFirstCallInit' => 'newParserFirstCallInit',
			'InternalParseBeforeLinks' => 'newInternalParseBeforeLinks',
			'RejectParserCacheValue' => 'newRejectParserCacheValue',
			'IsFileCacheable' => 'newIsFileCacheable',

			'BaseTemplateToolbox' => 'newBaseTemplateToolbox',
			'SkinAfterContent' => 'newSkinAfterContent',
			'OutputPageParserOutput' => 'newOutputPageParserOutput',
			'OutputPageCheckLastModified' => 'newOutputPageCheckLastModified',
			'BeforePageDisplay' => 'newBeforePageDisplay',
			'BeforeDisplayNoArticleText' => 'newBeforeDisplayNoArticleText',
			'EditPage::showEditForm:initial' => 'newEditPageShowEditFormInitial',

			'TitleMoveComplete' => 'newTitleMoveComplete',
			'TitleIsAlwaysKnown' => 'newTitleIsAlwaysKnown',
			'TitleQuickPermissions' => 'newTitleQuickPermissions',
			'TitleIsMovable' => 'newTitleIsMovable',

			'ArticlePurge' => 'newArticlePurge',
			'ArticleDelete' => 'newArticleDelete',
			'ArticleFromTitle' => 'newArticleFromTitle',
			'ArticleProtectComplete' => 'newArticleProtectComplete',
			'ArticleViewHeader' => 'newArticleViewHeader',

			'NewRevisionFromEditComplete' => 'newNewRevisionFromEditComplete',
			'LinksUpdateConstructed' => 'newLinksUpdateConstructed',
			'FileUpload' => 'newFileUpload',

			'ResourceLoaderGetConfigVars' => 'newResourceLoaderGetConfigVars',
			'ResourceLoaderTestModules' => 'newResourceLoaderTestModules',
			'GetPreferences' => 'newGetPreferences',
			'PersonalUrls' => 'newPersonalUrls',
			'SkinTemplateNavigation' => 'newSkinTemplateNavigation',
			'LoadExtensionSchemaUpdates' => 'newLoadExtensionSchemaUpdates',

			'ExtensionTypes' => 'newExtensionTypes',
			'SpecialSearchResultsPrepend' => 'newSpecialSearchResultsPrepend',
			'SpecialStatsAddExtra' => 'newSpecialStatsAddExtra',

			'BlockIpComplete' => 'newBlockIpComplete',
			'UnblockUserComplete' => 'newUnblockUserComplete',
			'UserGroupsChanged' => 'newUserGroupsChanged',
		];

		foreach ( $hooks as $hook => $handler ) {
			$this->handlers[$hook] = [ $this, $handler ];
		}

		$this->registerHooksForInternalUse( $applicationFactory, $deferredRequestDispatchManager );
	}

	/**
	 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 */
	public function newParserAfterTidy( &$parser, &$text ) {

		$parserAfterTidy = new ParserAfterTidy(
			$parser
		);

		$parserAfterTidy->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$parserAfterTidy->isReadOnly(
			Site::isReadOnly()
		);

		return $parserAfterTidy->process( $text );
	}

	/**
	 * Hook: Called by BaseTemplate when building the toolbox array and
	 * returning it for the skin to output.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
	 */
	public function newBaseTemplateToolbox( $skinTemplate, &$toolbox ) {

		$baseTemplateToolbox = new BaseTemplateToolbox(
			$this->applicationFactory->getNamespaceExaminer()
		);

		$baseTemplateToolbox->setOptions(
			[
				'smwgBrowseFeatures' => $this->applicationFactory->getSettings()->get( 'smwgBrowseFeatures' )
			]
		);

		$baseTemplateToolbox->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		return $baseTemplateToolbox->process( $skinTemplate, $toolbox );
	}

	/**
	 * Hook: Allows extensions to add text after the page content and article
	 * metadata.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
	 */
	public function newSkinAfterContent( &$data, $skin = null ) {

		$skinAfterContent = new SkinAfterContent(
			$skin
		);

		return $skinAfterContent->performUpdate( $data );
	}

	/**
	 * Hook: Called after parse, before the HTML is added to the output
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 */
	public function newOutputPageParserOutput( &$outputPage, $parserOutput ) {

		$outputPageParserOutput = new OutputPageParserOutput(
			$outputPage,
			$parserOutput
		);

		return $outputPageParserOutput->process();
	}

	/**
	 * Hook: When checking if the page has been modified since the last visit
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageCheckLastModified
	 */
	public function newOutputPageCheckLastModified( &$lastModified ) {

		// Required to ensure that ViewAction doesn't bail out with
		// "ViewAction::show: done 304" and hereby neglects to run the
		// ArticleViewHeader hook

		// Required on 1.28- for the $outputPage->checkLastModified check
		// that would otherwise prevent running the ArticleViewHeader hook
		$lastModified['smw'] = wfTimestamp( TS_MW, time() );

		return true;
	}

	/**
	 * Hook: Allow an extension to disable file caching on pages
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/IsFileCacheable
	 */
	public function newIsFileCacheable( &$article ) {

		if ( !$this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $article->getTitle()->getNamespace() ) ) {
			return true;
		}

		// Disallow the file cache to avoid skipping the ArticleViewHeader hook
		// on Article::tryFileCache
		return !$this->applicationFactory->getSettings( 'smwgEnabledQueryDependencyLinksStore' );
	}

	/**
	 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 */
	public function newBeforePageDisplay( &$outputPage, &$skin ) {

		$beforePageDisplay = new BeforePageDisplay();

		return $beforePageDisplay->process( $outputPage, $skin );
	}

	/**
	 * Hook: Called immediately before returning HTML on the search results page
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
	 */
	public function newSpecialSearchResultsPrepend( $specialSearch, $outputPage, $term ) {

		$user = $outputPage->getUser();

		$specialSearchResultsPrepend = new SpecialSearchResultsPrepend(
			$specialSearch,
			$outputPage
		);

		$specialSearchResultsPrepend->setOptions(
			[
				'prefs-suggester-textinput' => $user->getOption( 'smw-prefs-general-options-suggester-textinput' ),
				'prefs-disable-search-info' => $user->getOption( 'smw-prefs-general-options-disable-search-info' )
			]
		);

		return $specialSearchResultsPrepend->process( $term );
	}

	/**
	 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
	 * code after <nowiki>, HTML-comments, and templates have been treated.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
	 */
	public function newInternalParseBeforeLinks( &$parser, &$text, &$stripState ) {

		$internalParseBeforeLinks = new InternalParseBeforeLinks(
			$parser,
			$stripState
		);

		$internalParseBeforeLinks->setOptions(
			[
				'smwgEnabledSpecialPage' => $this->applicationFactory->getSettings()->get( 'smwgEnabledSpecialPage' )
			]
		);

		return $internalParseBeforeLinks->process( $text );
	}

	/**
	 * Hook: NewRevisionFromEditComplete called when a revision was inserted
	 * due to an edit
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
	 */
	public function newNewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user ) {

		$mwCollaboratorFactory = $this->applicationFactory->newMwCollaboratorFactory();

		$editInfoProvider = $mwCollaboratorFactory->newEditInfoProvider(
			$wikiPage,
			$revision,
			$user
		);

		$pageInfoProvider = $mwCollaboratorFactory->newPageInfoProvider(
			$wikiPage,
			$revision,
			$user
		);

		$newRevisionFromEditComplete = new NewRevisionFromEditComplete(
			$wikiPage->getTitle(),
			$editInfoProvider,
			$pageInfoProvider
		);

		return $newRevisionFromEditComplete->process();
	}

	/**
	 * Hook: Occurs after the protect article request has been processed
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public function newArticleProtectComplete( &$wikiPage, &$user, $protections, $reason ) {

		$editInfoProvider = $this->applicationFactory->newMwCollaboratorFactory()->newEditInfoProvider(
			$wikiPage,
			$wikiPage->getRevision(),
			$user
		);

		$articleProtectComplete = new ArticleProtectComplete(
			$wikiPage->getTitle(),
			$editInfoProvider
		);

		$articleProtectComplete->setOptions(
			[
				'smwgEditProtectionRight' => $this->applicationFactory->getSettings()->get( 'smwgEditProtectionRight' )
			]
		);

		$articleProtectComplete->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		$articleProtectComplete->process( $protections, $reason );

		return true;
	}

	/**
	 * Hook: Occurs when an articleheader is shown
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewHeader
	 */
	public function newArticleViewHeader( &$page, &$outputDone, &$useParserCache ) {

		$settings = $this->applicationFactory->getSettings();

		$articleViewHeader = new ArticleViewHeader(
			$this->applicationFactory->getStore()
		);

		$articleViewHeader->setOptions(
			[
				'smwgChangePropagationProtection' => $settings->get( 'smwgChangePropagationProtection' ),
				'smwgChangePropagationWatchlist' => $settings->get( 'smwgChangePropagationWatchlist' )
			]
		);

		$articleViewHeader->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		$articleViewHeader->process( $page, $outputDone, $useParserCache );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
	 */
	public function newRejectParserCacheValue( $value, $wikiPage, $popts ) {

		$rejectParserCacheValue = new RejectParserCacheValue(
			$this->queryDependencyLinksStoreFactory->newDependencyLinksUpdateJournal()
		);

		// Return false to reject the parser cache
		// The log will contain something like "[ParserCache] ParserOutput
		// key valid, but rejected by RejectParserCacheValue hook handler."
		return $rejectParserCacheValue->process( $wikiPage->getTitle() );
	}

	/**
	 * Hook: TitleMoveComplete occurs whenever a request to move an article
	 * is completed
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 */
	public function newTitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId ) {

		$titleMoveComplete = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			$user,
			$oldId,
			$newId
		);

		return $titleMoveComplete->process();
	}

	/**
	 * Hook: ArticlePurge executes before running "&action=purge"
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
	 */
	public function newArticlePurge( &$wikiPage ) {

		$articlePurge = new ArticlePurge();

		return $articlePurge->process( $wikiPage );
	}

	/**
	 * Hook: ArticleDelete occurs whenever the software receives a request
	 * to delete an article
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
	 */
	public function newArticleDelete( &$wikiPage, &$user, &$reason, &$error ) {

		$articleDelete = new ArticleDelete(
			$this->applicationFactory->getStore()
		);

		$articleDelete->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		return $articleDelete->process( $wikiPage );
	}

	/**
	 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
	 */
	public function newLinksUpdateConstructed( $linksUpdate ) {

		$linksUpdateConstructed = new LinksUpdateConstructed();

		$linksUpdateConstructed->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		return $linksUpdateConstructed->process( $linksUpdate );
	}

	/**
	 * Hook: Add extra statistic at the end of Special:Statistics
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
	 */
	public function newSpecialStatsAddExtra( &$extraStats ) {

		$specialStatsAddExtra = new SpecialStatsAddExtra(
			$this->applicationFactory->getStore()
		);

		$specialStatsAddExtra->setOptions(
			[
				'smwgSemanticsEnabled' => $this->applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' )
			]
		);

		return $specialStatsAddExtra->process( $extraStats );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
	 */
	public function newFileUpload( $file, $reupload ) {

		$fileUpload = new FileUpload(
			$this->applicationFactory->getNamespaceExaminer()
		);

		return $fileUpload->process( $file, $reupload );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 */
	public function newResourceLoaderGetConfigVars( &$vars ) {

		$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars();

		return $resourceLoaderGetConfigVars->process( $vars );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 */
	public function newGetPreferences( $user, &$preferences ) {

		$settings = $this->applicationFactory->getSettings();

		$getPreferences = new GetPreferences(
			$user
		);

		$getPreferences->setOptions(
			[
				'smwgEnabledEditPageHelp' => $settings->get( 'smwgEnabledEditPageHelp' ),
				'wgSearchType' => $GLOBALS['wgSearchType'],
				'smwgJobQueueWatchlist' => $settings->get( 'smwgJobQueueWatchlist' )
			]
		);

		return $getPreferences->process( $preferences);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 */
	public function newPersonalUrls( array &$personal_urls, $title, $skinTemplate ) {

		$personalUrls = new PersonalUrls(
			$skinTemplate,
			$this->applicationFactory->getJobQueue()
		);

		$user = $skinTemplate->getUser();

		$personalUrls->setOptions(
			[
				'smwgJobQueueWatchlist' => $this->applicationFactory->getSettings()->get( 'smwgJobQueueWatchlist' ),
				'prefs-jobqueue-watchlist' => $user->getOption( 'smw-prefs-general-options-jobqueue-watchlist' )
			]
		);

		$personalUrls->process( $personal_urls );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 */
	public function newSkinTemplateNavigation( &$skinTemplate, &$links ) {

		$skinTemplateNavigation = new SkinTemplateNavigation(
			$skinTemplate,
			$links
		);

		return $skinTemplateNavigation->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 */
	public function newLoadExtensionSchemaUpdates( $databaseUpdater ) {

		$extensionSchemaUpdates = new ExtensionSchemaUpdates(
			$databaseUpdater
		);

		return $extensionSchemaUpdates->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 */
	public function newResourceLoaderTestModules( &$testModules, &$resourceLoader ) {

		$resourceLoaderTestModules = new ResourceLoaderTestModules(
			$resourceLoader,
			$this->basePath,
			$this->globalVars['IP']
		);

		return $resourceLoaderTestModules->process( $testModules );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
	 */
	public function newExtensionTypes( &$extTypes ) {

		$extensionTypes = new ExtensionTypes();

		return $extensionTypes->process( $extTypes);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
	 */
	public function newTitleIsAlwaysKnown( $title, &$result ) {

		$titleIsAlwaysKnown = new TitleIsAlwaysKnown(
			$title,
			$result
		);

		return $titleIsAlwaysKnown->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
	 */
	public function newArticleFromTitle( &$title, &$article ) {

		$articleFromTitle = new ArticleFromTitle(
			$this->applicationFactory->getStore()
		);

		return $articleFromTitle->process( $title, $article );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
	 */
	public function newTitleIsMovable( $title, &$isMovable ) {

		$titleIsMovable = new TitleIsMovable(
			$title
		);

		return $titleIsMovable->process( $isMovable );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
	 */
	public function newBeforeDisplayNoArticleText( $article ) {

		$beforeDisplayNoArticleText = new BeforeDisplayNoArticleText(
			$article
		);

		return $beforeDisplayNoArticleText->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
	 */
	public function newEditPageShowEditFormInitial( $editPage, $output ) {

		$user = $output->getUser();

		$editPageForm = new EditPageForm(
			$this->applicationFactory->getNamespaceExaminer()
		);

		$editPageForm->setOptions(
			[
				'smwgEnabledEditPageHelp' => $this->applicationFactory->getSettings()->get( 'smwgEnabledEditPageHelp' ),
				'prefs-disable-editpage' => $user->getOption( 'smw-prefs-general-options-disable-editpage-info' )
			]
		);

		return $editPageForm->process( $editPage );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
	 *
	 * "...Quick permissions are checked first in the Title::checkQuickPermissions
	 * function. Quick permissions are the most basic of permissions needed
	 * to perform an action ..."
	 */
	public function newTitleQuickPermissions( $title, $user, $action, &$errors, $rigor, $short ) {

		if ( $this->permissionPthValidator === null ) {
			$this->permissionPthValidator = new PermissionPthValidator(
				$this->applicationFactory->singleton( 'ProtectionValidator' )
			);
		}

		return $this->permissionPthValidator->checkQuickPermission( $title, $user, $action, $errors );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserOptionsRegister (Only 1.30+)
	 */
	public function newParserOptionsRegister( &$defaults, &$inCacheKey ) {

		// #2509
		// Register a new options key, used in connection with #ask/#show
		// where the use of a localTime invalidates the ParserCache to avoid
		// stalled settings for users with different preferences
		$defaults['localTime'] = false;
		$inCacheKey['localTime'] = true;

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 */
	public function newParserFirstCallInit( &$parser ) {

		$parserFunctionFactory = $this->applicationFactory->newParserFunctionFactory();
		$parserFunctionFactory->registerFunctionHandlers( $parser );

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
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
	 * @provided by MW 1.4
	 *
	 * "... occurs after the request to block (or change block settings of)
	 * an IP or user has been processed ..."
	 */
	public function newBlockIpComplete( $block, $performer, $priorBlock ) {

		$userChange = new UserChange(
			$this->applicationFactory->getNamespaceExaminer()
		);

		$userChange->setOrigin( 'BlockIpComplete' );
		$userChange->process( $block->getTarget() );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
	 * @provided by MW 1.29
	 *
	 * "... occurs after the request to unblock an IP or user has been
	 * processed ..."
	 */
	public function newUnblockUserComplete( $block, $performer ) {

		$userChange = new UserChange(
			$this->applicationFactory->getNamespaceExaminer()
		);

		$userChange->setOrigin( 'UnblockUserComplete' );
		$userChange->process( $block->getTarget() );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 * @provided by MW 1.26
	 *
	 * "... called after user groups are changed ..."
	 */
	public function newUserGroupsChanged( $user ) {

		$userChange = new UserChange(
			$this->applicationFactory->getNamespaceExaminer()
		);

		$userChange->setOrigin( 'UserGroupsChanged' );
		$userChange->process( $user->getName() );

		return true;
	}

	private function registerHooksForInternalUse( ApplicationFactory $applicationFactory, DeferredRequestDispatchManager $deferredRequestDispatchManager ) {

		$queryDependencyLinksStore = $this->queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$applicationFactory->getStore()
		);

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
		 */
		$this->handlers['SMW::SQLStore::AfterDataUpdateComplete'] = function ( $store, $semanticData, $changeOp ) use ( $queryDependencyLinksStore, $deferredRequestDispatchManager ) {

			$queryDependencyLinksStore->setStore( $store );
			$subject = $semanticData->getSubject();

			$queryDependencyLinksStore->pruneOutdatedTargetLinks(
				$subject,
				$changeOp
			);

			$entityIdListRelevanceDetectionFilter = $this->queryDependencyLinksStoreFactory->newEntityIdListRelevanceDetectionFilter(
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

			$textByChangeUpdater = $fulltextSearchTableFactory->newTextByChangeUpdater(
				$store
			);

			$textByChangeUpdater->pushUpdates(
				$changeOp,
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
			$queryDependencyLinksStore->updateDependencies( $result );

			$applicationFactory->singleton( 'CachedQueryResultPrefetcher' )->recordStats();

			$store->getObjectIds()->warmUpCache( $result );

			return true;
		};

		/**
		 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::AfterIncomingPropertiesLookupComplete
		 */
		$this->handlers['SMW::Browse::AfterIncomingPropertiesLookupComplete'] = function ( $store, $semanticData, $requestOptions ) {

			$queryReferenceBacklinks = $this->queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
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

			$queryReferenceBacklinks = $this->queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
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
