<?php

namespace SMW\Tests\Integration\Parser;

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
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ContentParserParserFunctionIntegrationTest extends \PHPUnit_Framework_TestCase {

	// This is to ensure that the original value is cached since we are unable
	// to inject the setting during testing
	private $parserHook = array();

	protected function setUp() {
		$this->parserHook = $GLOBALS['wgHooks']['ParserFirstCallInit'];
		$GLOBALS['wgHooks']['ParserFirstCallInit'] = array();

		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
		$GLOBALS['wgHooks']['ParserFirstCallInit'] = $this->parserHook;
	}

	/**
	 * @return ExtensionContext
	 */
	private function newExtensionContext( $smwgQEnabled ) {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgCacheType', CACHE_NONE );
		$context->getSettings()->set( 'smwgQEnabled', $smwgQEnabled );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );

		return $context;
	}

	/**
	 * @return ContentParser
	 */
	private function newContentParser( $smwgQEnabled = true ) {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$registration = array(
			'wgExtensionAssetsPath' => false,
			'wgResourceModules'     => array(),
			'wgScriptPath' => '/Foo',
			'wgServer'     => 'http://example.org',
			'wgVersion'    => '1.21',
			'wgLang'       => $language
		);

		$title = Title::newFromText( __METHOD__ );

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
	 */
	public function testParseWithParserFunctionEnabled( $text ) {

		$instance = $this->newContentParser( true );
		$instance->parse( $text );

		$this->assertInstanceAfterParse( $instance );
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testParseWithParserFunctionDisabled( $text ) {

		$instance = $this->newContentParser( false );
		$instance->parse( $text );

		$this->assertInstanceAfterParse( $instance );
	}

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
