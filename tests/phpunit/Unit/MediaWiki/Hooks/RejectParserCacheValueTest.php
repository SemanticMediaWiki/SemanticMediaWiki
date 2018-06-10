<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\RejectParserCacheValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dependencyLinksUpdateJournal;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyLinksUpdateJournal = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RejectParserCacheValue::class,
			new RejectParserCacheValue( $this->dependencyLinksUpdateJournal )
		);
	}

	public function testProcessOnJournalEntryToReject() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->dependencyLinksUpdateJournal->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$this->dependencyLinksUpdateJournal->expects( $this->once() )
			->method( 'delete' );

		$instance = new RejectParserCacheValue(
			$this->dependencyLinksUpdateJournal
		);

		$this->assertFalse(
			$instance->process( $subject->getTitle() )
		);
	}

}
