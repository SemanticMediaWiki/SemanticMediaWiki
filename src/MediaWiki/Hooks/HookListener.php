<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use ParserHooks\HookRegistrant;
use SMW\ApplicationFactory;
use SMW\ParserFunctions\DocumentationParserFunction;
use SMW\ParserFunctions\InfoParserFunction;
use SMW\ParserFunctions\SectionTag;
use SMW\MediaWiki\Search\SearchProfileForm;
use SMW\Site;
use SMW\Store;
use SMW\Options;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HookListener {

	/**
	 * @var array
	 */
	private $vars;

	/**
	 * @var string
	 */
	private $basePath;

	/**
	 * @since 3.0
	 *
	 * @param array &$vars
	 * @param string $basePath
	 */
	public function __construct( &$vars = [], $basePath = '' ) {
		$this->vars = $vars;
		$this->basePath = $basePath;
	}

	/**
	 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 */
	public function onParserAfterTidy( &$parser, &$text ) {

		$parserAfterTidy = new ParserAfterTidy(
			$parser
		);

		$parserAfterTidy->isCommandLineMode(
			Site::isCommandLineMode()
		);

		// #3341
		// When running as part of the install don't try to access the DB
		// or update the Store
		$parserAfterTidy->isReadOnly(
			Site::isBlocked()
		);

		$parserAfterTidy->process( $text );

		return true;
	}

	/**
	 * Hook: Called by BaseTemplate when building the toolbox array and
	 * returning it for the skin to output.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
	 */
	public function onBaseTemplateToolbox( $skinTemplate, &$toolbox ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$baseTemplateToolbox = new BaseTemplateToolbox(
			$applicationFactory->getNamespaceExaminer()
		);

		$baseTemplateToolbox->setOptions(
			[
				'smwgBrowseFeatures' => $applicationFactory->getSettings()->get( 'smwgBrowseFeatures' )
			]
		);

		$baseTemplateToolbox->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		return $baseTemplateToolbox->process( $skinTemplate, $toolbox );
	}

	/**
	 * Hook: Allows extensions to add text after the page content and article
	 * metadata.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
	 */
	public function onSkinAfterContent( &$data, $skin = null ) {

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
	public function onOutputPageParserOutput( &$outputPage, $parserOutput ) {

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
	public function onOutputPageCheckLastModified( &$lastModified ) {

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
	public function onIsFileCacheable( &$article ) {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( !$applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $article->getTitle()->getNamespace() ) ) {
			return true;
		}

		// Disallow the file cache to avoid skipping the ArticleViewHeader hook
		// on Article::tryFileCache
		return !$applicationFactory->getSettings( 'smwgEnabledQueryDependencyLinksStore' );
	}

	/**
	 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 */
	public function onBeforePageDisplay( &$outputPage, &$skin ) {

		$beforePageDisplay = new BeforePageDisplay();

		return $beforePageDisplay->process( $outputPage, $skin );
	}

	/**
	 * Hook: Called immediately before returning HTML on the search results page
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
	 */
	public function onSpecialSearchResultsPrepend( $specialSearch, $outputPage, $term ) {

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
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfiles
	 */
	public function onSpecialSearchProfiles( array &$profiles ) {

		SearchProfileForm::addProfile(
			$GLOBALS['wgSearchType'],
			$profiles
		);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm
	 */
	public function onSpecialSearchProfileForm( $specialSearch, &$form, $profile, $term, $opts ) {

		if ( $profile !== SearchProfileForm::PROFILE_NAME ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$searchProfileForm = new SearchProfileForm(
			$applicationFactory->getStore(),
			$specialSearch
		);

		$searchProfileForm->setSearchableNamespaces(
			\MediaWiki\MediaWikiServices::getInstance()->getSearchEngineConfig()->searchableNamespaces()
		);

		$searchProfileForm->getForm( $form, $opts );

		return false;
	}

	/**
	 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
	 * code after <nowiki>, HTML-comments, and templates have been treated.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
	 */
	public function onInternalParseBeforeLinks( &$parser, &$text, &$stripState ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$internalParseBeforeLinks = new InternalParseBeforeLinks(
			$parser,
			$stripState
		);

		$internalParseBeforeLinks->setOptions(
			[
				'smwgEnabledSpecialPage' => $applicationFactory->getSettings()->get( 'smwgEnabledSpecialPage' )
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
	public function onNewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user ) {

		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

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
	public function onArticleProtectComplete( &$wikiPage, &$user, $protections, $reason ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$editInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newEditInfoProvider(
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
				'smwgEditProtectionRight' => $applicationFactory->getSettings()->get( 'smwgEditProtectionRight' )
			]
		);

		$articleProtectComplete->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$articleProtectComplete->process( $protections, $reason );

		return true;
	}

	/**
	 * Hook: Occurs when an articleheader is shown
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewHeader
	 */
	public function onArticleViewHeader( &$page, &$outputDone, &$useParserCache ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$articleViewHeader = new ArticleViewHeader(
			$applicationFactory->getStore()
		);

		$articleViewHeader->setOptions(
			[
				'smwgChangePropagationProtection' => $settings->get( 'smwgChangePropagationProtection' ),
				'smwgChangePropagationWatchlist' => $settings->get( 'smwgChangePropagationWatchlist' )
			]
		);

		$articleViewHeader->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$articleViewHeader->process( $page, $outputDone, $useParserCache );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
	 */
	public function onRejectParserCacheValue( $value, $wikiPage, $popts ) {

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

		$rejectParserCacheValue = new RejectParserCacheValue(
			$queryDependencyLinksStoreFactory->newDependencyLinksUpdateJournal()
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
	public function onTitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId ) {

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
	public function onArticlePurge( &$wikiPage ) {

		$articlePurge = new ArticlePurge();

		return $articlePurge->process( $wikiPage );
	}

	/**
	 * Hook: ArticleDelete occurs whenever the software receives a request
	 * to delete an article
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
	 */
	public function onArticleDelete( &$wikiPage, &$user, &$reason, &$error ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$articleDelete = new ArticleDelete(
			$applicationFactory->getStore()
		);

		$articleDelete->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$articleDelete->setOptions(
			[
				'smwgEnabledQueryDependencyLinksStore' => $applicationFactory->getSettings()->get( 'smwgEnabledQueryDependencyLinksStore' )
			]
		);

		return $articleDelete->process( $wikiPage );
	}

	/**
	 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
	 */
	public function onLinksUpdateConstructed( $linksUpdate ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$linksUpdateConstructed = new LinksUpdateConstructed(
			$applicationFactory->getNamespaceExaminer()
		);

		$linksUpdateConstructed->setLogger(
			 $applicationFactory->getMediaWikiLogger()
		);

		// #3341
		// When running as part of the install don't try to access the DB
		// or update the Store
		$linksUpdateConstructed->isReadOnly(
			Site::isBlocked()
		);

		$linksUpdateConstructed->process( $linksUpdate );

		return true;
	}

	/**
	 * Hook: Occurs when an articleheader is shown
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentHandlerForModelID
	 */
	public function onContentHandlerForModelID( $modelId, &$contentHandler ) {

		// 'rule-json' being a legacy model, remove with 3.1
		if ( $modelId === 'rule-json' || $modelId === 'smw/schema' ) {
			$contentHandler = new \SMW\Schema\Content\ContentHandler();
		}

		return true;
	}

	/**
	 * Hook: Add extra statistic at the end of Special:Statistics
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
	 */
	public function onSpecialStatsAddExtra( &$extraStats ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$specialStatsAddExtra = new SpecialStatsAddExtra(
			$applicationFactory->getStore()
		);

		$specialStatsAddExtra->setOptions(
			[
				'smwgSemanticsEnabled' => $applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' )
			]
		);

		return $specialStatsAddExtra->process( $extraStats );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
	 */
	public function onFileUpload( $file, $reupload ) {

		$fileUpload = new FileUpload(
			ApplicationFactory::getInstance()->getNamespaceExaminer()
		);

		return $fileUpload->process( $file, $reupload );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 */
	public function onResourceLoaderGetConfigVars( &$vars ) {

		$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars();

		return $resourceLoaderGetConfigVars->process( $vars );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 */
	public function onGetPreferences( $user, &$preferences ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

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

		$getPreferences->process( $preferences);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 */
	public function onPersonalUrls( array &$personal_urls, $title, $skinTemplate ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$personalUrls = new PersonalUrls(
			$skinTemplate,
			$applicationFactory->getJobQueue()
		);

		$user = $skinTemplate->getUser();

		$personalUrls->setOptions(
			[
				'smwgJobQueueWatchlist' => $applicationFactory->getSettings()->get( 'smwgJobQueueWatchlist' ),
				'prefs-jobqueue-watchlist' => $user->getOption( 'smw-prefs-general-options-jobqueue-watchlist' )
			]
		);

		$personalUrls->process( $personal_urls );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 */
	public function onSkinTemplateNavigation( &$skinTemplate, &$links ) {

		$skinTemplateNavigation = new SkinTemplateNavigation(
			$skinTemplate,
			$links
		);

		return $skinTemplateNavigation->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 */
	public function onLoadExtensionSchemaUpdates( $databaseUpdater ) {

		$extensionSchemaUpdates = new ExtensionSchemaUpdates(
			$databaseUpdater
		);

		return $extensionSchemaUpdates->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 */
	public function onResourceLoaderTestModules( &$testModules, &$resourceLoader ) {

		$resourceLoaderTestModules = new ResourceLoaderTestModules(
			$resourceLoader,
			$this->basePath,
			$this->vars['IP']
		);

		return $resourceLoaderTestModules->process( $testModules );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
	 */
	public function onExtensionTypes( &$extTypes ) {

		$extensionTypes = new ExtensionTypes();

		return $extensionTypes->process( $extTypes);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
	 */
	public function onTitleIsAlwaysKnown( $title, &$result ) {

		$titleIsAlwaysKnown = new TitleIsAlwaysKnown(
			$title,
			$result
		);

		return $titleIsAlwaysKnown->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
	 */
	public function onArticleFromTitle( &$title, &$article ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$articleFromTitle = new ArticleFromTitle(
			$applicationFactory->getStore()
		);

		return $articleFromTitle->process( $title, $article );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
	 */
	public function onTitleIsMovable( $title, &$isMovable ) {

		$titleIsMovable = new TitleIsMovable(
			$title
		);

		return $titleIsMovable->process( $isMovable );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
	 */
	public function onBeforeDisplayNoArticleText( $article ) {

		$beforeDisplayNoArticleText = new BeforeDisplayNoArticleText(
			$article
		);

		return $beforeDisplayNoArticleText->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
	 */
	public function onEditPageShowEditFormInitial( $editPage, $output ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$user = $output->getUser();

		$editPageForm = new EditPageForm(
			$applicationFactory->getNamespaceExaminer()
		);

		$editPageForm->setOptions(
			[
				'smwgEnabledEditPageHelp' => $applicationFactory->getSettings()->get( 'smwgEnabledEditPageHelp' ),
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
	public function onTitleQuickPermissions( $title, $user, $action, &$errors, $rigor, $short ) {

		$permissionPthValidator = ApplicationFactory::getInstance()->singleton( 'PermissionPthValidator' );

		$ret = $permissionPthValidator->checkQuickPermission(
			$title,
			$user,
			$action,
			$errors
		);

		return $ret;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserOptionsRegister (Only 1.30+)
	 */
	public function onParserOptionsRegister( &$defaults, &$inCacheKey ) {

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
	public function onParserFirstCallInit( &$parser ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$parserFunctionFactory = $applicationFactory->newParserFunctionFactory();
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

		/**
		 * Support for <section> ... </section>
		 */
		SectionTag::register(
			$parser,
			$applicationFactory->getSettings()->get( 'smwgSupportSectionTag' )
		);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
	 * @provided by MW 1.4
	 *
	 * "... occurs after the request to block (or change block settings of)
	 * an IP or user has been processed ..."
	 */
	public function onBlockIpComplete( $block, $performer, $priorBlock ) {

		$userChange = new UserChange(
			ApplicationFactory::getInstance()->getNamespaceExaminer()
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
	public function onUnblockUserComplete( $block, $performer ) {

		$userChange = new UserChange(
			ApplicationFactory::getInstance()->getNamespaceExaminer()
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
	public function onUserGroupsChanged( $user ) {

		$userChange = new UserChange(
			ApplicationFactory::getInstance()->getNamespaceExaminer()
		);

		$userChange->setOrigin( 'UserGroupsChanged' );
		$userChange->process( $user->getName() );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SoftwareInfo
	 */
	public function onSoftwareInfo( &$software ) {

		$store = ApplicationFactory::getInstance()->getStore();
		$info = $store->getConnection( 'elastic' )->getSoftwareInfo();

		if ( !isset( $software[$info['component']] ) && $info['version'] !== null ) {
			$software[$info['component']] = $info['version'];
		}

		return true;
	}

}
