<?php

namespace SMW\MediaWiki;

use ALTree;
use Article;
use File;
use MediaWiki\Context\IContextSource;
use MediaWiki\EditPage\EditPage;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use Skin;
use SMW\MediaWiki\Hooks\AdminLinks;
use SMW\MediaWiki\Hooks\AfterCreateTablesComplete;
use SMW\MediaWiki\Hooks\AfterDataUpdateComplete;
use SMW\MediaWiki\Hooks\AfterIncomingPropertiesLookupComplete;
use SMW\MediaWiki\Hooks\AfterQueryResultLookupComplete;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\MediaWiki\Hooks\ArticleFromTitle;
use SMW\MediaWiki\Hooks\ArticleProtectComplete;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\MediaWiki\Hooks\ArticleViewHeader;
use SMW\MediaWiki\Hooks\BeforeDisplayNoArticleText;
use SMW\MediaWiki\Hooks\BeforeIncomingPropertyValuesFurtherLinkCreate;
use SMW\MediaWiki\Hooks\BeforePageDisplay;
use SMW\MediaWiki\Hooks\BeforeQueryResultLookupComplete;
use SMW\MediaWiki\Hooks\BlockIpComplete;
use SMW\MediaWiki\Hooks\DeleteAccount;
use SMW\MediaWiki\Hooks\EditPageForm;
use SMW\MediaWiki\Hooks\ExtensionSchemaUpdates;
use SMW\MediaWiki\Hooks\ExtensionTypes;
use SMW\MediaWiki\Hooks\FileUpload;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Hooks\InternalParseBeforeLinks;
use SMW\MediaWiki\Hooks\LinksUpdateComplete;
use SMW\MediaWiki\Hooks\MaintenanceUpdateAddParams;
use SMW\MediaWiki\Hooks\OutputPageCheckLastModified;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\MediaWiki\Hooks\PageMoveComplete;
use SMW\MediaWiki\Hooks\PageSchemasRegisterHandlers;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\MediaWiki\Hooks\ParserClearState;
use SMW\MediaWiki\Hooks\ParserFirstCallInit;
use SMW\MediaWiki\Hooks\ParserOptionsRegister;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\MediaWiki\Hooks\ResourceLoaderGetConfigVars;
use SMW\MediaWiki\Hooks\RevisionFromEditComplete;
use SMW\MediaWiki\Hooks\SidebarBeforeOutput;
use SMW\MediaWiki\Hooks\SkinAfterContent;
use SMW\MediaWiki\Hooks\SkinTemplateNavigationUniversal;
use SMW\MediaWiki\Hooks\SoftwareInfo;
use SMW\MediaWiki\Hooks\SpecialSearchProfileForm;
use SMW\MediaWiki\Hooks\SpecialSearchProfiles;
use SMW\MediaWiki\Hooks\SpecialSearchResultsPrepend;
use SMW\MediaWiki\Hooks\SpecialStatsAddExtra;
use SMW\MediaWiki\Hooks\TitleIsAlwaysKnown;
use SMW\MediaWiki\Hooks\TitleIsMovable;
use SMW\MediaWiki\Hooks\TitleQuickPermissions;
use SMW\MediaWiki\Hooks\UnblockUserComplete;
use SMW\MediaWiki\Hooks\UserChange;
use SMW\MediaWiki\Hooks\UserGroupsChanged;
use SMW\NamespaceManager;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Store;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class Hooks {

	/**
	 * @var array
	 */
	private $handlers = [];

	private HookContainer $hookContainer;

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
	 * @return bool
	 */
	public function isRegistered( $name ): bool {
		return isset( $this->handlers[$name] );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 */
	public function clear( string $name = '' ): void {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$handlers = $name !== '' ? [ $name ] : $this->getHandlerList();

		foreach ( $handlers as $name ) {
			$this->hookContainer->clear( $name );
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 *
	 * @return callable|false
	 */
	public function getHandlerFor( $name ): callable|false {
		return $this->handlers[$name] ?? false;
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getHandlerList(): array {
		return array_keys( $this->handlers );
	}

	/**
	 * @since 2.1
	 */
	public function register(): void {
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
	public static function registerExtensionCheck( array &$vars ): void {
		$vars['wgHooks']['BeforePageDisplay']['smw-extension-check'] = static function ( OutputPage $outputPage ): bool {
			BeforePageDisplay::informAboutExtensionAvailability( $outputPage );

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
	public static function onCanonicalNamespaces( array &$namespaces ): bool {
		NamespaceManager::initCanonicalNamespaces(
			$namespaces
		);

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$vars
	 */
	public static function registerEarly( array &$vars ): void {
		// Remove the hook registered via `Hook::registerExtensionCheck` given
		// that at this point we know the extension was loaded and hereby is
		// available.
		if ( defined( 'SMW_EXTENSION_LOADED' ) ) {
			unset( $vars['wgHooks']['BeforePageDisplay']['smw-extension-check'] );
		}
	}

	private function registerHandlers(): void {
		$elasticFactory = ApplicationFactory::getInstance()->singleton( 'ElasticFactory' );

		$this->handlers = $elasticFactory->newHooks()->getHandlers();

		$this->handlers += [
			'ParserAfterTidy' => [ $this, 'onParserAfterTidy' ],
			'ParserClearState' => [ $this, 'onParserClearState' ],
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
	public function onParserAfterTidy( &$parser, &$text ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$parserAfterTidy = new ParserAfterTidy(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getCache(),
			$applicationFactory,
			$applicationFactory->getHookDispatcher(),
			$applicationFactory->getSettings(),
			$applicationFactory->getMediaWikiLogger()
		);

		$parserAfterTidy->onParserAfterTidy( $parser, $text );

		return true;
	}

	/**
	 * Hook: ParserClearState fires at the start of every `Parser::parse()`
	 * call. Used to track in-flight parses per title so that `ParserAfterTidy`
	 * can distinguish the outermost fire from inner (nested) fires triggered
	 * by extensions that clone the parser, see #5923.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserClearState
	 *
	 * @since 7.0.0
	 */
	public function onParserClearState( Parser $parser ): bool {
		( new ParserClearState() )->onParserClearState( $parser );

		return true;
	}

	/**
	 * Hook: Called by Skin when building the toolbox array and
	 * returning it for the skin to output.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SidebarBeforeOutput
	 */
	public function onSidebarBeforeOutput( $skin, array &$sidebar ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$sidebarBeforeOutput = new SidebarBeforeOutput(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getSettings()
		);

		$sidebarBeforeOutput->onSidebarBeforeOutput( $skin, $sidebar );

		return true;
	}

	/**
	 * Hook: Allows extensions to add text after the page content and article
	 * metadata.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
	 */
	public function onSkinAfterContent( string &$data, $skin = null ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$skinAfterContent = new SkinAfterContent(
			$applicationFactory->getFactboxFactory()
		);

		return $skinAfterContent->onSkinAfterContent( $data, $skin );
	}

	/**
	 * Hook: Called after parse, before the HTML is added to the output
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 */
	public function onOutputPageParserOutput( OutputPage &$outputPage, ParserOutput $parserOutput ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$outputPageParserOutput = new OutputPageParserOutput(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getFactboxText(),
			$applicationFactory->getFactboxFactory(),
			MediaWikiServices::getInstance()->getUserOptionsLookup()
		);

		$outputPageParserOutput->onOutputPageParserOutput( $outputPage, $parserOutput );

		return true;
	}

	/**
	 * Hook: When checking if the page has been modified since the last visit
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageCheckLastModified
	 */
	public function onOutputPageCheckLastModified( array &$lastModified ): bool {
		( new OutputPageCheckLastModified() )->onOutputPageCheckLastModified( $lastModified, null );

		return true;
	}

	/**
	 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 */
	public function onBeforePageDisplay( OutputPage &$outputPage, Skin &$skin ): bool {
		$beforePageDisplay = new BeforePageDisplay(
			MediaWikiServices::getInstance()->getUserOptionsLookup(),
			ApplicationFactory::getInstance()->getSettings()
		);

		$beforePageDisplay->onBeforePageDisplay( $outputPage, $skin );

		return true;
	}

	/**
	 * Hook: Called immediately before returning HTML on the search results page
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsPrepend
	 */
	public function onSpecialSearchResultsPrepend( $specialSearch, $outputPage, $term ): bool {
		$specialSearchResultsPrepend = new SpecialSearchResultsPrepend(
			ApplicationFactory::getInstance()->getUserOptionsLookup()
		);

		return $specialSearchResultsPrepend->onSpecialSearchResultsPrepend( $specialSearch, $outputPage, $term );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfiles
	 */
	public function onSpecialSearchProfiles( array &$profiles ): bool {
		( new SpecialSearchProfiles() )->onSpecialSearchProfiles( $profiles );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm
	 */
	public function onSpecialSearchProfileForm( $specialSearch, &$form, $profile, $term, array $opts ): bool {
		$specialSearchProfileForm = new SpecialSearchProfileForm(
			ApplicationFactory::getInstance()->getStore()
		);

		return $specialSearchProfileForm->onSpecialSearchProfileForm( $specialSearch, $form, $profile, $term, $opts );
	}

	/**
	 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
	 * code after <nowiki>, HTML-comments, and templates have been treated.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
	 */
	public function onInternalParseBeforeLinks( &$parser, &$text, &$stripState ): bool {
		$internalParseBeforeLinks = new InternalParseBeforeLinks(
			ApplicationFactory::getInstance()->getSettings()
		);

		return $internalParseBeforeLinks->onInternalParseBeforeLinks( $parser, $text, $stripState );
	}

	/**
	 * Hook: RevisionFromEditComplete called when a revision was inserted
	 * due to an edit
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RevisionFromEditComplete
	 */
	public function onRevisionFromEditComplete( WikiPage $wikiPage, ?RevisionRecord $revision, $baseId, $user ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$revisionFromEditComplete = new RevisionFromEditComplete(
			$applicationFactory->singleton( 'PropertyAnnotatorFactory' ),
			$applicationFactory->singleton( 'SchemaFactory' ),
			$applicationFactory->getStore(),
			$applicationFactory->getEventDispatcher()
		);

		$tags = [];
		$revisionFromEditComplete->onRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user, $tags );

		return true;
	}

	/**
	 * Hook: Occurs after the protect article request has been processed
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public function onArticleProtectComplete( WikiPage &$wikiPage, ?User &$user, array $protections, $reason ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$articleProtectComplete = new ArticleProtectComplete(
			$applicationFactory->getSettings(),
			$applicationFactory->getMediaWikiLogger()
		);

		$articleProtectComplete->onArticleProtectComplete( $wikiPage, $user, $protections, $reason );

		return true;
	}

	/**
	 * Hook: Occurs when an articleheader is shown
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewHeader
	 */
	public function onArticleViewHeader( Article &$page, &$outputDone, &$useParserCache ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$articleViewHeader = new ArticleViewHeader(
			$applicationFactory->getStore(),
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getSettings()
		);

		$articleViewHeader->onArticleViewHeader( $page, $outputDone, $useParserCache );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
	 */
	public function onRejectParserCacheValue( $value, $page, $popts ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$rejectParserCacheValue = new RejectParserCacheValue(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getMediaWikiLogger()
		);

		// Return false to reject the parser cache
		// The log will contain something like "[ParserCache] ParserOutput
		// key valid, but rejected by RejectParserCacheValue hook handler."
		return $rejectParserCacheValue->onRejectParserCacheValue( $value, $page, $popts );
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
	): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$pageMoveComplete = new PageMoveComplete(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getStore(),
			$applicationFactory->getEventDispatcher()
		);

		$pageMoveComplete->onPageMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId, '', null );

		return true;
	}

	/**
	 * Hook: ArticlePurge executes before running "&action=purge"
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
	 */
	public function onArticlePurge( WikiPage &$wikiPage ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$articlePurge = new ArticlePurge(
			$applicationFactory->getCache(),
			$applicationFactory->getSettings(),
			$applicationFactory->getEventDispatcher()
		);

		$articlePurge->onArticlePurge( $wikiPage );

		return true;
	}

	/**
	 * Hook: ArticleDelete occurs whenever the software receives a request
	 * to delete an article
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
	 */
	public function onArticleDelete( &$wikiPage, &$user, &$reason, &$error ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$articleDelete = new ArticleDelete(
			$applicationFactory->getStore(),
			$applicationFactory->newJobFactory(),
			$applicationFactory->getEventDispatcher()
		);

		$status = Status::newGood();
		$articleDelete->onArticleDelete( $wikiPage, $user, $reason, $error, $status, false );

		return true;
	}

	/**
	 * Hook: LinksUpdateComplete called at the end of LinksUpdate() construction
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 */
	public function onLinksUpdateComplete( $linksUpdate ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$linksUpdateComplete = new LinksUpdateComplete(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory,
			$applicationFactory->singleton( 'RevisionGuard' ),
			$applicationFactory->getMediaWikiLogger()
		);

		$linksUpdateComplete->onLinksUpdateComplete( $linksUpdate, null );

		return true;
	}

	/**
	 * Hook: Add extra statistic at the end of Special:Statistics
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
	 */
	public function onSpecialStatsAddExtra( array &$extraStats, IContextSource $context ): bool {
		$specialStatsAddExtra = new SpecialStatsAddExtra(
			ApplicationFactory::getInstance()->getStore()
		);

		$specialStatsAddExtra->onSpecialStatsAddExtra( $extraStats, $context );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
	 */
	public function onFileUpload( File $file, ?bool $reupload ): bool {
		$fileUpload = new FileUpload(
			ApplicationFactory::getInstance()->getNamespaceExaminer(),
			MediaWikiServices::getInstance()->getHookContainer(),
			ApplicationFactory::getInstance()->newPageCreator()
		);

		return $fileUpload->onFileUpload( $file, (bool)$reupload, false );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/MaintenanceUpdateAddParams
	 */
	public function onMaintenanceUpdateAddParams( array &$params ): bool {
		( new MaintenanceUpdateAddParams() )->onMaintenanceUpdateAddParams( $params );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 */
	public function onResourceLoaderGetConfigVars( array &$vars ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$resourceLoaderGetConfigVars = new ResourceLoaderGetConfigVars(
			MediaWikiServices::getInstance()->getNamespaceInfo(),
			$applicationFactory->getSettings()
		);

		$resourceLoaderGetConfigVars->onResourceLoaderGetConfigVars( $vars, '', MediaWikiServices::getInstance()->getMainConfig() );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 */
	public function onGetPreferences( ?User $user, array &$preferences ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$getPreferences = new GetPreferences(
			$applicationFactory->singleton( 'SchemaFactory' ),
			$applicationFactory->getHookDispatcher(),
			$applicationFactory->getSettings()
		);

		$getPreferences->onGetPreferences( $user, $preferences );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
	 */
	public function onPersonalUrls( array &$personal_urls, $title, $skinTemplate ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$personalUrls = new PersonalUrls(
			$applicationFactory->getJobQueue(),
			$applicationFactory->getUserOptionsLookup(),
			$applicationFactory->getSettings()
		);

		$personalUrls->onPersonalUrls( $personal_urls, $title, $skinTemplate );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
	 */
	public function onSkinTemplateNavigationUniversal( &$skinTemplate, array &$links ): bool {
		$skinTemplateNavigationUniversal = new SkinTemplateNavigationUniversal(
			MediaWikiServices::getInstance()->getService( 'SMW.PersonalUrls' )
		);
		$skinTemplateNavigationUniversal->onSkinTemplateNavigation__Universal( $skinTemplate, $links );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 */
	public function onLoadExtensionSchemaUpdates( $databaseUpdater ): bool {
		( new ExtensionSchemaUpdates() )->onLoadExtensionSchemaUpdates( $databaseUpdater );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
	 */
	public function onExtensionTypes( array &$extTypes ): bool {
		( new ExtensionTypes() )->onExtensionTypes( $extTypes );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
	 */
	public function onTitleIsAlwaysKnown( $title, &$result ): bool {
		( new TitleIsAlwaysKnown() )->onTitleIsAlwaysKnown( $title, $result );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
	 */
	public function onArticleFromTitle( Title &$title, ?Article &$article ): bool {
		$articleFromTitle = new ArticleFromTitle(
			ApplicationFactory::getInstance()->getStore()
		);

		$articleFromTitle->onArticleFromTitle( $title, $article, null );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
	 */
	public function onTitleIsMovable( $title, &$isMovable ): bool {
		( new TitleIsMovable() )->onTitleIsMovable( $title, $isMovable );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeDisplayNoArticleText
	 */
	public function onBeforeDisplayNoArticleText( $article ) {
		return ( new BeforeDisplayNoArticleText() )->onBeforeDisplayNoArticleText( $article );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
	 */
	public function onEditPageShowEditFormInitial( EditPage $editPage, $output ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$editPageForm = new EditPageForm(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->getUserOptionsLookup(),
			$applicationFactory->getSettings()
		);

		return $editPageForm->onEditPage__showEditForm_initial( $editPage, $output );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
	 *
	 * "...Quick permissions are checked first in the Title::checkQuickPermissions
	 * function. Quick permissions are the most basic of permissions needed
	 * to perform an action ..."
	 */
	public function onTitleQuickPermissions( Title $title, User $user, $action, &$errors, $rigor, $short ) {
		$applicationFactory = ApplicationFactory::getInstance();

		$titleQuickPermissions = new TitleQuickPermissions(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->singleton( 'TitlePermissions' )
		);

		return $titleQuickPermissions->onTitleQuickPermissions( $title, $user, $action, $errors, $rigor, $short );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserOptionsRegister (Only 1.30+)
	 */
	public function onParserOptionsRegister( array &$defaults, array &$inCacheKey ): bool {
		$lazyLoad = [];
		( new ParserOptionsRegister() )->onParserOptionsRegister( $defaults, $inCacheKey, $lazyLoad );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 */
	public function onParserFirstCallInit( Parser &$parser ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$parserFirstCallInit = new ParserFirstCallInit(
			$applicationFactory->newParserFunctionFactory(),
			$applicationFactory->getSettings()
		);

		$parserFirstCallInit->onParserFirstCallInit( $parser );

		return true;
	}

	/**
	 * @see https://github.com/wikimedia/mediawiki-extensions-UserMerge/blob/master/includes/MergeUser.php#L654
	 * @provided by Extension:UserMerge
	 */
	public function onDeleteAccount( $user ): bool {
		$applicationFactory = ApplicationFactory::getInstance();

		$articleDelete = new ArticleDelete(
			$applicationFactory->getStore(),
			$applicationFactory->newJobFactory(),
			$applicationFactory->getEventDispatcher()
		);

		$deleteAccount = new DeleteAccount(
			$applicationFactory->getNamespaceExaminer(),
			$articleDelete
		);

		$deleteAccount->onDeleteAccount( $user );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
	 */
	public function onBlockIpComplete( $block, $performer, $priorBlock ): bool {
		( new BlockIpComplete( $this->newUserChange() ) )
			->onBlockIpComplete( $block, $performer, $priorBlock );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
	 */
	public function onUnblockUserComplete( $block, $performer ): bool {
		( new UnblockUserComplete( $this->newUserChange() ) )
			->onUnblockUserComplete( $block, $performer );

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 */
	public function onUserGroupsChanged( $user ): bool {
		( new UserGroupsChanged( $this->newUserChange() ) )
			->onUserGroupsChanged( $user, [], [], false, false, [], [] );

		return true;
	}

	private function newUserChange(): UserChange {
		$applicationFactory = ApplicationFactory::getInstance();

		return new UserChange(
			$applicationFactory->getNamespaceExaminer(),
			$applicationFactory->newJobFactory()
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SoftwareInfo
	 */
	public function onSoftwareInfo( array &$software ): bool {
		( new SoftwareInfo() )->onSoftwareInfo( $software );

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
	 */
	public function onAfterDataUpdateComplete( Store $store, $semanticData, ChangeOp $changeOp ): bool {
		( new AfterDataUpdateComplete() )->onSMWSQLStoreAfterDataUpdateComplete( $store, $semanticData, $changeOp );

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::BeforeQueryResultLookupComplete
	 */
	public function onBeforeQueryResultLookupComplete( $store, $query, &$result, $queryEngine ): bool {
		return ( new BeforeQueryResultLookupComplete() )
			->onSMWStoreBeforeQueryResultLookupComplete( $store, $query, $result, $queryEngine );
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Store::AfterQueryResultLookupComplete
	 */
	public function onAfterQueryResultLookupComplete( $store, &$result ): bool {
		( new AfterQueryResultLookupComplete() )->onSMWStoreAfterQueryResultLookupComplete( $store, $result );

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks/Browse::AfterIncomingPropertiesLookupComplete
	 */
	public function onAfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions ): bool {
		( new AfterIncomingPropertiesLookupComplete() )
			->onSMWBrowseAfterIncomingPropertiesLookupComplete( $store, $semanticData, $requestOptions );

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
		return ( new BeforeIncomingPropertyValuesFurtherLinkCreate() )
			->onSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate( $property, $subject, $html, $store );
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::Installer::AfterCreateTablesComplete
	 */
	public function onAfterCreateTablesComplete( $tableBuilder, $messageReporter, $options ): bool {
		( new AfterCreateTablesComplete() )
			->onSMWSQLStoreInstallerAfterCreateTablesComplete( $tableBuilder, $messageReporter, $options );

		return true;
	}

	public function onAdminLinks(
		// @phan-suppress-next-line PhanUndeclaredTypeParameter
		ALTree $admin_links_tree
	): bool {
		( new AdminLinks() )->onAdminLinks( $admin_links_tree );

		return true;
	}

	public function onPageSchemasRegisterHandlers(): bool {
		( new PageSchemasRegisterHandlers() )->onPageSchemasRegisterHandlers();

		return true;
	}

}
