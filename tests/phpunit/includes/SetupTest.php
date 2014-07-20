<?php

namespace SMW\Test;

use SMW\Setup;
use SMW\ExtensionContext;

/**
 * @covers \SMW\Setup
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SetupTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Setup';
	}

	/**
	 * @since 1.9
	 */
	private function newExtensionContext( $store = null ) {

		$context = new ExtensionContext();

		$settings = $context->getSettings();
		$settings->set( 'smwgCacheType', CACHE_NONE );
		$settings->set( 'smwgEnableUpdateJobs', false );

		$context->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		return $context;
	}

	/**
	 * @since 1.9
	 *
	 * @return Setup
	 */
	private function newInstance( &$config = array(), $basePath = 'Foo', $context = null ) {

		$language = $this->newMockBuilder()->newObject( 'Language' );

		$default  = array(
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

		$config  = array_merge( $default, $config );

		if( $context === null ) {
			$context = $this->newExtensionContext();
		}

		return new Setup( $config, $basePath, $context );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testResourceModules() {

		$config   = array();
		$context  = $this->newExtensionContext();
		$basepath = $context->getSettings()->get( 'smwgIP' );

		$this->newInstance( $config, $basepath, $context )->run();
		$this->assertNotEmpty( $config['wgResourceModules'] );

	}

	/**
	 * @dataProvider functionHooksProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterFunctionHooksWithoutInitialization( $hook, $setup ) {
		$this->assertArrayHookEntry( $hook, $setup, 1 );
	}

	/**
	 * @dataProvider functionHookForInitializationProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterFunctionHookWithInitialization( $hook, $setup ) {

		$this->assertArrayHookEntry( $hook, $setup, 1 );

		// Verify that registered closures are executable
		$result = $this->executeHookOnMock( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}

	}

	/**
	 * @dataProvider parserHooksForInitializationProvider
	 *
	 * @since 1.9
	 */
	public function testParserHooksWithInitialization( $hook, $setup ) {

		// 4 because of having hooks registered without using a callback, after
		// all parser hooks being registered using a callback this can be
		// reduced to 1
		$this->assertArrayHookEntry( $hook, $setup, 3 );

		// Verify that registered closures are executable
		$result = $this->executeHookOnMock( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}

	}

	/**
	 * Verifies that a registered closure can be executed
	 *
	 * @since  1.9
	 */
	private function executeHookOnMock( $hook, $object ) {

		$empty = '';
		$emptyArray = array();

		$editInfo = (object)array();
		$editInfo->output = null;

		// Evade execution by setting the title object as isSpecialPage
		// the hook class should always ensure that isSpecialPage is checked
		$title =  $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true
		) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'isAllowed' )
			->will( $this->returnValue( false ) );

		$parserOutput = $this->newMockBuilder()->newObject( 'ParserOutput' );

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $title
		) );

		$parser = $this->newMockBuilder()->newObject( 'Parser', array(
			'getTitle' => $title
		) );

		$linksUpdate = $this->newMockBuilder()->newObject( 'LinksUpdate', array(
			'getTitle'        => $title,
			'getParserOutput' => $parserOutput
		) );

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'  => $title,
			'getOutput' => $outputPage
		) );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getSkin' )
			->will( $this->returnValue( $skin ) );

		$skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$parserOptions = $this->newMockBuilder()->newObject( 'ParserOptions' );

		$file = $this->newMockBuilder()->newObject( 'File', array(
			'getTitle' => null
		) );

		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'prepareContentForEdit' => $editInfo,
			'prepareTextForEdit'    => $editInfo,
			'getTitle' => $title,
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
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

	/**
	 * @since  1.9
	 *
	 * @return boolean
	 */
	private function callObject( $object, array $arguments ) {
		return is_callable( $object ) ? call_user_func_array( $object, $arguments ) : false;
	}

	/**
	 * @dataProvider apiModulesDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterApiModules( $moduleEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgAPIModules', $moduleEntry, $setup );
	}

	/**
	 * @dataProvider jobClassesDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterJobClasses( $jobEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgJobClasses', $jobEntry, $setup );
	}

	/**
	 * @dataProvider messagesFilesDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterMessageFiles( $moduleEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgExtensionMessagesFiles', $moduleEntry, $setup, 'file' );
	}

	/**
	 * @dataProvider specialPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterSpecialPages( $specialEntry, $setup ) {
		$this->assertArrayEntryExists( 'wgSpecialPages', $specialEntry, $setup );
	}

	/**
	 * @since 1.9
	 */
	public function testRegisterRights() {

		$setup['wgAvailableRights'][] = '';
		$setup['wgGroupPermissions']['sysop']['smw-admin'] = '';
		$setup['wgGroupPermissions']['smwadministrator']['smw-admin'] = '';

		foreach ( $setup['wgAvailableRights'] as $value ) {
			$this->assertEmpty( $value );
		}

		$this->assertEmpty( $setup['wgGroupPermissions']['sysop']['smw-admin'] );
		$this->assertEmpty( $setup['wgGroupPermissions']['smwadministrator']['smw-admin'] );

		$this->newInstance( $setup )->run();

		$this->assertNotEmpty( $setup['wgAvailableRights'] );
		$this->assertNotEmpty( $setup['wgGroupPermissions']['sysop']['smw-admin'] );
		$this->assertNotEmpty( $setup['wgGroupPermissions']['smwadministrator']['smw-admin'] );

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterParamDefinitions() {

		$setup['wgParamDefinitions']['smwformat'] = '';

		$this->assertEmpty( $setup['wgParamDefinitions']['smwformat'] );

		$this->newInstance( $setup )->run();

		$this->assertNotEmpty( $setup['wgParamDefinitions']['smwformat'] );

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterFooterIcon() {

		$setup['wgFooterIcons']['poweredby']['semanticmediawiki'] = '';

		$this->newInstance( $setup )->run();
		$this->assertNotEmpty( $setup['wgFooterIcons']['poweredby']['semanticmediawiki'] );

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

	/**
	 * @return array
	 */
	public function parserHooksForInitializationProvider() {

		$hooks = array(
			'ParserFirstCallInit'
		);

		return $this->buildDataProvider( 'wgHooks', $hooks, array() );
	}

	/**
	 * @since  1.9
	 */
	private function assertArrayHookEntry( $hook, &$setup, $expectedCount ) {

		$this->assertCount(
			0,
			$setup['wgHooks'][$hook],
			'Asserts that before run() the entry counts 0'
		);

		$this->newInstance( $setup )->run();

		$this->assertCount(
			$expectedCount,
			$setup['wgHooks'][$hook],
			"Asserts that after run() the entry counts {$expectedCount}"
		);

	}

	/**
	 * @since 1.9
	 */
	private function assertArrayEntryExists( $target, $entry, $setup, $type = 'class' ) {

		$this->assertEmpty(
			$setup[$target][$entry],
			"Asserts that {$entry} is empty"
		);

		$this->newInstance( $setup )->run();

		$this->assertNotEmpty( $setup[$target][$entry] );

		switch ( $type ) {
			case 'class':
				$this->assertTrue( class_exists( $setup[$target][$entry] ) );
				break;
			case 'file':
				$this->assertTrue( file_exists( $setup[$target][$entry] ) );
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

			$contentHandler = $this->newMockBuilder()->newObject( 'ContentHandler', array(
				'getDefaultFormat' => 'Foo'
			) );

			$content = $this->newMockBuilder()->newObject( 'Content', array(
				'getContentHandler' => $contentHandler,
			) );
		}

		return $content;
	}

}
