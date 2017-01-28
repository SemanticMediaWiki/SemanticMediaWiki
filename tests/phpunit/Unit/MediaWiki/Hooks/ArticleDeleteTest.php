<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\Tests\TestEnvironment;

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

	public function testProcess() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( '\SMWSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'deleteSubject' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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
