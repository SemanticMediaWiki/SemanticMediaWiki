<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\ChangePropagationDispatchJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationDispatchJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ChangePropagationDispatchJob::class,
			new ChangePropagationDispatchJob( $title )
		);
	}

	public function testRemoveProcessMarker() {

		$subject = DIWikiPage::newFromText(__METHOD__, SMW_NS_PROPERTY );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'delete' );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		ChangePropagationDispatchJob::removeProcessMarker( $subject );
	}

	public function testHasPendingJobs() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->once() )
			->method( 'get' )
			->will( $this->returnValue( $jobQueue ) );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( 42 ) );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		$this->assertTrue(
			ChangePropagationDispatchJob::hasPendingJobs( $subject )
		);
	}

	public function testGetPendingJobsCount() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\JobQueue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'get' )
			->will( $this->returnValue( $jobQueue ) );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->getMockForAbstractClass();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( 42 ) );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		$this->assertSame(
			42,
			ChangePropagationDispatchJob::getPendingJobsCount( $subject )
		);
	}

	public function testFindAndDispatchOnNonPropertyEntity() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->never() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$instance = new ChangePropagationDispatchJob(
			$subject->getTitle()
		);

		$instance->findAndDispatch();
	}

	public function testPlanAsJob() {

		if ( !method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$subject = DIWikiPage::newFromText( 'Foo' );

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->once() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		ChangePropagationDispatchJob::planAsJob( $subject );
	}

	public function testCleanUp() {

		if ( !method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$subject = DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->once() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$tempFile = $this->getMockBuilder( '\SMW\Utils\TempFile' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'TempFile', $tempFile );

		ChangePropagationDispatchJob::cleanUp( $subject );
	}

	public function testFindAndDispatchOnPropertyEntity() {

		if ( !method_exists( 'JobQueueGroup', 'lazyPush' ) ) {
			$this->markTestSkipped( 'JobQueueGroup::lazyPush is not supported.' );
		}

		$subject = DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$tempFile = $this->getMockBuilder( '\SMW\Utils\TempFile' )
			->disableOriginalConstructor()
			->getMock();

		$tempFile->expects( $this->atLeastOnce() )
			->method( 'write' );

		$this->testEnvironment->registerObject( 'TempFile', $tempFile );

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPropertyID' ) )
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->atLeastOnce() )
			->method( 'getDefaultDataItemTables' )
			->will( $this->returnValue( array() ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getAllPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ChangePropagationDispatchJob(
			$subject->getTitle(),
			array(
				'isTypePropagation' => true
			)
		);

		$instance->findAndDispatch();
	}

}
