<?php

namespace SMW\Test;

use SMW\Setup;

/**
 * @covers \SMW\Setup
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class SetupTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Setup';
	}

	/**
	 * Helper method that returns a Setup object
	 *
	 * @since 1.9
	 *
	 * @return Setup
	 */
	private function newInstance( &$test = array() ) {
		return new Setup( $test );
	}

	/**
	 * @test Setup::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test Setup::run
	 * @dataProvider functionHooksProvider
	 *
	 * @since 1.9
	 */
	public function testRegisterFunctionHooks( $hook, $setup ) {

		$this->assertHook( $hook, $setup, 1 );

	}

	/**
	 * @test Setup::run
	 * @dataProvider parserHooksProvider
	 *
	 * @since 1.9
	 */
	public function testParserHooks( $hook, $setup ) {

		// 4 because of having hooks registered without using a callback, after
		// all parser hooks being registered using a callback this can be
		// reduced to 1
		$this->assertHook( $hook, $setup, 4 );

		// Verify that registered closures are executable otherwise
		// malicious code could hide if not properly checked
		$result = $this->executeHookOnMock( $hook, $setup['wgHooks'][$hook][0] );

		if ( $result !== null ) {
			$this->assertTrue( $result );
		} else {
			$this->markTestIncomplete( "Test is incomplete because of a missing {$hook} closure verification" );
		}

	}

	/**
	 * Asserts a hook
	 *
	 * @since  1.9
	 *
	 * @param $hook
	 * @param $object
	 */
	private function assertHook( $hook, &$setup, $count ) {

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
	 *
	 * @param $hook
	 * @param $object
	 */
	private function executeHookOnMock( $hook, $object ) {

		$empty = '';

		// Evade execution by setting the title object as isSpecialPage
		// the hook class should always ensure that isSpecialPage is checked
		$title =  $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true
		) );

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $title
		) );

		$parser = $this->newMockBuilder()->newObject( 'Parser', array(
			'getTitle' => $title
		) );

		$linksUpdate = $this->newMockBuilder()->newObject( 'LinksUpdate', array(
			'getTitle' => $title
		) );

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle' => $title
		) );

		$skinTemplate = $this->newMockBuilder()->newObject( 'SkinTemplate', array(
			'getSkin' => $skin
		) );

		switch ( $hook ) {
			case 'BeforePageDisplay':
				$result = $this->callObject( $object, array( &$outputPage, &$skin ) );
				break;
			case 'InternalParseBeforeLinks':
				$result = $this->callObject( $object, array( &$parser, &$empty ) );
				break;
			case 'LinksUpdateConstructed':
				$result = $this->callObject( $object, array( $linksUpdate ) );
				break;
			case 'BaseTemplateToolbox':
				$result = $this->callObject( $object, array( $skinTemplate, &$empty ) );
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
	 * Executes a closure
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	private function callObject( $object, array $arguments ) {
		return is_callable( $object ) ? call_user_func_array( $object, $arguments ) : false;
	}

	/**
	 * @test Setup::run
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
	 * @test Setup::run
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
	 * @test Setup::run
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
	 * @test Setup::run
	 *
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
	 * @test Setup::run
	 *
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
	 * @test Setup::run
	 *
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
	 * @test Setup::run
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
	 * @since 1.9
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
	 * @since 1.9
	 */
	public function jobClassesDataProvider() {

		$provider = array();

		$jobs = array(
			'SMW\UpdateJob',
			'SMWRefreshJob',
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
	 * @since 1.9
	 */
	public function apiModulesDataProvider() {

		$provider = array();

		$modules = array(
			'ask',
			'smwinfo',
			'askargs',
		);

		foreach ( $modules as $module ) {
			$provider[] = array(
				$module,
				array( 'wgAPIModules' => array( $module => '' ) ) );
		}

		return $provider;
	}


	/**
	 * @since 1.9
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
	 * @since 1.9
	 */
	public function functionHooksProvider() {

		$provider = array();

		$hooks = array(
			'LoadExtensionSchemaUpdates',
			'ParserTestTables',
			'AdminLinks',
			'PageSchemasRegisterHandlers',
			'ArticlePurge',
			'ParserAfterTidy',
			'LinksUpdateConstructed',
			'ArticleDelete',
			'TitleMoveComplete',
			'NewRevisionFromEditComplete',
			'InternalParseBeforeLinks',
			'ArticleFromTitle',
			'SkinTemplateNavigation',
			'UnitTestsList',
			'ResourceLoaderTestModules',
			'ResourceLoaderGetConfigVars',
			'SpecialStatsAddExtra',
			'GetPreferences',
			'BeforePageDisplay',
			'TitleIsAlwaysKnown',
			'BeforeDisplayNoArticleText',
			'SkinAfterContent',
			'OutputPageParserOutput',
			'ExtensionTypes',
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
	 * @since 1.9
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

}
