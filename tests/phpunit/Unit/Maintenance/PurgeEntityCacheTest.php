<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\PurgeEntityCache;
use FakeResultWrapper;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Maintenance\PurgeEntityCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PurgeEntityCacheTest extends \PHPUnit_Framework_TestCase {

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
			PurgeEntityCache::class,
			new PurgeEntityCache()
		);
	}

	public function testExecute() {

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
				$this->equalTo( $fields ),
				$this->anything() )
			->will( $this->returnValue( new FakeResultWrapper( [ $row ] ) ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->entityCache->expects( $this->atLeastOnce() )
			->method( 'invalidate' )
			->with( $this->equalTo( $subject ) );

		$instance = new PurgeEntityCache();

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->execute();
	}

}
