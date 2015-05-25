<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\ByIdDataRebuildDispatcher;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\DIWikiPage;
use SMW\SemanticData;

/**
 * @covers \SMW\SQLStore\ByIdDataRebuildDispatcher
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ByIdDataRebuildDispatcherTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
		$this->applicationFactory->getSettings()->set( 'smwgCacheType', 'hash' );
		$this->applicationFactory->getSettings()->set( 'smwgEnableUpdateJobs', false );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ByIdDataRebuildDispatcher',
			new ByIdDataRebuildDispatcher( $store )
		);
	}

	public function testDispatchRebuildForSingleIterationToIndicateNoFurtherProgress() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( array( 'getPropertyTables' ) )
			->getMock();

		$instance = new ByIdDataRebuildDispatcher( $store );
		$instance->setIterationLimit( 1 );
		$instance->setUpdateJobToUseJobQueueScheduler( false );

		$id = 42;

		$instance->dispatchRebuildFor( $id );

		$this->assertSame(
			-1,
			$id
		);

		$this->assertSame(
			1,
			$instance->getProgress()
		);
	}

}
