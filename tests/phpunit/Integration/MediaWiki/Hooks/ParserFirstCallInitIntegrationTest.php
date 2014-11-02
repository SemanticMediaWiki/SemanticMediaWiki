<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\UtilityFactory;

use SMW\Application;
use SMW\ContentParser;

use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ParserFunctionInTextParseTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;
	private $application;
	private $parserFactory;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->parserFactory = UtilityFactory::getInstance()->newParserFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getSemanticData' ) )
			->getMockForAbstractClass();

	//	$store->expects( $this->atLeastOnce() )
	//		->method( 'getSemanticData' );

		$this->application = Application::getInstance();
		$this->application->registerObject( 'Store', $store );

		$this->application->getSettings()->set( 'smwgCacheType', CACHE_NONE );

		$this->mwHooksHandler->register(
			'ParserFirstCallInit',
			$this->mwHooksHandler->getHookRegistry()->getDefinition( 'ParserFirstCallInit' )
		);
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();
		$this->application->clear();

		parent::tearDown();
	}

	/**
	 * @dataProvider textToParseProvider
	 */
	public function testParseWithParserFunctionEnabled( $parserName, $text ) {

		$title = Title::newFromText( __METHOD__ );
		$parser = $this->parserFactory->newFromTitle( $title );

		$this->application->getSettings()->set( 'smwgQEnabled', true );

		$instance = new ContentParser( $title, $parser );
		$instance->parse( $text );

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
	 * @dataProvider textToParseProvider
	 */
	public function testParseWithParserFunctionDisabled( $parserName, $text ) {

		$title = Title::newFromText( __METHOD__ );
		$parser = $this->parserFactory->newFromTitle( $title );

		$this->application->getSettings()->set( 'smwgQEnabled', false );

		$instance = new ContentParser( $title, $parser );
		$instance->parse( $text );

		$this->assertInstanceOf(
			'ParserOutput',
			$instance->getOutput()
		);

		$this->assertInternalType(
			'string',
			$instance->getOutput()->getText()
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

}
