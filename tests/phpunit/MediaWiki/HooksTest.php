<?php

namespace SMW\Tests\MediaWiki;

use MediaWiki\Block\Block;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Edit\PreparedEdit;
use MediaWiki\User\UserIdentity;
use ParserOptions;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Hooks;
use SMW\Tests\TestEnvironment;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Hooks
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HooksTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $parser;
	private $title;
	private $outputPage;
	private $requestContext;
	private $skin;
	private $store;
	private $testEnvironment;

	/**
	 * Has a static signature on purpose!
	 *
	 * @var array
	 */
	private static $handlers = [];

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$this->title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->willReturn( 9900 );

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestContext = $this->getMockBuilder( '\RequestContext' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestContext->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $this->outputPage );

		$this->requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->willReturn( $webRequest );

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->skin->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $this->requestContext );

		$this->skin->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $this->outputPage );

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->any() )
			->method( 'parse' )
			->willReturn( $this->parser );

		$deferredCallableUpdate = $this->getMockBuilder( CallableUpdate::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );
		$this->testEnvironment->registerObject( 'DeferredCallableUpdate', $deferredCallableUpdate );

		$parserCache = $this->getMockBuilder( '\ParserCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'ParserCache', $parserCache );

		$permissionManager = $this->getMockBuilder( '\SMW\MediaWiki\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PermissionManager', $permissionManager );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$vars = [];

		$this->assertInstanceOf(
			Hooks::class,
			new Hooks()
		);
	}

	public function testRegisterExtensionCheck() {
		$vars = [];

		Hooks::registerExtensionCheck( $vars );

		// BeforePageDisplay
		$callback = end( $vars['wgHooks']['BeforePageDisplay'] );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$callback,
			[ $outputPage ]
		);
	}

	/**
	 * @dataProvider callMethodProvider
	 */
	public function testRegister( $method ) {
		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$vars = [
			'IP' => 'bar',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		];

		$instance = new Hooks();
		$instance->register( $vars );

		self::$handlers[] = call_user_func_array( [ $this, $method ], [ $instance ] );
	}

	/**
	 * @depends testRegister
	 */
	public function testCheckOnMissingHandlers() {
		$disabled = [
			'PageSchemasRegisterHandlers',
			'AdminLinks'
		];

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$vars = [
			'IP' => 'bar',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		];

		$instance = new Hooks();
		$instance->register( $vars );

		$handlerList = $instance->getHandlerList();
		$handlers = array_flip( self::$handlers );

		foreach ( $handlerList as $handler ) {

			if ( array_search( $handler, $disabled ) !== false ) {
				continue;
			}

			$this->assertArrayHasKey( $handler, $handlers, "Missing a `$handler` test!" );
		}

		self::$handlers = [];
	}

	public function callMethodProvider() {
		return [
			[ 'callParserAfterTidy' ],
			[ 'callSkinAfterContent' ],
			[ 'callSidebarBeforeOutput' ],
			[ 'callOutputPageParserOutput' ],
			[ 'callBeforePageDisplay' ],
			[ 'callSpecialSearchResultsPrepend' ],
			[ 'callSpecialSearchProfiles' ],
			[ 'callSpecialSearchProfileForm' ],
			[ 'callInternalParseBeforeLinks' ],
			[ 'callRevisionFromEditComplete' ],
			[ 'callPageMoveComplete' ],
			[ 'callArticleProtectComplete' ],
			[ 'callArticleViewHeader' ],
			[ 'callArticlePurge' ],
			[ 'callArticleDelete' ],
			[ 'callLinksUpdateComplete' ],
			[ 'callSpecialStatsAddExtra' ],
			[ 'callFileUpload' ],
			[ 'callMaintenanceUpdateAddParams' ],
			[ 'callResourceLoaderGetConfigVars' ],
			[ 'callGetPreferences' ],
			[ 'callSkinTemplateNavigation' ],
			[ 'callLoadExtensionSchemaUpdates' ],
			[ 'callExtensionTypes' ],
			[ 'callTitleIsAlwaysKnown' ],
			[ 'callBeforeDisplayNoArticleText' ],
			[ 'callArticleFromTitle' ],
			[ 'callTitleIsMovable' ],
			[ 'callContentHandlerForModelID' ],
			[ 'callEditPageForm' ],
			[ 'callParserOptionsRegister' ],
			[ 'callParserFirstCallInit' ],
			[ 'callTitleQuickPermissions' ],
			[ 'callOutputPageCheckLastModified' ],
			[ 'callRejectParserCacheValue' ],
			[ 'callSoftwareInfo' ],
			[ 'callBlockIpComplete' ],
			[ 'callUnblockUserComplete' ],
			[ 'callUserGroupsChanged' ],
			[ 'callDeleteAccount' ],
			[ 'callSMWSQLStoreEntityReferenceCleanUpComplete' ],
			[ 'callSMWAdminRegisterTaskHandlers' ],
			[ 'callSMWEventRegisterEventListeners' ],
			[ 'callSMWSQLStorAfterDataUpdateComplete' ],
			[ 'callSMWStoreBeforeQueryResultLookupComplete' ],
			[ 'callSMWStoreAfterQueryResultLookupComplete' ],
			[ 'callSMWBrowseAfterIncomingPropertiesLookupComplete' ],
			[ 'callSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate' ],
			[ 'callSMWSQLStoreInstallerAfterCreateTablesComplete' ],
			[ 'callSMWMaintenanceAfterUpdateEntityCollationComplete' ],
			[ 'callSMWIndicatorEntityExaminerRegisterDeferrableIndicatorProviders' ]

		];
	}

