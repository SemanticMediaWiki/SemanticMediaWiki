<?php

namespace SMW\Tests\Unit\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\EntityCache;
use SMW\Maintenance\updateQueryDependencies;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\Maintenance\updateQueryDependencies
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateQueryDependenciesTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $testEnvironment;
	private $messageReporter;
	private $store;
	private $connection;
	private $entityCache;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->messageReporter = $this->getMockBuilder( MessageReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
		$this->testEnvironment->registerObject( 'EntityCache', $this->entityCache );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			updateQueryDependencies::class,
			new updateQueryDependencies()
		);
	}

	public function testExecute() {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$row = new stdClass;
		$row->smw_id = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = '0';
		$row->smw_iw = '';
		$row->smw_subobject = '';

		$this->connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				fn () => $this->createMockSelectQueryBuilder( [ $row ] )
			);

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new updateQueryDependencies();

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->execute();
	}

}
