<?php

namespace SMW\Test;

use SMW\ExtensionContext;
use SMW\ContentParser;
use SMW\Setup;

use Title;
use Parser;

/**
 * @covers \SMW\AskParserFunction
 * @covers \SMW\ShowParserFunction
 * @covers \SMW\ContentParser
 * @covers \SMW\Setup
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ParserFunctionIntegrationTest extends SemanticMediaWikiTestCase {

	/** @var array */
	private $parserHook = array();

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @return 1.9
	 */
	protected function setUp() {
		$this->removeParserHookRegistrationBeforeTest();
	}

	/**
	 * @return 1.9
	 */
	protected function tearDown() {
		$this->restoreParserHookRegistrationAfterTest();
	}

	/**
	 * In order for the test not being influenced by an exisiting setup
	 * registration we remove the configuration from the GLOBALS temporary
	 * and enable to assign hook definitions freely during testing
	 *
	 * @return 1.9
	 */
	protected function removeParserHookRegistrationBeforeTest() {
		$this->parserHook = $GLOBALS['wgHooks']['ParserFirstCallInit'];
		$GLOBALS['wgHooks']['ParserFirstCallInit'] = array();
	}

	/**
	 * @return 1.9
	 */
	protected function restoreParserHookRegistrationAfterTest() {
		$GLOBALS['wgHooks']['ParserFirstCallInit'] = $this->parserHook;
	}

	/**
	 * @since 1.9
	 *
	 * @return ExtensionContext
	 */
	private function newExtensionContext( $smwgQEnabled ) {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgCacheType', CACHE_NONE );
		$context->getSettings()->set( 'smwgQEnabled', $smwgQEnabled );

		$context->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		return $context;
	}

	/**
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	private function newContentParser( $smwgQEnabled = true ) {

		$registration = array(
			'wgExtensionAssetsPath' => false,
			'wgResourceModules'     => array(),
			'wgScriptPath' => '/Foo',
			'wgServer'     => 'http://example.org',
			'wgVersion'    => '1.21',
			'wgLang'       => $this->newMockBuilder()->newObject( 'Language' )
		);

		$title = $this->newTitle();

		$setup = new Setup( $registration, 'Foo', $this->newExtensionContext( $smwgQEnabled ) );
		$setup->run();

		$parser = new Parser();

		foreach ( $registration['wgHooks']['ParserFirstCallInit'] as $object ) {
			call_user_func_array( $object, array( &$parser ) );
		}

		return new ContentParser( $title, $parser );
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testParseWithParserFunctionEnabled( $text ) {

		$instance = $this->newContentParser( true );
		$instance->parse( $text );

		$this->assertInstanceAfterParse( $instance );
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testParseWithParserFunctionDisabled( $text ) {

		$instance = $this->newContentParser( false );
		$instance->parse( $text );

		$this->assertInstanceAfterParse( $instance );
	}

	/**
	 * @since 1.9
	 */
	protected function assertInstanceAfterParse( $instance ) {

		$this->assertInstanceOf(
			'ParserOutput',
			$instance->getOutput(),
			'Asserts that a ParserOutput object is available'
			);

		$this->assertInternalType(
			'string',
			$instance->getOutput()->getText(),
			'Asserts that getText() is returning a string'
		);

	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0 AskParserFunction
		$provider[] = array( __METHOD__ . '{{#ask: [[Modification date::+]]|limit=1}}' );

		// #1 ShowParserFunction
		$provider[] = array( __METHOD__ . '{{#show: [[Foo]]|limit=1}}' );

		// #2 SubobjectParserFunction
		$provider[] = array( __METHOD__ . '{{#subobject:|foo=bar|lila=lula,linda,luna|+sep=,}}' );

		// #3 RecurringEventsParserFunction
		$provider[] = array( __METHOD__ . '{{#set_recurring_event:some more tests|property=has date|' .
			'has title=Some recurring title|title2|has group=Events123|Events456|start=June 8, 2010|end=June 8, 2011|' .
			'unit=week|period=1|limit=10|duration=7200|include=March 16, 2010;March 23, 2010|+sep=;|' .
			'exclude=March 15, 2010;March 22, 2010|+sep=;}}' );

		return $provider;
	}

}
