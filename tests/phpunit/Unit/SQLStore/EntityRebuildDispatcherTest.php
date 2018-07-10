<?php

namespace SMW\Tests\SQLStore;

use SMW\ApplicationFactory;
use SMW\SQLStore\EntityRebuildDispatcher;

/**
 * @covers \SMW\SQLStore\EntityRebuildDispatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EntityRebuildDispatcherTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( 0 ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->applicationFactory->registerObject( 'Store', $store );
		$this->applicationFactory->getSettings()->set( 'smwgCacheType', 'hash' );
		$this->applicationFactory->getSettings()->set( 'smwgEnableUpdateJobs', false );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityRebuildDispatcher',
			new EntityRebuildDispatcher( $store )
		);
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testDispatchRebuildForSingleIteration( $id, $expected ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$connection->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( $expected ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new EntityRebuildDispatcher( $store );

		$instance->setDispatchRangeLimit( 1 );
		$instance->setUpdateJobParseMode( SMW_UJ_PM_CLASTMDATE );

		$instance->useJobQueueScheduler( false );
		$instance->rebuild( $id );

		$this->assertSame(
			$expected,
			$id
		);

		$this->assertLessThanOrEqual(
			1,
			$instance->getEstimatedProgress()
		);
	}

	public function idProvider() {

		$provider[] = [
			42, // Within the border Id
			43
		];

		$provider[] = [
			51,
			-1
		];

		return $provider;
	}
}
