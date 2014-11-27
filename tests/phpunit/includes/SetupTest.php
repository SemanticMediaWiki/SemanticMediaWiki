<?php

namespace SMW\Tests;

use SMW\Tests\Utils\Mock\MockObjectBuilder;
use SMW\Tests\Utils\Mock\CoreMockObjectRepository;
use SMW\Tests\Utils\Mock\MediaWikiMockObjectRepository;

use SMW\Setup;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\Setup
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SetupTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $defaultConfig;
	private $mockbuilder;

	protected function setUp() {
		parent::setUp();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array() ) );

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$this->defaultConfig = array(
			'smwgCacheType' => CACHE_NONE,
			'smwgNamespacesWithSemanticLinks' => array(),
			'smwgEnableUpdateJobs' => false,
			'wgNamespacesWithSubpages' => array(),
			'wgExtensionAssetsPath'    => false,
			'wgResourceModules' => array(),
			'wgScriptPath'      => '/Foo',
			'wgServer'          => 'http://example.org',
			'wgVersion'         => '1.21',
			'wgLanguageCode'    => 'en',
			'wgLang'            => $language,
			'IP'                => 'Foo'
		);

		foreach ( $this->defaultConfig as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		// This needs to be fixed but not now
		$this->mockbuilder = new MockObjectBuilder();
		$this->mockbuilder->registerRepository( new CoreMockObjectRepository() );
		$this->mockbuilder->registerRepository( new MediaWikiMockObjectRepository() );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$applicationFactory = $this->getMockBuilder( '\SMW\ApplicationFactory' )
			->disableOriginalConstructor()
			->getMock();

		$config = array();
		$basepath = 'Foo';

		$this->assertInstanceOf(
			'\SMW\Setup',
			new Setup( $applicationFactory, $config, $basepath )
		);
	}

	public function testResourceModules() {

		$config   = $this->defaultConfig;
		$basepath = $this->applicationFactory->getSettings()->get( 'smwgIP' );

		$instance = new Setup( $this->applicationFactory, $config, $basepath );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgResourceModules']
		);
	}

	/**
	 * @dataProvider functionHooksProvider
	 */
	public function testRegisterFunctionHooksWithoutInitialization( $hook, $setup ) {
		$this->assertArrayHookEntry( $hook, $setup, 1 );
	}

	/**
	 * @dataProvider functionHookForInitializationProvider
	 */
	public function testRegisterFunctionHookWithInitialization( $hook, $setup ) {

		$this->assertArrayHookEntry( $hook, $setup, 1 );

		// Verify that registered closures are executable
		$result = $this->tryToExecuteHook( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}
	}

	/**
	 * @dataProvider parserHooksForInitializationProvider
	 */
	public function testParserHooksWithInitialization( $hook, $setup ) {

		// 4 because of having hooks registered without using a callback, after
		// all parser hooks being registered using a callback this can be
		// reduced to 1
		$this->assertArrayHookEntry( $hook, $setup, 3 );

		// Verify that registered closures are executable
		$result = $this->tryToExecuteHook( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}
	}

	private function tryToExecuteHook( $hook, $object ) {

		$empty = '';
		$emptyArray = array();

		$editInfo = (object)array();
		$editInfo->output = null;

		// Evade execution by setting the title object as isSpecialPage
		// the hook class should always ensure that isSpecialPage is checked
		$title =  $this->mockbuilder->newObject( 'Title', array(
			'isSpecialPage' => true
		) );

		$webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$requestContext = $this->getMockBuilder( '\RequestContext' )
			->disableOriginalConstructor()
			->getMock();

		$requestContext->expects( $this->any() )
			->method( 'getRequest' )
			->will( $this->returnValue( $webRequest ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'isAllowed' )
			->will( $this->returnValue( false ) );

		$parserOutput = $this->mockbuilder->newObject( 'ParserOutput' );

		$outputPage = $this->mockbuilder->newObject( 'OutputPage', array(
			'getTitle' => $title
		) );

		$parser = $this->mockbuilder->newObject( 'Parser', array(
			'getTitle' => $title
		) );

		$linksUpdate = $this->mockbuilder->newObject( 'LinksUpdate', array(
			'getTitle'        => $title,
			'getParserOutput' => $parserOutput
		) );

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $outputPage ) );

		$skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $requestContext ) );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$parserOptions = $this->mockbuilder->newObject( 'ParserOptions' );

		$file = $this->mockbuilder->newObject( 'File', array(
			'getTitle' => null
		) );

		$wikiPage = $this->mockbuilder->newObject( 'WikiPage', array(
			'prepareContentForEdit' => $editInfo,
			'prepareTextForEdit'    => $editInfo,
			'getTitle' => $title,
		) );

		$revision = $this->mockbuilder->newObject( 'Revision', array(
			'getTitle'   => $title,
			'getRawText' => 'Foo',
			'getContent' => $this->newMockContent()
		) );

		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$resourceLoader = $this->getMockBuilder( '\ResourceLoader' )
			->disableOriginalConstructor()
			->getMock();

		switch ( $hook ) {
			case 'SkinAfterContent':
				$result = $this->callObject( $object, array( &$empty, $skin ) );
				break;
			case 'OutputPageParserOutput':
				$result = $this->callObject( $object, array( &$outputPage, $parserOutput ) );
				break;
			case 'BeforePageDisplay':
				$result = $this->callObject( $object, array( &$outputPage, &$skin ) );
				break;
			case 'InternalParseBeforeLinks':
				$result = $this->callObject( $object, array( &$parser, &$empty ) );
				break;
			case 'ParserAfterTidy':
				$result = $this->callObject( $object, array( &$parser, &$empty ) );
				break;
			case 'LinksUpdateConstructed':
				$result = $this->callObject( $object, array( $linksUpdate ) );
				break;
			case 'BaseTemplateToolbox':
				$result = $this->callObject( $object, array( $skinTemplate, &$empty ) );
				break;
			case 'NewRevisionFromEditComplete':
				$result = $this->callObject( $object, array( $wikiPage, $revision, $empty, $user ) );
				break;
			case 'TitleMoveComplete':
				$result = $this->callObject( $object, array( &$title, &$title, &$user, $empty, $empty ) );
				break;
			case 'CanonicalNamespaces':
				$result = $this->callObject( $object, array( &$emptyArray ) );
				break;
			case 'ArticlePurge':
				$result = $this->callObject( $object, array( &$wikiPage ) );
				break;
			case 'ArticleDelete':
				$result = $this->callObject( $object, array( &$wikiPage, &$user, &$empty, &$empty ) );
				break;
			case 'SpecialStatsAddExtra':
				$result = $this->callObject( $object, array( &$emptyArray ) );
				break;
			case 'FileUpload':
				$result = $this->callObject( $object, array( $file, $empty ) );
				break;
			case 'ResourceLoaderGetConfigVars':
				$result = $this->callObject( $object, array( &$emptyArray ) );
				break;
			case 'GetPreferences':
				$result = $this->callObject( $object, array( $user, &$emptyArray ) );
				break;
			case 'SkinTemplateNavigation':
				$result = $this->callObject( $object, array( &$skinTemplate, &$emptyArray ) );
				break;
			case 'LoadExtensionSchemaUpdates':
				$result = $this->callObject( $object, array( $databaseUpdater ) );
				break;
			case 'ResourceLoaderTestModules':
				$result = $this->callObject( $object, array( &$emptyArray, &$resourceLoader ) );
				break;
			case 'ExtensionTypes':
				$result = $this->callObject( $object, array( &$emptyArray ) );
				break;
			case 'TitleIsAlwaysKnown':
				$result = $this->callObject( $object, array( $title, &$empty ) );
				break;
			case 'BeforeDisplayNoArticleText':
				$result = $this->callObject( $object, array( $wikiPage ) );
				break;
			case 'ArticleFromTitle':
				$result = $this->callObject( $object, array( &$title, &$wikiPage ) );
				break;
			case 'ParserFirstCallInit':

				// ParserFirstCallInit itself contains closures for
				// registered parser functions that are not checked here
				// @see ParserFunctionIntegrationTest

				$result = $this->callObject( $object, array( &$parser ) );
				break;
			default:
				$result = null;
		}

		return $result;
	}

	private function callObject( $object, array $arguments ) {
		return is_callable( $object ) ? call_user_func_array( $object, $arguments ) : false;
	}

	/**
	 * @dataProvider apiModulesDataProvider
	 */
	public function testRegisterApiModules( $moduleEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgAPIModules', $moduleEntry, $setup );
	}

	/**
	 * @dataProvider jobClassesDataProvider
	 */
	public function testRegisterJobClasses( $jobEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgJobClasses', $jobEntry, $setup );
	}

	/**
	 * @dataProvider messagesFilesDataProvider
	 */
	public function testRegisterMessageFiles( $moduleEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgExtensionMessagesFiles', $moduleEntry, $setup, 'file' );
	}

	/**
	 * @dataProvider specialPageDataProvider
	 */
	public function testRegisterSpecialPages( $specialEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgSpecialPages', $specialEntry, $setup );
	}

	public function testRegisterRights() {

		$config['wgAvailableRights'][] = '';
		$config['wgGroupPermissions']['sysop']['smw-admin'] = '';
		$config['wgGroupPermissions']['smwadministrator']['smw-admin'] = '';

		foreach ( $config['wgAvailableRights'] as $value ) {
			$this->assertEmpty( $value );
		}

		$this->assertEmpty( $config['wgGroupPermissions']['sysop']['smw-admin'] );
		$this->assertEmpty( $config['wgGroupPermissions']['smwadministrator']['smw-admin'] );

		$config = $this->defaultConfig;

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgAvailableRights']
		);

		$this->assertNotEmpty(
			$config['wgGroupPermissions']['sysop']['smw-admin']
		);

		$this->assertNotEmpty(
			$config['wgGroupPermissions']['smwadministrator']['smw-admin']
		);
	}

	public function testRegisterParamDefinitions() {

		$config = $this->defaultConfig;

		$config['wgParamDefinitions']['smwformat'] = '';

		$this->assertEmpty(
			$config['wgParamDefinitions']['smwformat']
		);

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgParamDefinitions']['smwformat']
		);
	}

	public function testRegisterFooterIcon() {

		$config = $this->defaultConfig;

		$config['wgFooterIcons']['poweredby']['semanticmediawiki'] = '';

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty(
			$config['wgFooterIcons']['poweredby']['semanticmediawiki']
		);
	}

	/**
	 * @return array
	 */
	public function specialPageDataProvider() {

		$specials = array(
			'Ask',
			'Browse',
			'PageProperty',
			'SearchByProperty',
			'SMWAdmin',
			'SemanticStatistics',
			'Concepts',
			'ExportRDF',
			'Types',
			'URIResolver',
			'Properties',
			'UnusedProperties',
			'WantedProperties',
		);

		return $this->buildDataProvider( 'wgSpecialPages', $specials, '' );
	}

	/**
	 * @return array
	 */
	public function jobClassesDataProvider() {

		$jobs = array(
			'SMW\UpdateJob',
			'SMW\RefreshJob',
			'SMW\UpdateDispatcherJob',
			'SMW\DeleteSubjectJob',

			// Legacy
			'SMWUpdateJob',
			'SMWRefreshJob',
		);

		return $this->buildDataProvider( 'wgJobClasses', $jobs, '' );
	}

	/**
	 * @return array
	 */
	public function apiModulesDataProvider() {

		$modules = array(
			'ask',
			'smwinfo',
			'askargs',
			'browsebysubject',
		);

		return $this->buildDataProvider( 'wgAPIModules', $modules, '' );
	}


	/**
	 * @return array
	 */
	public function messagesFilesDataProvider() {

		$modules = array(
			'SemanticMediaWiki',
			'SemanticMediaWikiAlias',
			'SemanticMediaWikiMagic',
			'SemanticMediaWikiNamespaces'
		);

		return $this->buildDataProvider( 'wgExtensionMessagesFiles', $modules, '' );
	}

	/**
	 * @return array
	 */
	public function functionHooksProvider() {

		$hooks = array(
			'AdminLinks',
			'PageSchemasRegisterHandlers',
		);

		return $this->buildDataProvider( 'wgHooks', $hooks, array() );
	}

	/**
	 * @return array
	 */
	public function functionHookForInitializationProvider() {

		$hooks = array(
			'SkinAfterContent',
			'OutputPageParserOutput',
			'BeforePageDisplay',
			'InternalParseBeforeLinks',
			'TitleMoveComplete',
			'NewRevisionFromEditComplete',
			'ArticlePurge',
			'ArticleDelete',
			'ParserAfterTidy',
			'LinksUpdateConstructed',
			'SpecialStatsAddExtra',
			'BaseTemplateToolbox',
			'CanonicalNamespaces',
			'FileUpload',
			'ResourceLoaderGetConfigVars',
			'GetPreferences',
			'SkinTemplateNavigation',
			'LoadExtensionSchemaUpdates',
			'ResourceLoaderTestModules',
			'ExtensionTypes',
			'TitleIsAlwaysKnown',
			'BeforeDisplayNoArticleText',
			'ArticleFromTitle',
		);

		return $this->buildDataProvider( 'wgHooks', $hooks, array() );
	}

	public function parserHooksForInitializationProvider() {

		$hooks = array(
			'ParserFirstCallInit'
		);

		return $this->buildDataProvider( 'wgHooks', $hooks, array() );
	}

	private function assertArrayHookEntry( $hook, &$config, $expectedCount ) {

		$config = $config + $this->defaultConfig;

		$this->assertCount(
			0,
			$config['wgHooks'][$hook],
			'Asserts that before run() the entry counts 0'
		);

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertCount(
			$expectedCount,
			$config['wgHooks'][$hook],
			"Asserts that after run() the entry counts {$expectedCount}"
		);
	}

	private function assertArrayEntryExists( $target, $entry, $config, $type = 'class' ) {

		$config = $config + $this->defaultConfig;

		$this->assertEmpty(
			$config[$target][$entry],
			"Asserts that {$entry} is empty"
		);

		$instance = new Setup( $this->applicationFactory, $config, 'Foo' );
		$instance->run();

		$this->assertNotEmpty( $config[$target][$entry] );

		switch ( $type ) {
			case 'class':
				$this->assertTrue( class_exists( $config[$target][$entry] ) );
				break;
			case 'file':
				$this->assertTrue( file_exists( $config[$target][$entry] ) );
				break;
		}
	}

	/**
	 * @return array
	 */
	private function buildDataProvider( $id, $definitions, $default ) {

		$provider = array();

		foreach ( $definitions as $definition ) {
			$provider[] = array(
				$definition,
				array( $id => array( $definition => $default ) ),
			);
		}

		return $provider;
	}

	/**
	 * @return Content|null
	 */
	public function newMockContent() {

		$content = null;

		if ( class_exists( 'ContentHandler' ) ) {

			$contentHandler = $this->mockbuilder->newObject( 'ContentHandler', array(
				'getDefaultFormat' => 'Foo'
			) );

			$content = $this->mockbuilder->newObject( 'Content', array(
				'getContentHandler' => $contentHandler,
			) );
		}

		return $content;
	}

}
