<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\DIProperty;
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
	private $handlers = [];

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

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$globalVars = array(
			'IP' => 'bar',
			'wgVersion' => '1.24',
			'wgLang' => $language
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\HookRegistry',
			new HookRegistry( $globalVars, 'foo' )
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

	public function testRegister() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$globalVars = array(
			'IP' => 'bar',
			'wgVersion' => '1.24',
			'wgLang' => $language,
			'smwgEnabledDeferredUpdate' => false
		);

		$instance = new HookRegistry( $globalVars, 'foo' );
		$instance->register();

		$this->doTestExecutionForParserAfterTidy( $instance );
		$this->doTestExecutionForBaseTemplateToolbox( $instance );
		$this->doTestExecutionForSkinAfterContent( $instance );
		$this->doTestExecutionForOutputPageParserOutput( $instance );
		$this->doTestExecutionForBeforePageDisplay( $instance );
		$this->doTestExecutionForSpecialSearchResultsPrepend( $instance );
		$this->doTestExecutionForInternalParseBeforeLinks( $instance );
		$this->doTestExecutionForNewRevisionFromEditComplete( $instance );
		$this->doTestExecutionForTitleMoveComplete( $instance );
		$this->doTestExecutionForArticleProtectComplete( $instance );
		$this->doTestExecutionForArticleViewHeader( $instance );
		$this->doTestExecutionForArticlePurge( $instance );
		$this->doTestExecutionForArticleDelete( $instance );
		$this->doTestExecutionForLinksUpdateConstructed( $instance );
		$this->doTestExecutionForSpecialStatsAddExtra( $instance );
		$this->doTestExecutionForFileUpload( $instance );
		$this->doTestExecutionForResourceLoaderGetConfigVars( $instance );
		$this->doTestExecutionForGetPreferences( $instance );
		$this->doTestExecutionForPersonalUrls( $instance );
		$this->doTestExecutionForSkinTemplateNavigation( $instance );
		$this->doTestExecutionForLoadExtensionSchemaUpdates( $instance );
		$this->doTestExecutionForResourceLoaderTestModules( $instance );
		$this->doTestExecutionForExtensionTypes( $instance );
		$this->doTestExecutionForTitleIsAlwaysKnown( $instance );
		$this->doTestExecutionForBeforeDisplayNoArticleText( $instance );
		$this->doTestExecutionForArticleFromTitle( $instance );
		$this->doTestExecutionForTitleIsMovable( $instance );
		$this->doTestExecutionForEditPageForm( $instance );
		$this->doTestExecutionForParserOptionsRegister( $instance );
		$this->doTestExecutionForParserFirstCallInit( $instance );
		$this->doTestExecutionForTitleQuickPermissions( $instance );
		$this->doTestExecutionForOutputPageCheckLastModified( $instance );
		$this->doTestExecutionForIsFileCacheable( $instance );
		$this->doTestExecutionForRejectParserCacheValue( $instance );
		$this->doTestExecutionForBlockIpComplete( $instance );
		$this->doTestExecutionForUnblockUserComplete( $instance );
		$this->doTestExecutionForUserGroupsChanged( $instance );

		// Usage of registered hooks in/by smw-core
		//$this->doTestExecutionForSMWStoreDropTables( $instance );
		$this->doTestExecutionForSMWSQLStorAfterDataUpdateComplete( $instance );
		$this->doTestExecutionForSMWStoreBeforeQueryResultLookupComplete( $instance );
		$this->doTestExecutionForSMWStoreAfterQueryResultLookupComplete( $instance );
		$this->doTestExecutionForSMWBrowseAfterIncomingPropertiesLookupComplete( $instance );
		$this->doTestExecutionForSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate( $instance );
		$this->doTestExecutionForSMWSQLStoreInstallerAfterCreateTablesComplete( $instance );

		$handlerList = $instance->getHandlerList();

		foreach ( $handlerList as $handler ) {
			$this->assertArrayHasKey( $handler, $this->handlers, "Missing a `$handler` test!" );
		}
	}

	public function doTestExecutionForParserAfterTidy( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForBaseTemplateToolbox( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSkinAfterContent( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForOutputPageParserOutput( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForBeforePageDisplay( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSpecialSearchResultsPrepend( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForInternalParseBeforeLinks( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForNewRevisionFromEditComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForTitleMoveComplete( $instance ) {

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
		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForArticleProtectComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForArticleViewHeader( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForArticlePurge( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForArticleDelete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForLinksUpdateConstructed( $instance ) {

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
		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSpecialStatsAddExtra( $instance ) {

		$handler = 'SpecialStatsAddExtra';

		$extraStats = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$extraStats )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForFileUpload( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForResourceLoaderGetConfigVars( $instance ) {

		$handler = 'ResourceLoaderGetConfigVars';

		$vars = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$vars )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForGetPreferences( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForPersonalUrls( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSkinTemplateNavigation( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForLoadExtensionSchemaUpdates( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForResourceLoaderTestModules( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForExtensionTypes( $instance ) {

		$handler = 'ExtensionTypes';

		$extTypes = array();

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$extTypes )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForTitleIsAlwaysKnown( $instance ) {

		$handler = 'TitleIsAlwaysKnown';

		$result = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $this->title, &$result )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForBeforeDisplayNoArticleText( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForArticleFromTitle( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForTitleIsMovable( $instance ) {

		$handler = 'TitleIsMovable';

		$isMovable = '';

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $this->title, &$isMovable  )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForEditPageForm( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForParserOptionsRegister( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForParserFirstCallInit( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForTitleQuickPermissions( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForOutputPageCheckLastModified( $instance ) {

		$handler = 'OutputPageCheckLastModified';
		$modifiedTimes = [];

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( &$modifiedTimes )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForIsFileCacheable( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForRejectParserCacheValue( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForBlockIpComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForUnblockUserComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForUserGroupsChanged( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWStoreDropTables( $instance ) {

		$handler = 'SMW::Store::dropTables';

		$verbose = false;

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $verbose )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWSQLStorAfterDataUpdateComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWStoreBeforeQueryResultLookupComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWStoreAfterQueryResultLookupComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWBrowseAfterIncomingPropertiesLookupComplete( $instance ) {

		$handler = 'SMW::Browse::AfterIncomingPropertiesLookupComplete';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists', 'getIDFor' ) )
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

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWBrowseBeforeIncomingPropertyValuesFurtherLinkCreate( $instance ) {

		$handler = 'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate';

		$html = '';
		$property = new DIProperty( 'Foo' );
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->assertTrue(
			$instance->isRegistered( $handler )
		);

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $handler ),
			array( $property, $subject, &$html )
		);

		$this->handlers[$handler] = true;
	}

	public function doTestExecutionForSMWSQLStoreInstallerAfterCreateTablesComplete( $instance ) {

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

		$this->handlers[$handler] = true;
	}

	private function assertThatHookIsExcutable( callable $handler, $arguments ) {
		$this->assertInternalType(
			'boolean',
			call_user_func_array( $handler, $arguments )
		);
	}

}