// SMW::Maintenance::AfterUpdateEntityCollationComplete
	public function callParserAfterTidy( $instance ) {
		$handler = 'ParserAfterTidy';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$text = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$this->parser, &$text ]
		);

		return $handler;
	}

	public function callSkinAfterContent( $instance ) {
		$handler = 'SkinAfterContent';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$this->skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$data = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$data, $this->skin ]
		);

		return $handler;
	}

	public function callSidebarBeforeOutput( $instance ) {
		$handler = 'SidebarBeforeOutput';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$this->skin->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$sidebar = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $this->skin, &$sidebar ]
		);

		return $handler;
	}

	public function callOutputPageParserOutput( $instance ) {
		$handler = 'OutputPageParserOutput';

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$this->outputPage, $parserOutput ]
		);

		return $handler;
	}

	public function callBeforePageDisplay( $instance ) {
		$handler = 'BeforePageDisplay';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$this->outputPage, &$this->skin ]
		);

		return $handler;
	}

	public function callSpecialSearchResultsPrepend( $instance ) {
		$handler = 'SpecialSearchResultsPrepend';

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $specialSearch, $this->outputPage, '' ]
		);

		return $handler;
	}

	public function callSpecialSearchProfiles( $instance ) {
		$handler = 'SpecialSearchProfiles';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$profiles = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$profiles ]
		);

		return $handler;
	}

	public function callSpecialSearchProfileForm( $instance ) {
		$handler = 'SpecialSearchProfileForm';

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->any() )
			->method( 'getSearchEngine' )
			->willReturn( $searchEngine );

		$specialSearch->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$specialSearch->expects( $this->any() )
			->method( 'getNamespaces' )
			->willReturn( [] );

		$specialSearch->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $this->requestContext );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$form = '';
		$profile = 'smw';
		$term = '';
		$opts = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $specialSearch, &$form, $profile, $term, $opts ]
		);

		return $handler;
	}

	public function callInternalParseBeforeLinks( $instance ) {
		$handler = 'InternalParseBeforeLinks';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$parser->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( $this->createMock( ParserOptions::class ) );

		$stripState = $this->getMockBuilder( '\StripState' )
			->disableOriginalConstructor()
			->getMock();

		$text = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$parser, &$text, &$stripState ]
		);

		return $handler;
	}

	public function callRevisionFromEditComplete( $instance ) {
		$handler = 'RevisionFromEditComplete';

		$contentHandler = $this->getMockBuilder( '\ContentHandler' )
			->disableOriginalConstructor()
			->getMock();
		$contentHandler->expects( $this->any() )
			->method( 'getDefaultFormat' )
			->willReturn( CONTENT_FORMAT_WIKITEXT );

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->any() )
			->method( 'getContentHandler' )
			->willReturn( $contentHandler );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->willReturn( $content );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->with( $content, null, $user, CONTENT_FORMAT_WIKITEXT )
			->willReturn( $this->createMock( PreparedEdit::class ) );

		$baseId = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $wikiPage, $revision, $baseId, $user ]
		);

		return $handler;
	}

	public function callPageMoveComplete( $instance ) {
		$handler = 'PageMoveComplete';

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'deleteSubject' ] )
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $store );

		$oldTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$oldTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_SPECIAL );

		$oldTitle->expects( $this->any() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$newTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$newTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_SPECIAL );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$oldId = 42;
		$newId = 0;

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$oldTitle, &$newTitle, &$user, $oldId, $newId ]
		);

		$this->testEnvironment->registerObject( 'Store', $this->store );
		return $handler;
	}

	public function callArticleProtectComplete( $instance ) {
		$handler = 'ArticleProtectComplete';

		$contentHandler = $this->getMockBuilder( '\ContentHandler' )
			->disableOriginalConstructor()
			->getMock();
		$contentHandler->expects( $this->any() )
			->method( 'getDefaultFormat' )
			->willReturn( CONTENT_FORMAT_WIKITEXT );

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->any() )
			->method( 'getContentHandler' )
			->willReturn( $contentHandler );

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->willReturn( $content );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard->expects( $this->any() )
			->method( 'newRevisionFromPage' )
			->willReturn( $revision );

		$wikiPage->expects( $this->any() )
			->method( 'prepareContentForEdit' )
			->with( $content, null, $user, CONTENT_FORMAT_WIKITEXT )
			->willReturn( $this->createMock( PreparedEdit::class ) );

		$this->testEnvironment->registerObject( 'RevisionGuard', $revisionGuard );

		$protections = [];
		$reason = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$wikiPage, &$user, $protections, $reason ]
		);

		return $handler;
	}

	public function callArticleViewHeader( $instance ) {
		$handler = 'ArticleViewHeader';

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyTables', 'getPropertyTableInfoFetcher', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->testEnvironment->registerObject( 'Store', $store );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$page->expects( $this->any() )
			->method( 'makeParserOptions' )
			->with( 'canonical' )
			->willReturn( $this->createMock( ParserOptions::class ) );

		$article = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$article->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$article->expects( $this->any() )
			->method( 'getPage' )
			->willReturn( $page );

		$outputDone = '';
		$useParserCache = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$article, &$outputDone, &$useParserCache ]
		);

		return $handler;
	}

	public function callArticlePurge( $instance ) {
		$handler = 'ArticlePurge';

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$wikiPage ]
		);

		return $handler;
	}

	public function callArticleDelete( $instance ) {
		$handler = 'ArticleDelete';

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$reason = '';
		$error = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$wikiPage, &$user, &$reason, &$error ]
		);

		return $handler;
	}

	public function callLinksUpdateComplete( $instance ) {
		$handler = 'LinksUpdateComplete';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds', 'updateData' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->testEnvironment->registerObject( 'Store', $store );

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_SPECIAL );

		$linksUpdate = $this->createMock( LinksUpdate::class );

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$linksUpdate->expects( $this->any() )
			->method( 'getParserOutput' )
			->willReturn( $parserOutput );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $linksUpdate ]
		);

		$this->testEnvironment->registerObject( 'Store', $this->store );
		return $handler;
	}

	public function callSpecialStatsAddExtra( $instance ) {
		$handler = 'SpecialStatsAddExtra';

		$extraStats = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$extraStats, $this->requestContext ]
		);

		return $handler;
	}

	public function callFileUpload( $instance ) {
		$handler = 'FileUpload';

		$file = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$reupload = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $file, $reupload ]
		);

		return $handler;
	}

	public function callMaintenanceUpdateAddParams( $instance ) {
		$handler = 'MaintenanceUpdateAddParams';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$params = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$params ]
		);

		return $handler;
	}

	public function callResourceLoaderGetConfigVars( $instance ) {
		$handler = 'ResourceLoaderGetConfigVars';

		$vars = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$vars ]
		);

		return $handler;
	}

	public function callGetPreferences( $instance ) {
		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->willReturn( $schemaList );

		$schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaFinder' )
			->willReturn( $schemaFinder );

		$this->testEnvironment->registerObject( 'SchemaFactory', $schemaFactory );

		$handler = 'GetPreferences';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
		$preferences = [];
		$this->assertTrue(
			$instance->isRegistered( $handler )
		);
		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $user, &$preferences ]
		);
		return $handler;
	}

	public function callSkinTemplateNavigation( $instance ) {
		$handler = 'SkinTemplateNavigation::Universal';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$links = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$skinTemplate, &$links ]
		);

		return $handler;
	}

	public function callLoadExtensionSchemaUpdates( $instance ) {
		$handler = 'LoadExtensionSchemaUpdates';

		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $databaseUpdater ]
		);

		return $handler;
	}

	public function callExtensionTypes( $instance ) {
		$handler = 'ExtensionTypes';

		$extTypes = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$extTypes ]
		);

		return $handler;
	}

	public function callTitleIsAlwaysKnown( $instance ) {
		$handler = 'TitleIsAlwaysKnown';

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $this->title, &$result ]
		);

		return $handler;
	}

	public function callBeforeDisplayNoArticleText( $instance ) {
		$handler = 'BeforeDisplayNoArticleText';

		$article = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$article->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $article ]
		);

		return $handler;
	}

	public function callArticleFromTitle( $instance ) {
		$handler = 'ArticleFromTitle';

		$article = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$article->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$this->title, &$article ]
		);

		return $handler;
	}

	public function callTitleIsMovable( $instance ) {
		$handler = 'TitleIsMovable';

		$isMovable = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $this->title, &$isMovable ]
		);

		return $handler;
	}

	public function callContentHandlerForModelID( $instance ) {
		$handler = 'ContentHandlerForModelID';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$modelId = 'smw/schema';
		$contentHandler = '';

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $modelId, &$contentHandler ]
		);

		return $handler;
	}

	public function callEditPageForm( $instance ) {
		$handler = 'EditPage::showEditForm:initial';

		$title = Title::newFromText( 'Foo' );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$editPage = $this->getMockBuilder( '\EditPage' )
			->disableOriginalConstructor()
			->getMock();

		$editPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $user );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $editPage, $outputPage ]
		);

		return $handler;
	}

	public function callParserOptionsRegister( $instance ) {
		$handler = 'ParserOptionsRegister';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$defaults = [];
		$inCacheKey = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$defaults, &$inCacheKey ]
		);

		return $handler;
	}

	public function callParserFirstCallInit( $instance ) {
		$handler = 'ParserFirstCallInit';

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$parser ]
		);

		return $handler;
	}

	public function callTitleQuickPermissions( $instance ) {
		$handler = 'TitleQuickPermissions';

		$title = $this->title;

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$action = '';
		$errors = [];
		$rigor = '';
		$short = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $title, $user, $action, &$errors, $rigor, $short ]
		);

		return $handler;
	}

	public function callOutputPageCheckLastModified( $instance ) {
		$handler = 'OutputPageCheckLastModified';
		$modifiedTimes = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$modifiedTimes ]
		);

		return $handler;
	}

	public function callRejectParserCacheValue( $instance ) {
		$handler = 'RejectParserCacheValue';

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$parseOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyTables', 'getPropertyTableInfoFetcher' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$this->testEnvironment->registerObject( 'Store', $store );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $this->title );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$value = '';

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $value, $wikiPage, $parseOptions ]
		);

		return $handler;
	}

	public function callSoftwareInfo( $instance ) {
		$handler = 'SoftwareInfo';

		$software = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$software ]
		);

		return $handler;
	}

	public function callBlockIpComplete( $instance ) {
		$handler = 'BlockIpComplete';

		$block = $this->createMock( Block::class );

		$user = $this->createMock( UserIdentity::class );
		$user->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$block->expects( $this->any() )
			->method( 'getTargetUserIdentity' )
			->willReturn( $user );

		$performer = '';
		$priorBlock = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $block, $performer, $priorBlock ]
		);

		return $handler;
	}

	public function callUnblockUserComplete( $instance ) {
		$handler = 'UnblockUserComplete';

		$block = $this->createMock( Block::class );

		$user = $this->createMock( UserIdentity::class );
		$user->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$block->expects( $this->any() )
			->method( 'getTargetUserIdentity' )
			->willReturn( $user );

		$performer = '';
		$priorBlock = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $block, $performer, $priorBlock ]
		);

		return $handler;
	}

	public function callUserGroupsChanged( $instance ) {
		$handler = 'UserGroupsChanged';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $user ]
		);

		return $handler;
	}

	public function callDeleteAccount( $instance ) {
		$handler = 'DeleteAccount';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'foo_user' );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $user ]
		);

		return $handler;
	}

	public function callSMWStoreDropTables( $instance ) {
		$handler = 'SMW::Store::dropTables';

		$verbose = false;

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $verbose ]
		);

		return $handler;
	}

	public function callSMWSQLStoreEntityReferenceCleanUpComplete( $instance ) {
		$handler = 'SMW::SQLStore::EntityReferenceCleanUpComplete';

		if ( !$instance->getHandlerFor( $handler ) ) {
			return $this->markTestSkipped( "$handler not used" );
		}

		$id = 42;
		$subject = DIWikiPage::newFromText( __METHOD__ );
		$isRedirect = false;

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $this->store, $id, $subject, $isRedirect ]
		);

		return $handler;
	}

	public function callSMWAdminRegisterTaskHandlers( $instance ) {
		$handler = 'SMW::Admin::RegisterTaskHandlers';

		if ( !$instance->getHandlerFor( $handler ) ) {
			return $this->markTestSkipped( "$handler not used" );
		}

		$taskHandlerRegistry = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $taskHandlerRegistry, $store, $outputFormatter, $user ]
		);

		return $handler;
	}

	public function callSMWEventRegisterEventListeners( $instance ) {
		$handler = 'SMW::Event::RegisterEventListeners';

		if ( !$instance->getHandlerFor( $handler ) ) {
			return $this->markTestSkipped( "$handler not used" );
		}

		$eventListener = $this->getMockBuilder( '\Onoi\EventDispatcher\Listener\GenericCallbackEventListener' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $eventListener ]
		);

		return $handler;
	}

	public function callSMWSQLStorAfterDataUpdateComplete( $instance ) {
		$handler = 'SMW::SQLStore::AfterDataUpdateComplete';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( new \SMW\Options() );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->any() )
			->method( 'getChangedEntityIdSummaryList' )
			->willReturn( [] );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->willReturn( [] );

		$changeOp->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeOp->expects( $this->any() )
			->method( 'getFixedPropertyRecords' )
			->willReturn( [] );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $store, $semanticData, $changeOp ]
		);

		return $handler;
	}

	public function callSMWStoreBeforeQueryResultLookupComplete( $instance ) {
		$handler = 'SMW::Store::BeforeQueryResultLookupComplete';

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $this->store, $query, &$result, $queryEngine ]
		);

		return $handler;
	}

	public function callSMWStoreAfterQueryResultLookupComplete( $instance ) {
		$handler = 'SMW::Store::AfterQueryResultLookupComplete';

		$idTableLookup = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'warmUpCache' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds', 'getPropertyValues' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTableLookup );

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $store, &$result ]
		);

		return $handler;
	}

	public function callSMWBrowseAfterIncomingPropertiesLookupComplete( $instance ) {
		$handler = 'SMW::Browse::AfterIncomingPropertiesLookupComplete';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists', 'getId' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds', 'getPropertyValues' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->willReturn( [] );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $store, $semanticData, $requestOptions ]
		);

		return $handler;
	}

	public function callSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate( $instance ) {
		$handler = 'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate';

		$html = '';
		$property = new DIProperty( 'Foo' );
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $property, $subject, &$html, $this->store ]
		);

		return $handler;
	}

	public function callSMWSQLStoreInstallerAfterCreateTablesComplete( $instance ) {
		$handler = 'SMW::SQLStore::Installer::AfterCreateTablesComplete';

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( '\SMW\Options' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $tableBuilder, $messageReporter, $options ]
		);

		return $handler;
	}

	public function callSMWMaintenanceAfterUpdateEntityCollationComplete( $instance ) {
		$handler = 'SMW::Maintenance::AfterUpdateEntityCollationComplete';

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $store, $messageReporter ]
		);

		return $handler;
	}

	public function callSMWIndicatorEntityExaminerRegisterDeferrableIndicatorProviders( $instance ) {
		$handler = 'SMW::Indicator::EntityExaminer::RegisterDeferrableIndicatorProviders';

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProviders = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $store, &$indicatorProviders ]
		);

		return $handler;
	}

	private function assertThatHookIsExcutable( callable $handler, $arguments ) {
		$this->assertIsBool(

			call_user_func_array( $handler, $arguments )
		);
	}

}
