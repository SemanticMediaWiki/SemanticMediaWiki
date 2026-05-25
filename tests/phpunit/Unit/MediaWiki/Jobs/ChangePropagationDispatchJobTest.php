<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\MediaWiki\Jobs\ChangePropagationUpdateJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Property\SpecificationLookup as PropertySpecificationLookup;
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

	private TestEnvironment $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		// Static helpers planAsJob() / hasPendingJobs() / cleanUp() still go
		// through ApplicationFactory; register defaults the test environment
		// expects.
		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newCache(): Cache {
		return $this->getMockBuilder( Cache::class )->getMockForAbstractClass();
	}

	private function newPropertySpecificationLookup(): PropertySpecificationLookup {
		return $this->getMockBuilder( PropertySpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newIteratorFactory(): IteratorFactory {
		return new IteratorFactory();
	}

	private function newStore(): SQLStore {
		return $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newJobFactory(): JobFactory {
		return $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newJob(
		Title $title,
		array $params = [],
		?SQLStore $store = null,
		?Cache $cache = null,
		?PropertySpecificationLookup $propertySpecificationLookup = null,
		?IteratorFactory $iteratorFactory = null,
		?JobFactory $jobFactory = null
	): ChangePropagationDispatchJob {
		return new ChangePropagationDispatchJob(
			$title,
			$params,
			$store ?? $this->newStore(),
			$cache ?? $this->newCache(),
			$propertySpecificationLookup ?? $this->newPropertySpecificationLookup(),
			$iteratorFactory ?? $this->newIteratorFactory(),
			$jobFactory ?? $this->newJobFactory()
		);
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ChangePropagationDispatchJob::class,
			$this->newJob( $title )
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

		$jobFactory = $this->newJobFactory();
		$jobFactory->expects( $this->never() )
			->method( 'newChangePropagationDispatchJob' );

		$instance = $this->newJob( $subject->getTitle(), [], null, null, null, null, $jobFactory );

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

		$updateJob = $this->getMockBuilder( ChangePropagationUpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchJob = $this->getMockBuilder( ChangePropagationDispatchJob::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchJob->expects( $this->atLeastOnce() )
			->method( 'lazyPush' );

		$jobFactory = $this->newJobFactory();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newChangePropagationUpdateJob' )
			->willReturn( $updateJob );

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newChangePropagationDispatchJob' )
			->willReturn( $dispatchJob );

		$instance = $this->newJob(
			$subject->getTitle(),
			[
				'isTypePropagation' => true
			],
			$store,
			null,
			null,
			null,
			$jobFactory
		);

		$instance->run();
	}

	/**
	 * @dataProvider chooseUpdateStrategyProvider
	 */
	public function testChooseUpdateStrategy( array $params, string $expected ): void {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$job = $this->newJob( $title, $params );

		$reflector = new ReflectionClass( ChangePropagationDispatchJob::class );
		$method = $reflector->getMethod( 'chooseUpdateStrategy' );
		$method->setAccessible( true );

		$this->assertSame( $expected, $method->invoke( $job ) );
	}

	public function chooseUpdateStrategyProvider(): array {
		return [
			'no diffKeys param at all (backward-compat)' => [
				[],
				UpdateJob::FORCED_UPDATE,
			],
			'empty diffKeys array' => [
				[ 'diffKeys' => [] ],
				UpdateJob::FORCED_UPDATE,
			],
			'all keys in SHALLOW_SET (_SUBC only)' => [
				[ 'diffKeys' => [ '_SUBC' ] ],
				'shallowUpdate',
			],
			'all keys in SHALLOW_SET (multi)' => [
				[ 'diffKeys' => [ '_SUBC', '_PDESC' ] ],
				'shallowUpdate',
			],
			'mixed: one safe, one not' => [
				[ 'diffKeys' => [ '_PDESC', '_PVAL' ] ],
				UpdateJob::FORCED_UPDATE,
			],
			'all keys outside SHALLOW_SET' => [
				[ 'diffKeys' => [ '_PVAL', '_TYPE' ] ],
				UpdateJob::FORCED_UPDATE,
			],
			'_LIST is explicitly NOT in SHALLOW_SET' => [
				[ 'diffKeys' => [ '_LIST' ] ],
				UpdateJob::FORCED_UPDATE,
			],
		];
	}

	public function testDispatchFromDataForwardsDiffKeysToSecondStageJob(): void {
		// A secondary dispatch job (carrying a 'data' param produced by
		// pushChangePropagationDispatchJob on a prior pass) runs
		// dispatchFromData(), which is where scheduleChangePropagationUpdateJobFromList
		// fires and chooseUpdateStrategy() reads diffKeys. The secondary job must
		// have carried diffKeys forward from the primary dispatch for the shallow
		// path to apply.
		$subject = WikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$cache = $this->getMockBuilder( Cache::class )
			->getMockForAbstractClass();

		// Return a non-false value so dispatchFromData() passes the cache check.
		$cache->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( 3 );

		$updateJob = $this->getMockBuilder( ChangePropagationUpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$updateJob->expects( $this->once() )
			->method( 'insert' );

		$capturedParams = null;
		$jobFactory = $this->newJobFactory();

		$jobFactory->expects( $this->once() )
			->method( 'newChangePropagationUpdateJob' )
			->with(
				$this->anything(),
				$this->callback( static function ( array $params ) use ( &$capturedParams ) {
					$capturedParams = $params;
					return true;
				} )
			)
			->willReturn( $updateJob );

		// Subject serialisation must round-trip through WikiPage::doUnserialize.
		$dataItem = WikiPage::newFromTitle( $subject->getTitle() );
		$instance = $this->newJob(
			$subject->getTitle(),
			[
				'data' => $dataItem->asBase()->getHash(),
				'diffKeys' => [ '_SUBC' ],
			],
			null,
			$cache,
			null,
			null,
			$jobFactory
		);

		$instance->run();

		$this->assertNotNull( $capturedParams, 'newChangePropagationUpdateJob was not called' );
		$this->assertArrayHasKey(
			'shallowUpdate',
			$capturedParams,
			'Per-entity job must carry shallowUpdate=true when diffKeys is all-shallow'
		);
		$this->assertTrue( $capturedParams['shallowUpdate'] );
		$this->assertArrayNotHasKey(
			UpdateJob::FORCED_UPDATE,
			$capturedParams,
			'Per-entity job must NOT carry forcedUpdate when diffKeys is all-shallow'
		);
	}

	public function testDispatchSchemaChangePropagation() {
		$dataItem = WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $dataItem ] );

		$subject = WikiPage::newFromText( 'Foo' );

		$innerJob = $this->getMockBuilder( ChangePropagationDispatchJob::class )
			->disableOriginalConstructor()
			->getMock();

		$innerJob->expects( $this->once() )
			->method( 'insert' );

		// Verify the dataItem from `getPropertyValues` is the target Title.
		$checkTitleCallback = static function ( ?Title $title ) use( $dataItem ) {
			return $title !== null && WikiPage::newFromTitle( $title )->equals( $dataItem );
		};

		$jobFactory = $this->newJobFactory();

		$jobFactory->expects( $this->once() )
			->method( 'newChangePropagationDispatchJob' )
			->with( $this->callback( $checkTitleCallback ) )
			->willReturn( $innerJob );

		$instance = $this->newJob(
			$subject->getTitle(),
			[
				'schema_change_propagation' => true,
				'property_key' => 'Foo'
			],
			$store,
			null,
			null,
			null,
			$jobFactory
		);

		$instance->run();
	}

}
