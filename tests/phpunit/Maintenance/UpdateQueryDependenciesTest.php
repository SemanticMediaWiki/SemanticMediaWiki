<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\updateQueryDependencies;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\Maintenance\updateQueryDependencies
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateQueryDependenciesTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $messageReporter;
	private $store;
	private $connection;
	private $entityCache;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->messageReporter = $this->getMockBuilder( '\Onoi\MessageReporter\MessageReporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
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
		$updateJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\UpdateJob' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$fields = [
			"smw_subobject=''",
			'smw_iw != '
		];

		$row = new \stdClass;
		$row->smw_id = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = '0';
		$row->smw_iw = '';
		$row->smw_subobject = '';

		$subject = new DIWikiPage( 'Foo', 0 );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything() )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

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
