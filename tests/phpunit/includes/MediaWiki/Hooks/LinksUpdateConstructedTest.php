<?php

namespace SMW\Test\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\LinksUpdateConstructed;
use SMW\Application;

use ParserOutput;
use LinksUpdate;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\LinksUpdateConstructed
 *
 * @ingroup Test
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

	private $application;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->application->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->application->clear();

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

		$this->application->registerObject( 'Store', $store );

		$instance = new LinksUpdateConstructed( new LinksUpdate( $title, $parserOutput ) );

		$this->assertTrue( $instance->process() );
	}

}
