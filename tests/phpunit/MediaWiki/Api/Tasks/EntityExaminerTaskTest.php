<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\EntityExaminerTask;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\EntityExaminerTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerTaskTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $entityExaminerIndicatorsFactory;
	private $permissionExaminer;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityExaminerIndicatorsFactory = $this->getMockBuilder( '\SMW\Indicator\EntityExaminerIndicatorsFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityExaminerTask::class,
			new EntityExaminerTask( $this->store, $this->entityExaminerIndicatorsFactory )
		);
	}

	public function testProcess_EmptySubject() {
		$instance = new EntityExaminerTask(
			$this->store,
			$this->entityExaminerIndicatorsFactory
		);

		$this->assertEquals(
			[ 'done' => false ],
			$instance->process( [ 'subject' => '' ] )
		);
	}

	public function testProcess() {
		$entityExaminerDeferrableCompositeIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$entityExaminerDeferrableCompositeIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setPermissionExaminer' );

		$this->entityExaminerIndicatorsFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityExaminerDeferrableCompositeIndicatorProvider' )
			->willReturn( $entityExaminerDeferrableCompositeIndicatorProvider );

		$instance = new EntityExaminerTask(
			$this->store,
			$this->entityExaminerIndicatorsFactory
		);

		$instance->setPermissionExaminer(
			$this->permissionExaminer
		);

		$this->assertEquals(
			[ 'done' => true, 'indicators' => null, 'html' => '' ],
			$instance->process( [ 'subject' => 'Foo#0##' ] )
		);
	}

	public function testProcess_Placeholder() {
		$entityExaminerDeferrableCompositeIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$entityExaminerDeferrableCompositeIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setPermissionExaminer' );

		$compositeIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$compositeIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setPermissionExaminer' );

		$this->entityExaminerIndicatorsFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityExaminerDeferrableCompositeIndicatorProvider' )
			->willReturn( $entityExaminerDeferrableCompositeIndicatorProvider );

		$this->entityExaminerIndicatorsFactory->expects( $this->atLeastOnce() )
			->method( 'newEntityExaminerCompositeIndicatorProvider' )
			->willReturn( $compositeIndicatorProvider );

		$instance = new EntityExaminerTask(
			$this->store,
			$this->entityExaminerIndicatorsFactory
		);

		$instance->setPermissionExaminer(
			$this->permissionExaminer
		);

		$this->assertEquals(
			[ 'done' => true, 'indicators' => [], 'html' => null ],
			$instance->process( [ 'subject' => 'Foo#0##', 'is_placeholder' => true ] )
		);
	}

}
