<?php

namespace SMW\Tests\SQLStore;

use SMW\ApplicationFactory;
use SMW\SQLStore\ByIdDataRebuildDispatcher;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\ByIdDataRebuildDispatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ByIdDataRebuildDispatcherTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'hasIDFor' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'hasIDFor' )
			->will( $this->returnValue( 0 ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getWikiPageLastModifiedTimestamp', 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getWikiPageLastModifiedTimestamp' )
			->will( $this->returnValue( 0 ) );

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
			'\SMW\SQLStore\ByIdDataRebuildDispatcher',
			new ByIdDataRebuildDispatcher( $store )
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
			->will( $this->returnValue( array() ) );

		$connection->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( $expected ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new ByIdDataRebuildDispatcher( $store );

		$instance->setIterationLimit( 1 );
		$instance->setUpdateJobParseMode( SMW_UJ_PM_CLASTMDATE );

		$instance->setUpdateJobToUseJobQueueScheduler( false );
		$instance->dispatchRebuildFor( $id );

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

		$provider[] = array(
			42, // Within the border Id
			43
		);

		$provider[] = array(
			51,
			-1
		);

		return $provider;
	}
}
