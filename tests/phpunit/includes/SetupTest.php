<?php

namespace SMW\Test;

use SMW\ExtensionContext;
use SMW\Setup;

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
	private function newInstance( &$test = array(), $context = null ) {

		if ( $context === null ) {
			$context = $this->newExtensionContext();
		}

		return new Setup( $test, $context );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider functionHooksProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterFunctionHooksWithoutExecution( $hook, $setup ) {

		$this->assertHook( $hook, $setup, 1 );

	}

	/**
	 * @dataProvider functionHookForExecutionProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterFunctionHookWithExecution( $hook, $setup ) {

		$this->assertHook( $hook, $setup, 1 );

		// Verify that registered closures are executable
		$result = $this->executeHookOnMock( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}

	}

	/**
	 * @dataProvider parserHooksProvider
	 *
	 * @since 1.9
	 */
	public function testParserHooksWithExecution( $hook, $setup ) {

		// 4 because of having hooks registered without using a callback, after
		// all parser hooks being registered using a callback this can be
		// reduced to 1
		$this->assertHook( $hook, $setup, 4 );

		// Verify that registered closures are executable
		$result = $this->executeHookOnMock( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}

	}

	/**
	 * @since  1.9
	 */
	private function assertHook( $hook, &$setup, $count ) {

		$mockLang = $this->newMockBuilder()->newObject( 'Language' );

		$setup['wgVersion'] = '1.21';
		$setup['wgLang']    = $mockLang;

		$instance = $this->newInstance( $setup );

		$this->assertCount(
			0,
			$setup['wgHooks'][$hook],
			'Asserts that before run() the entry counts 0'
		);

		$instance->run();

		$this->assertCount(
			$count,
			$setup['wgHooks'][$hook],
			"Asserts that after run() the entry counts {$count}"
		);

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

		$skinTemplate = $this->newMockBuilder()->newObject( 'SkinTemplate', array(
			'getSkin' => $skin
		) );

		$parserOptions = $this->newMockBuilder()->newObject( 'ParserOptions' );

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

		$user = $this->newMockBuilder()->newObject( 'User' );

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
			case 'ArticlePurge':
				$result = $this->callObject( $object, array( &$wikiPage ) );
				break;
			case 'ArticleDelete':
				$result = $this->callObject( $object, array( &$wikiPage, &$user, &$empty, &$empty ) );
				break;
			case 'SpecialStatsAddExtra':
				$result = $this->callObject( $object, array( &$emptyArray ) );
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
	public function testRegisterApiModules( $module, $setup ) {

		$instance = $this->newInstance( $setup );
		$this->assertEmpty( $setup['wgAPIModules'][$module] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgAPIModules'][$module] );
		$this->assertTrue( class_exists( $setup['wgAPIModules'][$module] ) );

	}

	/**
	 * @dataProvider jobClassesDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterJobClasses( $job, $setup ) {

		$instance = $this->newInstance( $setup );
		$this->assertEmpty( $setup['wgJobClasses'][$job] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgJobClasses'][$job] );
		$this->assertTrue( class_exists( $setup['wgJobClasses'][$job] ) );

	}

	/**
	 * @dataProvider messagesFilesDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterMessageFiles( $modules, $setup ) {

		$instance = $this->newInstance( $setup );
		$this->assertEmpty( $setup['wgExtensionMessagesFiles'][$modules] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgExtensionMessagesFiles'][$modules] );
		$this->assertTrue( file_exists( $setup['wgExtensionMessagesFiles'][$modules] ) );

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterRights() {

		$setup['wgAvailableRights'][] = '';
		$setup['wgGroupPermissions']['sysop']['smw-admin'] = '';
		$setup['wgGroupPermissions']['smwadministrator']['smw-admin'] = '';

		$instance = $this->newInstance( $setup );

		foreach ( $setup['wgAvailableRights'] as $value) {
			$this->assertEmpty( $value );
		}

		$this->assertEmpty( $setup['wgGroupPermissions']['sysop']['smw-admin'] );
		$this->assertEmpty( $setup['wgGroupPermissions']['smwadministrator']['smw-admin'] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgAvailableRights'] );
		$this->assertNotEmpty( $setup['wgGroupPermissions']['sysop']['smw-admin'] );
		$this->assertNotEmpty( $setup['wgGroupPermissions']['smwadministrator']['smw-admin'] );

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterParamDefinitions() {

		$setup['wgParamDefinitions']['smwformat'] = '';
		$setup['wgParamDefinitions']['smwsource'] = '';

		$instance = $this->newInstance( $setup );

		$this->assertEmpty( $setup['wgParamDefinitions']['smwformat'] );
		$this->assertEmpty( $setup['wgParamDefinitions']['smwsource'] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgParamDefinitions']['smwformat'] );
		$this->assertNotEmpty( $setup['wgParamDefinitions']['smwsource'] );

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterFooterIcon() {

		$setup['wgFooterIcons']['poweredby']['semanticmediawiki'] = '';

		$instance = $this->newInstance( $setup );

		$this->assertEmpty( $setup['wgFooterIcons']['poweredby']['semanticmediawiki'] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgFooterIcons']['poweredby']['semanticmediawiki'] );

	}

	/**
	 * @dataProvider specialPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterSpecialPages( $special, $setup ) {

		$instance = $this->newInstance( $setup );
		$this->assertEmpty( $setup['wgSpecialPages'][$special] );

		$instance->run();

		$this->assertNotEmpty( $setup['wgSpecialPages'][$special] );
		$this->assertTrue( class_exists( $setup['wgSpecialPages'][$special] ) );

	}

	/**
	 * @return array
	 */
	public function specialPageDataProvider() {

		$provider = array();

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

		foreach ( $specials as $special ) {
			$provider[] = array(
				$special,
				array( 'wgSpecialPages' => array( $special => '' ) ) );
		}

		return $provider;
	}

	/**
	 * @return array
	 */
	public function jobClassesDataProvider() {

		$provider = array();

		$jobs = array(
			'SMW\UpdateJob',
			'SMW\RefreshJob',
			'SMW\UpdateDispatcherJob',
		);

		foreach ( $jobs as $job ) {
			$provider[] = array(
				$job,
				array( 'wgJobClasses' => array( $job => '' ) ) );
		}

		return $provider;
	}

	/**
	 * @return array
	 */
	public function apiModulesDataProvider() {

		$provider = array();

		$modules = array(
			'ask',
			'smwinfo',
			'askargs',
			'browsebysubject',
		);

		foreach ( $modules as $module ) {
			$provider[] = array(
				$module,
				array( 'wgAPIModules' => array( $module => '' ) ) );
		}

		return $provider;
	}


	/**
	 * @return array
	 */
	public function messagesFilesDataProvider() {

		$provider = array();

		$modules = array(
			'SemanticMediaWiki',
			'SemanticMediaWikiAlias',
			'SemanticMediaWikiMagic',
		);

		foreach ( $modules as $module ) {
			$provider[] = array(
				$module,
				array( 'wgExtensionMessagesFiles' => array( $module => '' ) ) );
		}

		return $provider;
	}

	/**
	 * @return array
	 */
	public function functionHooksProvider() {

		$provider = array();

		$hooks = array(
			'LoadExtensionSchemaUpdates',
			'ParserTestTables',
			'AdminLinks',
			'PageSchemasRegisterHandlers',
			'ArticleFromTitle',
			'SkinTemplateNavigation',
			'UnitTestsList',
			'ResourceLoaderTestModules',
			'ResourceLoaderGetConfigVars',
			'GetPreferences',
			'TitleIsAlwaysKnown',
			'BeforeDisplayNoArticleText',
			'ExtensionTypes',
		);

		foreach ( $hooks as $hook ) {
			$provider[] = array(
				$hook,
				array( 'wgHooks' => array( $hook => array() ) ),
			);
		}

		return $provider;
	}

	/**
	 * @return array
	 */
	public function functionHookForExecutionProvider() {

		$provider = array();

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
			'BaseTemplateToolbox'
		);

		foreach ( $hooks as $hook ) {
			$provider[] = array(
				$hook,
				array( 'wgHooks' => array( $hook => array() ) ),
			);
		}

		return $provider;
	}

	/**
	 * @return array
	 */
	public function parserHooksProvider() {

		$provider = array();

		$hooks = array(
			'ParserFirstCallInit'
		);

		foreach ( $hooks as $hook ) {
			$provider[] = array(
				$hook,
				array( 'wgHooks' => array( $hook => array() ) ),
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
