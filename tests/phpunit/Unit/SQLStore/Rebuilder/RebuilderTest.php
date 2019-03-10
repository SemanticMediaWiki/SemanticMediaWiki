<?php

namespace SMW\Tests\SQLStore\Rebuilder;

use SMW\ApplicationFactory;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\Rebuilder\Rebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class RebuilderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $titleFactory;
	private $entityValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgSemanticsEnabled' => true,
				'smwgAutoRefreshSubject' => true,
				'smwgCacheType' => 'hash',
				'smwgEnableUpdateJobs' => false,
			]
		);

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityValidator = $this->getMockBuilder( '\SMW\SQLStore\Rebuilder\EntityValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );

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

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			Rebuilder::class,
			new Rebuilder( $store, $this->titleFactory, $this->entityValidator )
		);
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testDispatchRebuildForSingleIteration( $id, $expected ) {

		$this->titleFactory->expects( $this->any() )
			->method( 'newFromIDs' )
			->will( $this->returnValue( [] ) );

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

		$instance = new Rebuilder(
			$store,
			$this->titleFactory,
			$this->entityValidator
		);

		$instance->setDispatchRangeLimit( 1 );
		$instance->setOptions(
			[
				'shallow-update' => true,
				'use-job' => false
			]
		);

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

	public function testRevisionMode() {

		$this->entityValidator->expects( $this->any() )
			->method( 'hasLatestRevID' )
			->will( $this->returnValue( true ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$this->titleFactory->expects( $this->any() )
			->method( 'newFromIDs' )
			->will( $this->returnValue( [ $title ] ) );

		$row = [
			'smw_id' => 9999999999999999,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash' => [],
			'smw_rev' => 0
		];

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( [ (object)$row] ) );

		$connection->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( 500 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new Rebuilder(
			$store,
			$this->titleFactory,
			$this->entityValidator
		);

		$instance->setDispatchRangeLimit( 1 );
		$instance->setOptions(
			[
				'revision-mode' => true,
				'force-update' => false,
				'use-job' => false
			]
		);

		$id = 999999999;

		$instance->rebuild( $id );
	}

	public function idProvider() {

		$provider[] = [
			42, // Within the border Id
			43
		];

		$provider[] = [
			9999999999999999999,
			-1
		];

		return $provider;
	}
}
