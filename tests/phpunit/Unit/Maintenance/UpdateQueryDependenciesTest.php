<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\UpdateQueryDependencies;
use FakeResultWrapper;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Maintenance\UpdateQueryDependencies
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateQueryDependenciesTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $messageReporter;
	private $store;
	private $connection;
	private $entityCache;

	protected function setUp() {

		$this->testEnvironment =  new TestEnvironment();

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

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			UpdateQueryDependencies::class,
			new UpdateQueryDependencies()
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
			->will( $this->returnValue( $updateJob ) );

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
			->will( $this->returnValue( new FakeResultWrapper( [ $row ] ) ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$instance = new UpdateQueryDependencies();

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->execute();
	}

}
