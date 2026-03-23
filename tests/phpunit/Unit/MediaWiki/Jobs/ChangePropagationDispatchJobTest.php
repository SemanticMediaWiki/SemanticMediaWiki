<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\ChangePropagationDispatchJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationDispatchJobTest extends TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ChangePropagationDispatchJob::class,
			new ChangePropagationDispatchJob( $title )
		);
	}

	public function testCleanUp() {
		$subject = WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$cache = $this->getMockBuilder( Cache::class )
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'delete' );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		ChangePropagationDispatchJob::cleanUp( $subject );
	}

	public function testHasPendingJobs() {
		$subject = WikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$cache = $this->getMockBuilder( Cache::class )
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( 42 );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		$this->assertTrue(
			ChangePropagationDispatchJob::hasPendingJobs( $subject )
		);
	}

	public function testGetPendingJobsCount() {
		$subject = WikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$cache = $this->getMockBuilder( Cache::class )
			->getMockForAbstractClass();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->willReturn( 42 );

		$this->testEnvironment->registerObject( 'Cache', $cache );

		$this->assertSame(
			42,
			ChangePropagationDispatchJob::getPendingJobsCount( $subject )
		);
	}

	public function testFindAndDispatchOnNonPropertyEntity() {
		$subject = WikiPage::newFromText( 'Foo' );

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
		$subject = WikiPage::newFromText( 'Foo' );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->once() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		ChangePropagationDispatchJob::planAsJob( $subject );
	}

	public function testFindAndDispatchOnPropertyEntity() {
		$subject = WikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->atLeastOnce() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->atLeastOnce() )
			->method( 'getDefaultDataItemTables' )
			->willReturn( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$store->expects( $this->atLeastOnce() )
			->method( 'getAllPropertySubjects' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

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
		$dataItem = WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $dataItem ] );

		$this->testEnvironment->registerObject( 'Store', $store );

		$subject = WikiPage::newFromText( 'Foo' );

		// Check that it is the dataItem from `getPropertyValues`
		$checkJobParameterCallback = static function ( $jobs ) use( $dataItem ) {
			foreach ( $jobs as $job ) {
				return WikiPage::newFromTitle( $job->getTitle() )->equals( $dataItem );
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
