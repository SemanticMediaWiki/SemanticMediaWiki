<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\ContentParser;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ParserFirstCallInitIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;
	private $applicationFactory;
	private $parserFactory;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->parserFactory = $utilityFactory->newParserFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $store );

		$this->applicationFactory->getSettings()->set( 'smwgCacheType', CACHE_NONE );

		$this->mwHooksHandler->register(
			'ParserFirstCallInit',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'ParserFirstCallInit' )
		);
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	/**
	 * @dataProvider textToParseProvider
	 */
	public function testParseWithParserFunctionEnabled( $parserName, $text ) {

		$expectedNullOutputFor = array(
			'concept',
			'declare'
		);

		$title = Title::newFromText( __METHOD__ );
		$parser = $this->parserFactory->newFromTitle( $title );

		$this->applicationFactory->getSettings()->set( 'smwgQEnabled', true );

		$instance = new ContentParser( $title, $parser );
		$instance->parse( $text );

		if ( in_array( $parserName, $expectedNullOutputFor ) ) {
			return $this->assertNull(
				$this->findSemanticataFromOutput( $instance->getOutput() )
			);
		}

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->findSemanticataFromOutput( $instance->getOutput() )
		);
	}

	/**
	 * @dataProvider textToParseProvider
	 */
	public function testParseWithParserFunctionDisabled( $parserName, $text ) {

		$expectedNullOutputFor = array(
			'concept',
			'declare',
			'ask',
			'show'
		);

		$title = Title::newFromText( __METHOD__ );
		$parser = $this->parserFactory->newFromTitle( $title );

		$this->applicationFactory->getSettings()->set( 'smwgQEnabled', false );

		$instance = new ContentParser( $title, $parser );
		$instance->parse( $text );

		if ( in_array( $parserName, $expectedNullOutputFor ) ) {
			return $this->assertNull(
				$this->findSemanticataFromOutput( $instance->getOutput() )
			);
		}

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$this->findSemanticataFromOutput( $instance->getOutput() )
		);
	}

	public function textToParseProvider() {

		$provider = array();

		#0 ask
		$provider[] = array(
			'ask',
			'{{#ask: [[Modification date::+]]|limit=1}}'
		);

		#1 show
		$provider[] = array(
			'show',
			'{{#show: [[Foo]]|limit=1}}'
		);

		#2 subobject
		$provider[] = array(
			'subobject',
			'{{#subobject:|foo=bar|lila=lula,linda,luna|+sep=,}}'
		);

		#3 set
		$provider[] = array(
			'set',
			'{{#set:|foo=bar|lila=lula,linda,luna|+sep=,}}'
		);

		#4 set_recurring_event
		$provider[] = array(
			'set_recurring_event',
			'{{#set_recurring_event:some more tests|property=has date|' .
			'has title=Some recurring title|title2|has group=Events123|Events456|start=June 8, 2010|end=June 8, 2011|' .
			'unit=week|period=1|limit=10|duration=7200|include=March 16, 2010;March 23, 2010|+sep=;|' .
			'exclude=March 15, 2010;March 22, 2010|+sep=;}}'
		);

		#5 declare
		$provider[] = array(
			'declare',
			'{{#declare:population=Foo}}'
		);

		#6 concept
		$provider[] = array(
			'concept',
			'{{#concept:[[Modification date::+]]|Foo}}'
		);

		return $provider;
	}

	private function findSemanticataFromOutput( $parserOutput ) {

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			return $parserOutput->getExtensionData( 'smwdata' );
		}

		return isset( $parserOutput->mSMWData ) ? $parserOutput->mSMWData : null;
	}

}
