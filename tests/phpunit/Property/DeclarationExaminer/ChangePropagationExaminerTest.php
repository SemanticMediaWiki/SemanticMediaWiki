<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use SMW\Property\DeclarationExaminer\ChangePropagationExaminer;
use SMW\DataItemFactory;
use SMW\SemanticData;
use SMW\ProcessingErrorMsgHandler;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Property\DeclarationExaminer\ChangePropagationExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ChangePropagationExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $declarationExaminer;
	private $store;
	private $semanticData;
	private $testEnvironment;
	private $jobQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer = $this->getMockBuilder( '\SMW\Property\DeclarationExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->declarationExaminer->expects( $this->any() )
			->method( 'getMessages' )
			->willReturn( [] );

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ChangePropagationExaminer::class,
			new ChangePropagationExaminer( $this->declarationExaminer, $this->store, $this->semanticData )
		);
	}

	public function testIsChangePropagation() {
		$dataItemFactory = new DataItemFactory();
		$subject = $dataItemFactory->newDIWikiPage( 'Test', NS_MAIN );

		$semanticData = new SemanticData(
			$subject
		);

		$semanticData->addPropertyObjectValue(
			$dataItemFactory->newDIProperty( '_CHGPRO' ),
			$dataItemFactory->newDIBlob( '...' )
		);

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$instance = new ChangePropagationExaminer(
			$this->declarationExaminer,
			$this->store,
			$this->semanticData
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Bar' )
		);

		$this->assertContains(
			'["error","smw-property-req-violation-change-propagation-locked-error","Bar"]',
			$instance->getMessagesAsString()
		);

		$this->assertTrue(
			$instance->isLocked()
		);
	}

	public function testPendingJob() {
		$dataItemFactory = new DataItemFactory();
		$subject = $dataItemFactory->newDIWikiPage( 'Test', NS_MAIN );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $this->semanticData );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$this->jobQueue->expects( $this->any() )
			->method( 'getQueueSize' )
			->willReturnOnConsecutiveCalls( 2, 4 );

		$instance = new ChangePropagationExaminer(
			$this->declarationExaminer,
			$this->store
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Bar' )
		);

		$this->assertContains(
			'["warning","smw-property-req-violation-change-propagation-pending",6]',
			$instance->getMessagesAsString()
		);

		$this->assertFalse(
			$instance->isLocked()
		);
	}

}
