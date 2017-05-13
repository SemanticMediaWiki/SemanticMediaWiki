<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\Tests\TestEnvironment;
use SMW\DIProperty;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleDelete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDeleteTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->addConfiguration(
			'smwgEnableUpdateJobs',
			false
		);

		$this->testEnvironment->addConfiguration(
			'smwgEnabledDeferredUpdate',
			false
		);
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new ArticleDelete();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ArticleDelete',
			$instance
		);
	}

	public function testActOn() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'deleteSubject' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getInProperties' )
			->will( $this->returnValue( array( new DIProperty( 'Foo' ) ) ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $subject->getTitle() ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ArticleDelete();

		$this->assertTrue(
			$instance->process( $wikiPage )
		);
	}

}
