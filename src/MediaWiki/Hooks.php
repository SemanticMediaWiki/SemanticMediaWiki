<?php

namespace SMW\MediaWiki;

use IContextSource;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Onoi\HttpRequest\HttpRequestFactory;
use Parser;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\NamespaceManager;
use SMW\SemanticData;
use SMW\Setup;
use SMW\Site;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use ParserHooks\HookRegistrant;
use SkinTemplate;
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
use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;
use SMW\MediaWiki\Hooks\ExtensionTypes;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\MediaWiki\Hooks\LinksUpdateComplete;
use SMW\MediaWiki\Hooks\RevisionFromEditComplete;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars;
use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use SMW\MediaWiki\Hooks\SkinAfterContent;
use SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal;
use SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\MediaWiki\Hooks\TitleIsAlwaysKnown;
use SMW\MediaWiki\Hooks\TitleIsMovable;
use SMW\MediaWiki\Hooks\PageMoveComplete;
use SMW\MediaWiki\Hooks\TitleQuickPermissions;
use SMW\MediaWiki\Hooks\UserChange;
use SMW\MediaWiki\Hooks\DeleteAccount;
use SMW\MediaWiki\Hooks\AdminLinks;
use SMW\MediaWiki\Hooks\SpecialPageList;
use SMW\MediaWiki\Hooks\ApiModuleManager;
use SMW\Maintenance\runImport;
use StubGlobalUser;
use User;

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

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
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
		return isset( $this->handlers[$name] );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 */
	public function clear( string $name = '' ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		if ( $name !== [] ) {
			$handlers = [ $name ];
		} else {
			$handlers = $this->getHandlerList();
		}

		foreach ( $handlers as $name ) {
			$this->hookContainer->clear( $name );
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
			$this->hookContainer->register( $name, $callback );
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
		$vars['wgHooks']['BeforePageDisplay']['smw-extension-check'] = function ( $outputPage ) {
			$beforePageDisplay = new BeforePageDisplay();

			$beforePageDisplay->setOptions(
				[
					'SMW_EXTENSION_LOADED' => defined( 'SMW_EXTENSION_LOADED' ),

					// We might run out of the Semantic MediaWiki context hence
					// rely on $GLOBALS to fetch the latest value
					'smwgIgnoreExtensionRegistrationCheck' => $GLOBALS['smwgIgnoreExtensionRegistrationCheck']
				]
			);

			$beforePageDisplay->informAboutExtensionAvailability( $outputPage );

			return true;
		};
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
	 */
	public static function onCanonicalNamespaces( array &$namespaces ) {
		NamespaceManager::initCanonicalNamespaces(
			$namespaces
		);

		return true;
	}

	/**
	 * Called when ApiMain has finished initializing its module manager. Can
	 * be used to conditionally register API modules.
	 *
	 * #2813
	 */
	public static function onApiModuleManager( $moduleManager ) {
		$apiModuleManager = new ApiModuleManager();
		$apiModuleManager->setOptions(
			[
				'SMW_EXTENSION_LOADED' => defined( 'SMW_EXTENSION_LOADED' )
			]
		);

		$apiModuleManager->process( $moduleManager );

		return true;
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
	}

	private function registerHandlers() {
		$elasticFactory = ApplicationFactory::getInstance()->singleton( 'ElasticFactory' );

		$this->handlers = $elasticFactory->newHooks()->getHandlers();

		$this->handlers += [
			'ParserAfterTidy' => [ $this, 'onParserAfterTidy' ],
			'ParserOptionsRegister' => [ $this, 'onParserOptionsRegister' ],
			'ParserFirstCallInit' => [ $this, 'onParserFirstCallInit' ],
			'InternalParseBeforeLinks' => [ $this, 'onInternalParseBeforeLinks' ],
			'RejectParserCacheValue' => [ $this, 'onRejectParserCacheValue' ],

			'SkinAfterContent' => [ $this, 'onSkinAfterContent' ],
			'OutputPageParserOutput' => [ $this, 'onOutputPageParserOutput' ],
			'OutputPageCheckLastModified' => [ $this, 'onOutputPageCheckLastModified' ],
			'BeforePageDisplay' => [ $this, 'onBeforePageDisplay' ],
			'BeforeDisplayNoArticleText' => [ $this, 'onBeforeDisplayNoArticleText' ],
			'EditPage::showEditForm:initial' => [ $this, 'onEditPageShowEditFormInitial' ],

			'PageMoveComplete' => [ $this, 'onPageMoveComplete' ],
			'TitleIsAlwaysKnown' => [ $this, 'onTitleIsAlwaysKnown' ],
			'TitleQuickPermissions' => [ $this, 'onTitleQuickPermissions' ],
			'TitleIsMovable' => [ $this, 'onTitleIsMovable' ],

			'ArticlePurge' => [ $this, 'onArticlePurge' ],
			'ArticleDelete' => [ $this, 'onArticleDelete' ],
			'ArticleFromTitle' => [ $this, 'onArticleFromTitle' ],
			'ArticleProtectComplete' => [ $this, 'onArticleProtectComplete' ],
			'ArticleViewHeader' => [ $this, 'onArticleViewHeader' ],
			'ContentHandlerForModelID' => [ $this, 'onContentHandlerForModelID' ],

			'RevisionFromEditComplete' => [ $this, 'onRevisionFromEditComplete' ],
			'LinksUpdateComplete' => [ $this, 'onLinksUpdateComplete' ],
			'FileUpload' => [ $this, 'onFileUpload' ],
			'MaintenanceUpdateAddParams' => [ $this, 'onMaintenanceUpdateAddParams' ],

			'ResourceLoaderGetConfigVars' => [ $this, 'onResourceLoaderGetConfigVars' ],
			'GetPreferences' => [ $this, 'onGetPreferences' ],
			'SkinTemplateNavigation::Universal' => [ $this, 'onSkinTemplateNavigationUniversal' ],
			'SidebarBeforeOutput' => [ $this, 'onSidebarBeforeOutput' ],
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
			'DeleteAccount' => [ $this, 'onDeleteAccount' ],

			'SMW::SQLStore::AfterDataUpdateComplete' => [ $this, 'onAfterDataUpdateComplete' ],
			'SMW::SQLStore::Installer::AfterCreateTablesComplete' => [
				$this, 'onAfterCreateTablesComplete'
			],

			'SMW::Store::BeforeQueryResultLookupComplete' => [
				$this, 'onBeforeQueryResultLookupComplete'
			],
			'SMW::Store::AfterQueryResultLookupComplete' => [
				$this, 'onAfterQueryResultLookupComplete'
			],

			'SMW::Browse::AfterIncomingPropertiesLookupComplete' => [
				$this, 'onAfterIncomingPropertiesLookupComplete'
			],
			'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate' => [
				$this, 'onBeforeIncomingPropertyValuesFurtherLinkCreate'
			],

			'SMW::SQLStore::EntityReferenceCleanUpComplete' => [
				$elasticFactory, 'onEntityReferenceCleanUpComplete'
			],
			'SMW::Event::RegisterEventListeners' => [ $elasticFactory, 'onRegisterEventListeners' ],
			'SMW::Maintenance::AfterUpdateEntityCollationComplete' => [
				$elasticFactory, 'onAfterUpdateEntityCollationComplete'
			],

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

		$parserAfterTidy->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$parserAfterTidy->setHookDispatcher(
			$applicationFactory->getHookDispatcher()
		);

		$parserAfterTidy->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$parserAfterTidy->isReady(
			Site::isReady()
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
	 * Hook: Called by Skin when building the toolbox array and
	 * returning it for the skin to output.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SidebarBeforeOutput
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$sidebarBeforeOutput = new SidebarBeforeOutput(
			$applicationFactory->getNamespaceExaminer()
		);

		$sidebarBeforeOutput->setOptions(
			[
				'smwgBrowseFeatures' => $applicationFactory->getSettings()->get( 'smwgBrowseFeatures' )
			]
		);

		return $sidebarBeforeOutput->process( $skin, $sidebar );
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

		$skinAfterContent->setOptions(
			[
				'SMW_EXTENSION_LOADED' => defined( 'SMW_EXTENSION_LOADED' )
			]
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

		$permissionExaminer = $applicationFactory->newPermissionExaminer(
			$outputPage->getUser()
		);

		$outputPageParserOutput = new OutputPageParserOutput(
			$applicationFactory->getNamespaceExaminer(),
			$permissionExaminer,
			$applicationFactory->getFactboxText()
		);

		$preferenceExaminer = $applicationFactory->newPreferenceExaminer( $outputPage->getUser() );

		$outputPageParserOutput->setIndicatorRegistry(
			$applicationFactory->create(
				'IndicatorRegistry',
				$preferenceExaminer->hasPreferenceOf( GetPreferences::SHOW_ENTITY_ISSUE_PANEL )
			)
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
		$setupFile = new SetupFile();

		$beforePageDisplay->setOptions(
			[
				'incomplete_tasks' => $setupFile->findIncompleteTasks(),
				'is_upgrade' => $setupFile->get( SetupFile::PREVIOUS_VERSION ),
				'smwgEnableExportRDFLink' => $GLOBALS['smwgEnableExportRDFLink'],
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
		$applicationFactory = ApplicationFactory::getInstance();

		$preferenceExaminer = $applicationFactory->newPreferenceExaminer(
			$outputPage->getUser()
		);

		$specialSearchResultsPrepend = new SpecialSearchResultsPrepend(
			$preferenceExaminer,
			$specialSearch,
			$outputPage
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
				'smwgEnabledSpecialPage' => $applicationFactory->getSettings()
															  ->get( 'smwgEnabledSpecialPage' )
			]
		);

		return $internalParseBeforeLinks->process( $text );
	}

	/**
	 * Hook: RevisionFromEditComplete called when a revision was inserted
	 * due to an edit
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RevisionFromEditComplete
	 */
	public function onRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

		$user = User::newFromIdentity( $user );
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

		$revisionFromEditComplete = new RevisionFromEditComplete(
			$editInfo,
			$pageInfoProvider,
			$applicationFactory->singleton( 'PropertyAnnotatorFactory' ),
			$applicationFactory->singleton( 'SchemaFactory' )
		);

		$revisionFromEditComplete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$revisionFromEditComplete->process( $wikiPage->getTitle() );

		return true;
	}

	/**
	 * Hook: Occurs after the protect article request has been processed
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public function onArticleProtectComplete( &$wikiPage, &$user, $protections, $reason ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$revisionGuard = $applicationFactory->singleton( 'RevisionGuard' );

		$editInfo = $applicationFactory->newMwCollaboratorFactory()->newEditInfo(
			$wikiPage,
			$revisionGuard->newRevisionFromPage( $wikiPage ),
			$user
		);

		$articleProtectComplete = new ArticleProtectComplete(
			$wikiPage->getTitle(),
			$editInfo
		);

		$articleProtectComplete->setOptions(
			[
				'smwgEditProtectionRight' => $applicationFactory->getSettings()
															   ->get( 'smwgEditProtectionRight' )
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

		// #4741
		$wikiPage = $page->getPage();

		$dependencyValidator->setETag(
			$this->getETag( $parserCache, $wikiPage, $wikiPage->makeParserOptions( 'canonical' ) )
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
			$applicationFactory->getNamespaceExaminer(),
			$dependencyValidator
		);

		$articleViewHeader->setOptions(
			[
				'smwgChangePropagationProtection' => $settings->get( 'smwgChangePropagationProtection' ),
				'smwgChangePropagationWatchlist' => $settings->get( 'smwgChangePropagationWatchlist' )
			]
		);

		$articleViewHeader->process( $page, $outputDone, $useParserCache );

		return true;
	}

	private function getETag( $parserCache, $page, $pOpts ) {
		if ( method_exists( $parserCache, 'makeParserOutputKey' ) ) {
			// 1.36+
			return 'W/"' . $parserCache->makeParserOutputKey( $page, $pOpts	) .
				"--" . $page->getTouched() . '"';
		} else {
			return $parserCache->getETag( $page, $pOpts );
		}
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
		$dependencyValidator->setETag( $this->getETag( $parserCache, $page, $popts ) );

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
	 * Hook: PageMoveComplete occurs whenever a request to move an article
	 * is completed
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageMoveComplete
	 */
	public function onPageMoveComplete(
		LinkTarget $oldTitle,
		LinkTarget $newTitle,
		UserIdentity $user,
		int $oldId,
		int $newId
	) {
		$applicationFactory = ApplicationFactory::getInstance();

		$pageMoveComplete = new PageMoveComplete(
			$applicationFactory->getNamespaceExaminer()
		);

		$pageMoveComplete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$pageMoveComplete->process( $oldTitle, $newTitle, $user, $oldId, $newId );

		return true;
	}

	/**
	 * Hook: ArticlePurge executes before running "&action=purge"
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
	 */
	public function onArticlePurge( &$wikiPage ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$articlePurge = new ArticlePurge();

		$articlePurge->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$articlePurge->setOptions(
			[
				'smwgAutoRefreshOnPurge' => $settings->get( 'smwgAutoRefreshOnPurge' ),
				'smwgQueryResultCacheRefreshOnPurge' => $settings->get(
					'smwgQueryResultCacheRefreshOnPurge'
				)
			]
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

		$articleDelete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		return $articleDelete->process( $wikiPage->getTitle() );
	}

	/**
	 * Hook: LinksUpdateComplete called at the end of LinksUpdate() construction
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 */
	public function onLinksUpdateComplete( $linksUpdate ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$linksUpdateConstructed = new LinksUpdateComplete(
			$applicationFactory->getNamespaceExaminer()
		);

		$linksUpdateConstructed->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		// #3341
		// When running as part of the install don't try to access the DB
		// or update the Store
		$linksUpdateConstructed->isReady(
			Site::isReady()
		);

		$linksUpdateConstructed->setRevisionGuard(
			$applicationFactory->singleton( 'RevisionGuard' )
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
	public function onSpecialStatsAddExtra( &$extraStats, IContextSource $context ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$context->getOutput()->addModules( 'ext.smw.tooltip' );

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
				'SMW_EXTENSION_LOADED' => defined( 'SMW_EXTENSION_LOADED' )
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
			ApplicationFactory::getInstance()->getNamespaceExaminer(),
			MediaWikiServices::getInstance()->getHookContainer()
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
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$permissionExaminer = $applicationFactory->newPermissionExaminer(
			$user
		);

		$getPreferences = new GetPreferences(
			$permissionExaminer,
			$applicationFactory->singleton( 'SchemaFactory' )
		);

		$getPreferences->setHookDispatcher(
			$applicationFactory->getHookDispatcher()
		);

		$getPreferences->setOptions(
			[
				'smwgEnabledEditPageHelp' => $settings->get( 'smwgEnabledEditPageHelp' ),
				'smwgJobQueueWatchlist' => $settings->get( 'smwgJobQueueWatchlist' ),
				'wgSearchType' => $GLOBALS['wgSearchType']
			]
		);

		$getPreferences->process( $user, $preferences );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 */
	public function onPersonalUrls( array &$personal_urls, $title, $skinTemplate ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$user = $skinTemplate->getUser();

		$permissionExaminer = $applicationFactory->newPermissionExaminer(
			$user
		);

		$preferenceExaminer = $applicationFactory->newPreferenceExaminer(
			$user
		);

		$personalUrls = new PersonalUrls(
			$skinTemplate,
			$applicationFactory->getJobQueue(),
			$permissionExaminer,
			$preferenceExaminer
		);

		$personalUrls->setOptions(
			[
				'smwgJobQueueWatchlist' => $applicationFactory->getSettings()
															 ->get( 'smwgJobQueueWatchlist' )
			]
		);

		$personalUrls->process( $personal_urls );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
	 */
	public function onSkinTemplateNavigationUniversal( &$skinTemplate, &$links ) {
		if ( isset( $links['user-interface-preferences'] ) ) {
			$applicationFactory = ApplicationFactory::getInstance();
			$user = $skinTemplate->getUser();

			$permissionExaminer = $applicationFactory->newPermissionExaminer(
				$user
			);

			$preferenceExaminer = $applicationFactory->newPreferenceExaminer(
				$user
			);

			$personalUrls = new PersonalUrls(
				$skinTemplate,
				$applicationFactory->getJobQueue(),
				$permissionExaminer,
				$preferenceExaminer
			);

			$personalUrls->setOptions(
				[
					'smwgJobQueueWatchlist' => $applicationFactory->getSettings()
																 ->get( 'smwgJobQueueWatchlist' )
				]
			);

			$personalUrls->process( $links['user-interface-preferences'] );
		}

		$skinTemplateNavigationUniversal = new SkinTemplateNavigationUniversal(
			$skinTemplate,
			$links
		);
		return $skinTemplateNavigationUniversal->process();
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 */
	public function onLoadExtensionSchemaUpdates( $databaseUpdater ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$extensionSchemaUpdates = new ExtensionSchemaUpdates(
			$databaseUpdater
		);

		$extensionSchemaUpdates->process(
			$applicationFactory->getStore()
		);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
	 */
	public function onExtensionTypes( &$extTypes ) {
		$extensionTypes = new ExtensionTypes();

		return $extensionTypes->process( $extTypes );
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

		$permissionExaminer = $applicationFactory->newPermissionExaminer(
			$user
		);

		$preferenceExaminer = $applicationFactory->newPreferenceExaminer(
			$user
		);

		$editPageForm = new EditPageForm(
			$applicationFactory->getNamespaceExaminer(),
			$permissionExaminer,
			$preferenceExaminer
		);

		$editPageForm->setOptions(
			[
				'smwgEnabledEditPageHelp' => $applicationFactory->getSettings()
															   ->get( 'smwgEnabledEditPageHelp' )
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
			$applicationFactory->singleton( 'TitlePermissions' )
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
	 * @see https://github.com/wikimedia/mediawiki-extensions-UserMerge/blob/master/includes/MergeUser.php#L654
	 * @provided by Extension:UserMerge
	 *
	 */
	public function onDeleteAccount( $user ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$articleDelete = new ArticleDelete(
			$applicationFactory->getStore()
		);

		$articleDelete->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$deleteAccount = new DeleteAccount(
			$applicationFactory->getNamespaceExaminer(),
			$articleDelete
		);

		$deleteAccount->process( $user );

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
		$userChange->process( $block->getTargetUserIdentity() );

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
		$userChange->process( $block->getTargetUserIdentity() );

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

		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()
										  ->singleton( 'QueryDependencyLinksStoreFactory' );

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
	public function onBeforeQueryResultLookupComplete( $store, $query, &$result, $queryEngine ) {
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
	public function onAfterQueryResultLookupComplete( $store, &$result ) {
		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()
										  ->singleton( 'QueryDependencyLinksStoreFactory' );

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
	public function onAfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions ) {
		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()
										  ->singleton( 'QueryDependencyLinksStoreFactory' );

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
	public function onBeforeIncomingPropertyValuesFurtherLinkCreate(
		$property,
		$subject,
		&$html,
		$store
	) {
		$queryDependencyLinksStoreFactory = ApplicationFactory::getInstance()
										  ->singleton( 'QueryDependencyLinksStoreFactory' );

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
	public function onAfterCreateTablesComplete( $tableBuilder, $messageReporter, $options ) {
		$messageReporter->reportMessage(
			( new \SMW\Utils\CliMsgFormatter() )->section( 'Import task(s)', 3, '-', true )
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$importerServiceFactory = $applicationFactory->create( 'ImporterServiceFactory' );

		$contentIterator = $importerServiceFactory->newJsonContentIterator(
			$applicationFactory->getSettings()->get( 'smwgImportFileDirs' )
		);

		$importer = $importerServiceFactory->newImporter(
			$contentIterator
		);

		if ( defined( 'User::MAINTENANCE_SCRIPT_USER' ) ) {
			$maintenanceUser = User::MAINTENANCE_SCRIPT_USER;
		} else {
			// MW < 1.37
			$maintenanceUser = 'Maintenance script';
		}

		$importer->isEnabled( $options->safeGet( \SMW\SQLStore\Installer::RUN_IMPORT, false ) );
		$importer->setMessageReporter( $messageReporter );
		$importer->setImporter( $maintenanceUser );
		$importer->runImport();

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
