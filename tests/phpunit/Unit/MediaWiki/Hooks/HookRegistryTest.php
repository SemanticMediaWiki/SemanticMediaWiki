<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\HookRegistry;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\HookRegistry
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HookRegistryTest extends \PHPUnit_Framework_TestCase {

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
			HookRegistry::class,
			new HookRegistry( $vars, 'foo' )
		);
	}

	public function testInitExtension() {

		$vars = [];

		HookRegistry::initExtension( $vars );

		// CanonicalNamespaces
		$callback = end( $vars['wgHooks']['CanonicalNamespaces'] );
		$namespaces = [];

		$this->assertThatHookIsExcutable(
			$callback,
			array( &$namespaces )
		);

		// SpecialPage_initList
		$callback = end( $vars['wgHooks']['SpecialPage_initList'] );
		$specialPages = [];

		$this->assertThatHookIsExcutable(
			$callback,
			array( &$specialPages )
		);

		// ApiMain::moduleManager
		$callback = end( $vars['wgHooks']['ApiMain::moduleManager'] );

		$apiModuleManager = $this->getMockBuilder( '\ApiModuleManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$callback,
			array( $apiModuleManager )
		);
	}

	/**
	 * @dataProvider callMethodProvider
	 */
	public function testRegister( $method ) {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$vars = array(
			'IP' => 'bar',
			'wgVersion' => '1.24',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		);

		$instance = new HookRegistry( $vars, 'foo' );
		$instance->register();

		self::$handlers[] = call_user_func_array( [ $this, $method ], [ $instance ] );
	}

    /**
     * @depends testRegister
     */
	public function testCheckOnMissingHandlers() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$vars = array(
			'IP' => 'bar',
			'wgVersion' => '1.24',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		);

		$instance = new HookRegistry( $vars, 'foo' );
		$instance->register();

		$handlerList = $instance->getHandlerList();
		$handlers = array_flip( self::$handlers );

		foreach ( $handlerList as $handler ) {
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
			[ 'callEditPageForm' ],
			[ 'callParserOptionsRegister' ],
			[ 'callParserFirstCallInit' ],
			[ 'callTitleQuickPermissions' ],
			[ 'callOutputPageCheckLastModified' ],
			[ 'callIsFileCacheable' ],
			[ 'callRejectParserCacheValue' ],
			[ 'callSoftwareInfo' ],
			[ 'callBlockIpComplete' ],
			[ 'callUnblockUserComplete' ],
			[ 'callUserGroupsChanged' ],
			[ 'callSMWSQLStoreEntityReferenceCleanUpComplete' ],
			[ 'callSMWAdminTaskHandlerFactory' ],
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
			array( &$this->parser, &$text )
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
			array( $skinTemplate, &$toolbox )
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
			array( &$data, $this->skin )
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
			array( &$this->outputPage, $parserOutput )
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
			array( &$this->outputPage, &$this->skin )
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
			array( $specialSearch, $this->outputPage, '' )
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
			array( &$parser, &$text, &$stripState )
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
			array( $wikiPage, $revision, $baseId, $user )
		);

		return $handler;
	}

	public function callTitleMoveComplete( $instance ) {

		$handler = 'TitleMoveComplete';

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'deleteSubject' ) )
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
			array( &$oldTitle, &$newTitle, &$user, $oldId, $newId )
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

		$protections = array();
		$reason = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$wikiPage, &$user, $protections, $reason )
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
			array( &$page, &$outputDone, &$useParserCache )
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
			array( &$wikiPage )
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
			->will( $this->returnValue( array() ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( array() ) );

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
			array( &$wikiPage, &$user, &$reason, &$error )
		);

		return $handler;
	}

	public function callLinksUpdateConstructed( $instance ) {

		$handler = 'LinksUpdateConstructed';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'updateData' ) )
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
			array( $linksUpdate )
		);

		$this->testEnvironment->registerObject( 'Store', $this->store );
		return $handler;
	}

	public function callSpecialStatsAddExtra( $instance ) {

		$handler = 'SpecialStatsAddExtra';

		$extraStats = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$extraStats )
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
			array( $file, $reupload )
		);

		return $handler;
	}

	public function callResourceLoaderGetConfigVars( $instance ) {

		$handler = 'ResourceLoaderGetConfigVars';

		$vars = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$vars )
		);

		return $handler;
	}

	public function callGetPreferences( $instance ) {

		$handler = 'GetPreferences';

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $user, &$preferences )
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
			array( &$personal_urls, $title, $skinTemplate )
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

		$links = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$skinTemplate, &$links )
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
			array( $databaseUpdater )
		);

		return $handler;
	}

	public function callResourceLoaderTestModules( $instance ) {

		$handler = 'ResourceLoaderTestModules';

		$resourceLoader = $this->getMockBuilder( '\ResourceLoader' )
			->disableOriginalConstructor()
			->getMock();

		$testModules = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$testModules, &$resourceLoader )
		);

		return $handler;
	}

	public function callExtensionTypes( $instance ) {

		$handler = 'ExtensionTypes';

		$extTypes = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$extTypes )
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
			array( $this->title, &$result )
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
			array( $article )
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
			array( &$this->title, &$article  )
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
			array( $this->title, &$isMovable  )
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
			array( $editPage, $outputPage )
		);

		return $handler;
	}

	public function callParserOptionsRegister( $instance ) {

		$handler = 'ParserOptionsRegister';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$defaults = array();
		$inCacheKey = array();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$defaults, &$inCacheKey )
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
			array( &$parser )
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
		$errors = array();
		$rigor = '';
		$short = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $title, $user, $action, &$errors, $rigor, $short )
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
			array( &$modifiedTimes )
		);

		return $handler;
	}

	public function callIsFileCacheable( $instance ) {

		$handler = 'IsFileCacheable';

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
			array( &$article )
		);

		return $handler;
	}

	public function callRejectParserCacheValue( $instance ) {

		$handler = 'RejectParserCacheValue';

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
		$popts = '';

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $value, $wikiPage, $popts )
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
			array( $block, $performer, $priorBlock )
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
			array( $block, $performer, $priorBlock )
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
			array( $user )
		);

		return $handler;
	}

	public function callSMWStoreDropTables( $instance ) {

		$handler = 'SMW::Store::dropTables';

		$verbose = false;

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $verbose )
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
			array( $this->store, $id, $subject, $isRedirect )
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
			array( &$taskHandlers, $store, $outputFormatter, $user )
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
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->any() )
			->method( 'getOrderedDiffByTable' )
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->any() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->any() )
			->method( 'getFixedPropertyRecords' )
			->will( $this->returnValue( array() ) );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $store, $semanticData, $changeOp )
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
			array( $this->store, $query, &$result, $queryEngine )
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
			array( $store, &$result )
		);

		return $handler;
	}

	public function callSMWBrowseAfterIncomingPropertiesLookupComplete( $instance ) {

		$handler = 'SMW::Browse::AfterIncomingPropertiesLookupComplete';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists', 'getId' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getPropertyValues' ) )
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
			array( $store, $semanticData, $requestOptions )
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
			array( $property, $subject, &$html, $this->store )
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
			array( $tableBuilder, $messageReporter, $options )
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
