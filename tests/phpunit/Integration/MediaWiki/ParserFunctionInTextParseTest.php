<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\ParserFactory;

use SMW\ExtensionContext;
use SMW\ContentParser;
use SMW\Setup;

use Title;

/**
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
class ParserFunctionInTextParseTest extends \PHPUnit_Framework_TestCase {

	private $parserFirstCallInit = array();

	protected function setUp() {
		parent::setUp();

		$this->parserFirstCallInit = $GLOBALS['wgHooks']['ParserFirstCallInit'];
		$GLOBALS['wgHooks']['ParserFirstCallInit'] = array();
	}

	protected function tearDown() {
		$GLOBALS['wgHooks']['ParserFirstCallInit'] = $this->parserFirstCallInit;

		parent::tearDown();
	}

	private function newContentParser( $smwgQEnabled = true ) {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgCacheType', CACHE_NONE );
		$context->getSettings()->set( 'smwgQEnabled', $smwgQEnabled );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$context
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Store', $store );

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$registration = array(
			'wgExtensionAssetsPath' => false,
			'wgResourceModules'     => array(),
			'wgScriptPath' => '/Foo',
			'wgServer'     => 'http://example.org',
			'wgVersion'    => '1.21',
			'wgLang'       => $language,
			'IP'           => 'Foo'
		);

		$title = Title::newFromText( __METHOD__ );

		$setup = new Setup( $registration, 'Foo', $context );
		$setup->run();

		$parser = ParserFactory::newFromTitle( $title );

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
			$instance->getOutput()
		);

		$this->assertInternalType(
			'string',
			$instance->getOutput()->getText()
		);
	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		#0 ask
		$provider[] = array( '{{#ask: [[Modification date::+]]|limit=1}}' );

		#1 show
		$provider[] = array( '{{#show: [[Foo]]|limit=1}}' );

		#2 subobject
		$provider[] = array( '{{#subobject:|foo=bar|lila=lula,linda,luna|+sep=,}}' );

		#3 set
		$provider[] = array( '{{#set:|foo=bar|lila=lula,linda,luna|+sep=,}}' );

		#4 set_recurring_event
		$provider[] = array( '{{#set_recurring_event:some more tests|property=has date|' .
			'has title=Some recurring title|title2|has group=Events123|Events456|start=June 8, 2010|end=June 8, 2011|' .
			'unit=week|period=1|limit=10|duration=7200|include=March 16, 2010;March 23, 2010|+sep=;|' .
			'exclude=March 15, 2010;March 22, 2010|+sep=;}}' );

		#5 declare
		$provider[] = array( '{{#declare:population=pop}}' );

		#6 concept
		$provider[] = array( '{{#concept:[[Modification date::+]]|Foo}}' );

		return $provider;
	}

}
