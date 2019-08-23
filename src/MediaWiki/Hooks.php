<?php

namespace SMW\MediaWiki;

use Onoi\HttpRequest\HttpRequestFactory;
use Parser;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\NamespaceManager;
use SMW\SemanticData;
use SMW\Setup;
use SMW\Site;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use ParserHooks\HookRegistrant;
use SMW\DataTypeRegistry;
use SMW\ParserFunctions\DocumentationParserFunction;
use SMW\ParserFunctions\InfoParserFunction;
use SMW\ParserFunctions\SectionTag;
use SMW\SetupFile;
use SMW\Store;
use SMW\Options;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\MediaWiki\Hooks\ArticleFromTitle;
use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\MediaWiki\Hooks\ArticleViewHeader;
use SMW\MediaWiki\Hooks\BaseTemplateToolbox;
use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;
use SMW\MediaWiki\Hooks\ExtensionTypes;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\MediaWiki\Hooks\LinksUpdateConstructed;
use SMW\MediaWiki\Hooks\NewRevisionFromEditComplete;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars;
use SMW\MediaWiki\Hooks\ResourceLoaderTestModules;
use SMW\MediaWiki\Hooks\SkinAfterContent;
use SMW\MediaWiki\Hooks\SkinTemplateNavigation;
use SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\MediaWiki\Hooks\TitleIsAlwaysKnown;
use SMW\MediaWiki\Hooks\TitleIsMovable;
use SMW\MediaWiki\Hooks\TitleMoveComplete;
use SMW\MediaWiki\Hooks\TitleQuickPermissions;
use SMW\MediaWiki\Hooks\UserChange;
use SMW\MediaWiki\Hooks\AdminLinks;
use SMW\MediaWiki\Hooks\SpecialPageList;
use SMW\MediaWiki\Hooks\ApiModuleManager;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class Hooks {

	/**
	 * @var array
	 */
	private $handlers = [];

	/**
	 * @var string
	 */
	private $localDirectory;

	/**
	 * @since 2.1
	 *
	 * @param string $localDirectory
	 */
	public function __construct( $localDirectory = '' ) {
		$this->localDirectory = $localDirectory;
		$this->registerHandlers();
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
	public function register( &$vars ) {
		foreach ( $this->handlers as $name => $callback ) {
			//\Hooks::register( $name, $callback );
			$vars['wgHooks'][$name][] = $callback;
		}
	}

	/**
	 * Allow to show a message on `Special:Version` when it is clear that the
	 * extension was loaded but not enabled.
	 *
	 * @since 3.1
	 *
	 * @param array &$vars
	 */
	public static function registerExtensionCheck( array &$vars ) {

		$vars['wgHooks']['BeforePageDisplay']['smw-extension-check'] = function( $outputPage ) {

			$beforePageDisplay = new BeforePageDisplay();

			$beforePageDisplay->setOptions(
				[
					'SMW_EXTENSION_LOADED' => defined( 'SMW_EXTENSION_LOADED' )
				]
			);

			$beforePageDisplay->informAboutExtensionAvailability( $outputPage );

			return true;
		};
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$vars
	 */
	public static function registerEarly( array &$vars ) {

		// Remove the hook registered via `Hook::registerExtensionCheck` given
		// that at this point we know the extension was loaded and hereby is
		// available.
		if ( defined( 'SMW_EXTENSION_LOADED' ) ) {
			unset( $vars['wgHooks']['BeforePageDisplay']['smw-extension-check'] );
		}

		$vars['wgContentHandlers'][CONTENT_MODEL_SMW_SCHEMA] = 'SMW\MediaWiki\Content\SchemaContentHandler';

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

			$applicationFactory = ApplicationFactory::getInstance();
			$settings = $applicationFactory->getSettings();

			$specialPageList = new SpecialPageList();
			$specialPageList->setOptions(
				[
					'smwgSemanticsEnabled' => $settings->get( 'smwgSemanticsEnabled' )
				]
			);

			$specialPageList->process( $specialPages );

			return true;
		};

		/**
		 * Called when ApiMain has finished initializing its module manager. Can
		 * be used to conditionally register API modules.
		 *
		 * #2813
		 */
		$vars['wgHooks']['ApiMain::moduleManager'][] = function( $moduleManager ) {

			$applicationFactory = ApplicationFactory::getInstance();
			$settings = $applicationFactory->getSettings();

			$apiModuleManager = new ApiModuleManager();
			$apiModuleManager->setOptions(
				[
					'smwgSemanticsEnabled' => $settings->get( 'smwgSemanticsEnabled' )
				]
			);

			$apiModuleManager->process( $moduleManager );

			return true;
		};
	}

	private function registerHandlers() {

		$elasticFactory = ApplicationFactory::getInstance()->singleton( 'ElasticFactory' );

		$this->handlers = [
			'ParserAfterTidy' => [ $this, 'onParserAfterTidy' ],
			'ParserOptionsRegister' => [ $this, 'onParserOptionsRegister' ],
			'ParserFirstCallInit' => [ $this, 'onParserFirstCallInit' ],
			'InternalParseBeforeLinks' => [ $this, 'onInternalParseBeforeLinks' ],
			'RejectParserCacheValue' => [ $this, 'onRejectParserCacheValue' ],

			'BaseTemplateToolbox' => [ $this, 'onBaseTemplateToolbox' ],
			'SkinAfterContent' => [ $this, 'onSkinAfterContent' ],
			'OutputPageParserOutput' => [ $this, 'onOutputPageParserOutput' ],
			'OutputPageCheckLastModified' => [ $this, 'onOutputPageCheckLastModified' ],
			'BeforePageDisplay' => [ $this, 'onBeforePageDisplay' ],
			'BeforeDisplayNoArticleText' => [ $this, 'onBeforeDisplayNoArticleText' ],
			'EditPage::showEditForm:initial' => [ $this, 'onEditPageShowEditFormInitial' ],

			'TitleMoveComplete' => [ $this, 'onTitleMoveComplete' ],
			'TitleIsAlwaysKnown' => [ $this, 'onTitleIsAlwaysKnown' ],
			'TitleQuickPermissions' => [ $this, 'onTitleQuickPermissions' ],
			'TitleIsMovable' => [ $this, 'onTitleIsMovable' ],

			'ArticlePurge' => [ $this, 'onArticlePurge' ],
			'ArticleDelete' => [ $this, 'onArticleDelete' ],
			'ArticleFromTitle' => [ $this, 'onArticleFromTitle' ],
			'ArticleProtectComplete' => [ $this, 'onArticleProtectComplete' ],
			'ArticleViewHeader' => [ $this, 'onArticleViewHeader' ],
			'ContentHandlerForModelID' => [ $this, 'onContentHandlerForModelID' ],

			'NewRevisionFromEditComplete' => [ $this, 'onNewRevisionFromEditComplete' ],
			'LinksUpdateConstructed' => [ $this, 'onLinksUpdateConstructed' ],
			'FileUpload' => [ $this, 'onFileUpload' ],
			'MaintenanceUpdateAddParams' => [ $this, 'onMaintenanceUpdateAddParams' ],

			'ResourceLoaderGetConfigVars' => [ $this, 'onResourceLoaderGetConfigVars' ],
			'ResourceLoaderTestModules' => [ $this, 'onResourceLoaderTestModules' ],
			'GetPreferences' => [ $this, 'onGetPreferences' ],
			'PersonalUrls' => [ $this, 'onPersonalUrls' ],
			'SkinTemplateNavigation' => [ $this, 'onSkinTemplateNavigation' ],
			'LoadExtensionSchemaUpdates' => [ $this, 'onLoadExtensionSchemaUpdates' ],

			'ExtensionTypes' => [ $this, 'onExtensionTypes' ],
			'SpecialStatsAddExtra' => [ $this, 'onSpecialStatsAddExtra' ],
			'SpecialSearchResultsPrepend' => [ $this, 'onSpecialSearchResultsPrepend' ],
			'SpecialSearchProfileForm' => [ $this, 'onSpecialSearchProfileForm' ],
			'SpecialSearchProfiles' => [ $this, 'onSpecialSearchProfiles' ],
			'SoftwareInfo' => [ $this, 'onSoftwareInfo' ],

			'BlockIpComplete' => [ $this, 'onBlockIpComplete' ],
			'UnblockUserComplete' => [ $this, 'onUnblockUserComplete' ],
			'UserGroupsChanged' => [ $this, 'onUserGroupsChanged' ],

			'SMW::SQLStore::AfterDataUpdateComplete' => [ $this, 'onAfterDataUpdateComplete'],
			'SMW::SQLStore::Installer::AfterCreateTablesComplete' => [ $this, 'onAfterCreateTablesComplete' ],

			'SMW::Store::BeforeQueryResultLookupComplete' => [ $this, 'onBeforeQueryResultLookupComplete'],
			'SMW::Store::AfterQueryResultLookupComplete'  => [ $this, 'onAfterQueryResultLookupComplete'],

			'SMW::Browse::AfterIncomingPropertiesLookupComplete' => [ $this, 'onAfterIncomingPropertiesLookupComplete' ],
			'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate' => [ $this, 'onBeforeIncomingPropertyValuesFurtherLinkCreate' ],

			'SMW::SQLStore::EntityReferenceCleanUpComplete' => [ $elasticFactory, 'onEntityReferenceCleanUpComplete' ],
			'SMW::Admin::TaskHandlerFactory' => [ $elasticFactory, 'onTaskHandlerFactory' ],
			'SMW::Api::AddTasks' => [ $elasticFactory, 'onApiTasks' ],
			'SMW::Event::RegisterEventListeners' => [ $elasticFactory, 'onRegisterEventListeners' ],
			'SMW::Maintenance::AfterUpdateEntityCollationComplete' => [ $elasticFactory, 'onAfterUpdateEntityCollationComplete' ],

			'AdminLinks' => [ $this, 'onAdminLinks' ],
			'PageSchemasRegisterHandlers' => [ $this, 'onPageSchemasRegisterHandlers' ]
		];
	}

	/**
	 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 */
	public function onParserAfterTidy( &$parser, &$text ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$parserAfterTidy = new ParserAfterTidy(
			$parser,
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getCache()
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

		$parserAfterTidy->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$parserAfterTidy->setOptions(
			[
				'smwgCheckForRemnantEntities' => $settings->get( 'smwgCheckForRemnantEntities' )
			]
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

		$applicationFactory = ApplicationFactory::getInstance();

		$outputPageParserOutput = new OutputPageParserOutput(
			$applicationFactory->getNamespaceExaminer()
		);

		$outputPageParserOutput->setIndicatorRegistry(
			$applicationFactory->create( 'IndicatorRegistry' )
		);

		$outputPageParserOutput->process( $outputPage, $parserOutput );

		return true;
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
	 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 */
	public function onBeforePageDisplay( &$outputPage, &$skin ) {

		$beforePageDisplay = new BeforePageDisplay();

		$beforePageDisplay->setOptions(
			[
				'incomplete_tasks' => SetupFile::findIncompleteTasks( $GLOBALS )
			]
		);

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

		$applicationFactory = ApplicationFactory::getInstance();
		$searchEngineConfig = $applicationFactory->singleton( 'SearchEngineConfig' );

		$options = [
			'default_namespaces' => $searchEngineConfig->defaultNamespaces()
		];

		ProfileForm::addProfile(
			Site::searchType(),
			$profiles,
			$options
		);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm
	 */
	public function onSpecialSearchProfileForm( $specialSearch, &$form, $profile, $term, $opts ) {

		if ( !ProfileForm::isValidProfile( $profile ) ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$searchEngineConfig = $applicationFactory->singleton( 'SearchEngineConfig' );

		$profileForm = new ProfileForm(
			$applicationFactory->getStore(),
			$specialSearch
		);

		$profileForm->setSearchableNamespaces(
			$searchEngineConfig->searchableNamespaces()
		);

		$profileForm->buildForm( $form, $opts );

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

		$applicationFactory = ApplicationFactory::getInstance();
		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

		$editInfo = $mwCollaboratorFactory->newEditInfo(
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
			$editInfo,
			$pageInfoProvider
		);

		$newRevisionFromEditComplete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
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

		$editInfo = $applicationFactory->newMwCollaboratorFactory()->newEditInfo(
			$wikiPage,
			$wikiPage->getRevision(),
			$user
		);

		$articleProtectComplete = new ArticleProtectComplete(
			$wikiPage->getTitle(),
			$editInfo
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

		// Get the key to distinguish between an anon and logged-in user stored
		// parser cache
		$parserCache = $applicationFactory->create( 'ParserCache' );

		$dependencyValidator = $applicationFactory->create( 'DependencyValidator' );

		$dependencyValidator->setETag(
			$parserCache->getETag( $page, $page->makeParserOptions( 'canonical' ) )
		);

		$dependencyValidator->setCacheTTL(
			Site::getCacheExpireTime( 'parser' )
		);

		$dependencyValidator->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$settings = $applicationFactory->getSettings();

		$articleViewHeader = new ArticleViewHeader(
			$applicationFactory->getStore(),
			$dependencyValidator
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
	public function onRejectParserCacheValue( $value, $page, $popts ) {

		$applicationFactory = ApplicationFactory::getInstance();

		// Get the key to distinguish between an anon and logged-in user stored
		// parser cache
		$parserCache = $applicationFactory->create( 'ParserCache' );

		$dependencyValidator = $applicationFactory->create( 'DependencyValidator' );

		$dependencyValidator->setETag(
			$parserCache->getETag( $page, $popts )
		);

		$dependencyValidator->setCacheTTL(
			Site::getCacheExpireTime( 'parser' )
		);

		$dependencyValidator->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$rejectParserCacheValue = new RejectParserCacheValue(
			$applicationFactory->getNamespaceExaminer(),
			$dependencyValidator
		);

		$rejectParserCacheValue->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		// Return false to reject the parser cache
		// The log will contain something like "[ParserCache] ParserOutput
		// key valid, but rejected by RejectParserCacheValue hook handler."
		return $rejectParserCacheValue->process( $page );
	}

	/**
	 * Hook: TitleMoveComplete occurs whenever a request to move an article
	 * is completed
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 */
	public function onTitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$titleMoveComplete = new TitleMoveComplete(
			$applicationFactory->getNamespaceExaminer()
		);

		$titleMoveComplete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$titleMoveComplete->process( $oldTitle, $newTitle, $user, $oldId, $newId );

		return true;
	}

	/**
	 * Hook: ArticlePurge executes before running "&action=purge"
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
	 */
	public function onArticlePurge( &$wikiPage ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$articlePurge = new ArticlePurge();

		$articlePurge->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

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

		$articleDelete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
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
			$contentHandler = new \SMW\MediaWiki\Content\SchemaContentHandler();
		}

		return true;
	}

	/**
	 * Hook: Add extra statistic at the end of Special:Statistics
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
	 */
	public function onSpecialStatsAddExtra( &$extraStats, $context ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$context->getOutput()->addModules( 'smw.tippy' );

		$specialStatsAddExtra = new SpecialStatsAddExtra(
			$applicationFactory->getStore()
		);

		$specialStatsAddExtra->setLanguage(
			$context->getLanguage()
		);

		$specialStatsAddExtra->setDataTypeLabels(
			DataTypeRegistry::getInstance()->getKnownTypeLabels()
		);

		$specialStatsAddExtra->setOptions(
			[
				'smwgSemanticsEnabled' => $applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' )
			]
		);

		$specialStatsAddExtra->process( $extraStats );

		return true;
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
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/MaintenanceUpdateAddParams
	 */
	public function onMaintenanceUpdateAddParams( &$params ) {

		ExtensionSchemaUpdates::addMaintenanceUpdateParams(
			$params
		);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 */
	public function onResourceLoaderGetConfigVars( &$vars ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = ApplicationFactory::getInstance()->getSettings();

		$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars(
			$applicationFactory->singleton( 'NamespaceInfo' )
		);

		$resourceLoaderGetConfigVars->setOptions(
			$settings->filter( ResourceLoaderGetConfigVars::OPTION_KEYS )
		);

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
			$this->localDirectory
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

		$applicationFactory = ApplicationFactory::getInstance();

		$titleQuickPermissions = new TitleQuickPermissions(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->singleton( 'PermissionManager' )
		);

		return $titleQuickPermissions->process( $title, $user, $action, $errors );
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

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
	 */
	public function onAfterDataUpdateComplete( $store, $semanticData, $changeOp ) {

		// A delete infused change should trigger an immediate update
		// without having to wait on the job queue
		$isPrimaryUpdate = $semanticData->getOption( SemanticData::PROC_DELETE, false );

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$store
		);

		$queryDependencyLinksStore->pruneOutdatedTargetLinks(
			$changeOp
		);

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$textChangeUpdater = $fulltextSearchTableFactory->newTextChangeUpdater(
			$store
		);

		$textChangeUpdater->isPrimary( $isPrimaryUpdate );

		$textChangeUpdater->pushUpdates(
			$changeOp
		);

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::BeforeQueryResultLookupComplete
	 */
	public function onBeforeQueryResultLookupComplete ( $store, $query, &$result, $queryEngine ) {

		$resultCache = ApplicationFactory::getInstance()->singleton( 'ResultCache' );

		$resultCache->setQueryEngine(
			$queryEngine
		);

		if ( !$resultCache->isEnabled() ) {
			return true;
		}

		$result = $resultCache->getQueryResult(
			$query
		);

		return false;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
	 */
	public function onAfterQueryResultLookupComplete ( $store, &$result ) {

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$store
		);

		$queryDependencyLinksStore->updateDependencies( $result );

		ApplicationFactory::getInstance()->singleton( 'ResultCache' )->recordStats();

		$store->getObjectIds()->warmUpCache( $result );

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::AfterIncomingPropertiesLookupComplete
	 */
	public function onAfterIncomingPropertiesLookupComplete ( $store, $semanticData, $requestOptions ) {

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryReferenceBacklinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
			$store
		);

		$queryReferenceBacklinks->addReferenceLinksTo(
			$semanticData,
			$requestOptions
		);

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::BeforeIncomingPropertyValuesFurtherLinkCreate
	 */
	public function onBeforeIncomingPropertyValuesFurtherLinkCreate ( $property, $subject, &$html, $store ) {

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryReferenceBacklinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
			$store
		);

		$doesRequireFurtherLink = $queryReferenceBacklinks->doesRequireFurtherLink(
			$property,
			$subject,
			$html
		);

		// Return false in order to stop the link creation process to replace the
		// standard link
		return $doesRequireFurtherLink;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
	 */
	public function onAfterCreateTablesComplete ( $tableBuilder, $messageReporter, $options ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$importerServiceFactory = $applicationFactory->create( 'ImporterServiceFactory' );

		$importer = $importerServiceFactory->newImporter(
			$importerServiceFactory->newJsonContentIterator(
				$applicationFactory->getSettings()->get( 'smwgImportFileDirs' )
			)
		);

		$importer->isEnabled( $options->safeGet( \SMW\SQLStore\Installer::OPT_IMPORT, false ) );
		$importer->setMessageReporter( $messageReporter );
		$importer->doImport();

		$options->set( 'hook-execution', [ 'import' ] );

		return true;
	}

	public function onAdminLinks( \ALTree $admin_links_tree ) {

		$adminLinks = new AdminLinks();
		$adminLinks->process( $admin_links_tree );

		return true;
	}

	public function onPageSchemasRegisterHandlers() {
		$GLOBALS['wgPageSchemasHandlerClasses'][] = 'SMWPageSchemas';
		return true;
	}

}
