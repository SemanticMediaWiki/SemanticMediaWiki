<?php

namespace SMW\Tests\MediaWiki;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks;
use SMW\Tests\TestEnvironment;
use Title;

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
class HooksTest extends \PHPUnit_Framework_TestCase {

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

	protected function setUp() {

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
			->will( $this->returnValue( NS_MAIN ) );

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestContext = $this->getMockBuilder( '\RequestContext' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestContext->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->outputPage ) );

		$this->requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->will( $this->returnValue( $webRequest ) );

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->requestContext ) );

		$this->skin->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $this->outputPage ) );

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $this->parser ) );

		$deferredCallableUpdate = $this->getMockBuilder( '\SMW\DeferredCallableUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );
		$this->testEnvironment->registerObject( 'DeferredCallableUpdate', $deferredCallableUpdate );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$vars = [];

		$this->assertInstanceOf(
			Hooks::class,
			new Hooks( 'foo' )
		);
	}

	public function testInitExtension() {

		$vars = [];

		Hooks::registerEarly( $vars );

		// CanonicalNamespaces
		$callback = end( $vars['wgHooks']['CanonicalNamespaces'] );
		$namespaces = [];

		$this->assertThatHookIsExcutable(
			$callback,
			[ &$namespaces ]
		);

		// SpecialPage_initList
		$callback = end( $vars['wgHooks']['SpecialPage_initList'] );
		$specialPages = [];

		$this->assertThatHookIsExcutable(
			$callback,
			[ &$specialPages ]
		);

		// ApiMain::moduleManager
		$callback = end( $vars['wgHooks']['ApiMain::moduleManager'] );

		$apiModuleManager = $this->getMockBuilder( '\ApiModuleManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$callback,
			[ $apiModuleManager ]
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
		//	'wgVersion' => '1.24',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		];

		$instance = new Hooks( 'foo' );
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
		//	'wgVersion' => '1.24',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		];

		$instance = new Hooks( 'foo' );
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
			[ 'callBaseTemplateToolbox' ],
			[ 'callSkinAfterContent' ],
			[ 'callOutputPageParserOutput' ],
			[ 'callBeforePageDisplay' ],
			[ 'callSpecialSearchResultsPrepend' ],
			[ 'callSpecialSearchProfiles' ],
			[ 'callSpecialSearchProfileForm' ],
			[ 'callInternalParseBeforeLinks' ],
			[ 'callNewRevisionFromEditComplete' ],
			[ 'callTitleMoveComplete' ],
			[ 'callArticleProtectComplete' ],
			[ 'callArticleViewHeader' ],
			[ 'callArticlePurge' ],
			[ 'callArticleDelete' ],
			[ 'callLinksUpdateConstructed' ],
			[ 'callSpecialStatsAddExtra' ],
			[ 'callFileUpload' ],
			[ 'callMaintenanceUpdateAddParams' ],
			[ 'callResourceLoaderGetConfigVars' ],
			[ 'callGetPreferences' ],
			[ 'callPersonalUrls' ],
			[ 'callSkinTemplateNavigation' ],
			[ 'callLoadExtensionSchemaUpdates' ],
			[ 'callResourceLoaderTestModules' ],
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
			[ 'callSMWSQLStoreEntityReferenceCleanUpComplete' ],
			[ 'callSMWAdminTaskHandlerFactory' ],
			[ 'callSMWApiAddTasks' ],
			[ 'callSMWEventRegisterEventListeners' ],
			[ 'callSMWSQLStorAfterDataUpdateComplete' ],
			[ 'callSMWStoreBeforeQueryResultLookupComplete' ],
			[ 'callSMWStoreAfterQueryResultLookupComplete' ],
			[ 'callSMWBrowseAfterIncomingPropertiesLookupComplete' ],
			[ 'callSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate' ],
			[ 'callSMWSQLStoreInstallerAfterCreateTablesComplete' ],
		];
	}

	public function callParserAfterTidy( $instance ) {

		$handler = 'ParserAfterTidy';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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

	public function callBaseTemplateToolbox( $instance ) {

		$handler = 'BaseTemplateToolbox';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$this->skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getSkin' )
			->will( $this->returnValue( $this->skin ) );

		$toolbox = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ $skinTemplate, &$toolbox ]
		);

		return $handler;
	}

	public function callSkinAfterContent( $instance ) {

		$handler = 'SkinAfterContent';

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$this->skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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

	public function callOutputPageParserOutput( $instance ) {

		$handler = 'OutputPageParserOutput';

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->title->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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
			->will( $this->returnValue( true ) );

		$this->outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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
			->will( $this->returnValue( [] ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = $this->getMockBuilder( '\SMW\MediaWiki\Search\Search' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$specialSearch = $this->getMockBuilder( '\SpecialSearch' )
			->disableOriginalConstructor()
			->getMock();

		$specialSearch->expects( $this->any() )
			->method( 'getSearchEngine' )
			->will( $this->returnValue( $searchEngine ) );

		$specialSearch->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$specialSearch->expects( $this->any() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( [] ) );

		$specialSearch->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->requestContext ) );

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
			->will( $this->returnValue( true ) );

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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

	public function callNewRevisionFromEditComplete( $instance ) {

		$handler = 'NewRevisionFromEditComplete';

		$contentHandler = $this->getMockBuilder( '\ContentHandler' )
			->disableOriginalConstructor()
			->getMock();

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->any() )
			->method( 'getContentHandler' )
			->will( $this->returnValue( $contentHandler ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

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

	public function callTitleMoveComplete( $instance ) {

		$handler = 'TitleMoveComplete';

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'deleteSubject' ] )
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $store );

		$oldTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$oldTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_SPECIAL ) );

		$oldTitle->expects( $this->any() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$newTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$newTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_SPECIAL ) );

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

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->any() )
			->method( 'getContentHandler' )
			->will( $this->returnValue( $contentHandler ) );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getRevision' )
			->will( $this->returnValue( $revision ) );

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

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

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$outputDone = '';
		$useParserCache = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$page, &$outputDone, &$useParserCache ]
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
			->will( $this->returnValue( $this->title ) );

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
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [] ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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

	public function callLinksUpdateConstructed( $instance ) {

		$handler = 'LinksUpdateConstructed';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'updateData' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->getOptions()->set( 'smwgSemanticsEnabled', false );

		$this->testEnvironment->registerObject( 'Store', $store );

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_SPECIAL ) );

		$linksUpdate = $this->getMockBuilder( '\LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

		$linksUpdate->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $parserOutput ) );

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

	public function callPersonalUrls( $instance ) {

		$handler = 'PersonalUrls';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$personal_urls = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$personal_urls, $title, $skinTemplate ]
		);

		return $handler;
	}

	public function callSkinTemplateNavigation( $instance ) {

		$handler = 'SkinTemplateNavigation';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

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

	public function callResourceLoaderTestModules( $instance ) {

		$handler = 'ResourceLoaderTestModules';

		$resourceLoader = $this->getMockBuilder( '\ResourceLoader' )
			->disableOriginalConstructor()
			->getMock();

		$testModules = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$testModules, &$resourceLoader ]
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
			->will( $this->returnValue( $this->title ) );

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
			->will( $this->returnValue( $this->title ) );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$this->title, &$article  ]
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
			[ $this->title, &$isMovable  ]
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
			[ $modelId, &$contentHandler  ]
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
			->will( $this->returnValue( $title ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

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
			->will( $this->returnValue( $this->title ) );

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

		$parseOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables', 'getPropertyTableInfoFetcher' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->title ) );

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

		$block = $this->getMockBuilder( '\Block' )
			->disableOriginalConstructor()
			->getMock();

		$block->expects( $this->any() )
			->method( 'getTarget' )
			->will( $this->returnValue( 'Foo' ) );

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

		$block = $this->getMockBuilder( '\Block' )
			->disableOriginalConstructor()
			->getMock();

		$block->expects( $this->any() )
			->method( 'getTarget' )
			->will( $this->returnValue( 'Foo' ) );

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
			->will( $this->returnValue( 'Foo' ) );

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

	public function callSMWAdminTaskHandlerFactory( $instance ) {

		$handler = 'SMW::Admin::TaskHandlerFactory';

		if ( !$instance->getHandlerFor( $handler ) ) {
			return $this->markTestSkipped( "$handler not used" );
		}

		$taskHandlers = [];

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
			[ &$taskHandlers, $store, $outputFormatter, $user ]
		);

		return $handler;
	}

	public function callSMWApiAddTasks( $instance ) {

		$handler = 'SMW::Api::AddTasks';

		if ( !$instance->getHandlerFor( $handler ) ) {
			return $this->markTestSkipped( "$handler not used" );
		}

		$services = [];

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			[ &$services ]
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
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( new \SMW\Options() ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->any() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [] ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( [] ) );

		$changeOp->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( [] ) );

		$changeOp->expects( $this->any() )
			->method( 'getFixedPropertyRecords' )
			->will( $this->returnValue( [] ) );

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
			->setMethods( [ 'warmUpCache' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getPropertyValues' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTableLookup ) );

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
			->setMethods( [ 'exists', 'getId' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getPropertyValues' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->will( $this->returnValue( [] ) );

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

	private function assertThatHookIsExcutable( callable $handler, $arguments ) {
		$this->assertInternalType(
			'boolean',
			call_user_func_array( $handler, $arguments )
		);
	}

}
