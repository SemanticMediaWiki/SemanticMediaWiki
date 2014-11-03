<?php

namespace SMW\Test\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\LinksUpdateConstructed;
use SMW\ApplicationFactory;

use ParserOutput;
use LinksUpdate;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\LinksUpdateConstructed
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructedTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$linksUpdate = $this->getMockBuilder( '\LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\LinksUpdateConstructed',
			new LinksUpdateConstructed( $linksUpdate )
		);
	}

	public function testProcess() {

		$title = Title::newFromText( __METHOD__ );
		$title->resetArticleID( rand( 1, 1000 ) );

		$parserOutput = new ParserOutput();
		$parserOutput->setTitleText( $title->getPrefixedText() );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'updateData' );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new LinksUpdateConstructed( new LinksUpdate( $title, $parserOutput ) );

		$this->assertTrue( $instance->process() );
	}

}
