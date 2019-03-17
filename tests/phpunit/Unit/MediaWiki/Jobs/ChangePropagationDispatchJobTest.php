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

	public function testCleanUp() {

		$subject = DIWikiPage::newFromText(__METHOD__, SMW_NS_PROPERTY );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'delete' );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		ChangePropagationDispatchJob::cleanUp( $subject );
	}

	public function testHasPendingJobs() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

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

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

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

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->never() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$instance = new ChangePropagationDispatchJob(
			$subject->getTitle()
		);

		$instance->run();
	}

	public function testPlanAsJob() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->once() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		ChangePropagationDispatchJob::planAsJob( $subject );
	}

	public function testFindAndDispatchOnPropertyEntity() {

		$subject = DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$tempFile = $this->getMockBuilder( '\SMW\Utils\TempFile' )
			->disableOriginalConstructor()
			->getMock();

		$tempFile->expects( $this->atLeastOnce() )
			->method( 'write' );

		$this->testEnvironment->registerObject( 'TempFile', $tempFile );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->atLeastOnce() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->atLeastOnce() )
			->method( 'getDefaultDataItemTables' )
			->will( $this->returnValue( [] ) );

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
			->will( $this->returnValue( [] ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ChangePropagationDispatchJob(
			$subject->getTitle(),
			[
				'isTypePropagation' => true
			]
		);

		$instance->run();
	}

	public function testDispatchSchemaChangePropagation() {

		$dataItem = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $dataItem ] ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$subject = DIWikiPage::newFromText( 'Foo' );

		// Check that it is the dataItem from `getPropertyValues`
		$checkJobParameterCallback = function( $jobs ) use( $dataItem ) {
			foreach ( $jobs as $job ) {
				return DIWikiPage::newFromTitle( $job->getTitle() )->equals( $dataItem );
			}
		};

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->once() )
			->method( 'push' )
			->with( $this->callback( $checkJobParameterCallback ) );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$instance = new ChangePropagationDispatchJob(
			$subject->getTitle(),
			[
				'schema_change_propagation' => true,
				'property_key' => 'Foo'
			]
		);

		$instance->run();
	}

}
