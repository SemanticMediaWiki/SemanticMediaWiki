<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\Browse;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Browse
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class BrowseTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $apiFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgCacheUsage' => [ 'api.browse' => true ]
			]
		);

		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();

		$proximityPropertyValueLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ProximityPropertyValueLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( $this->equalTo( 'ProximityPropertyValueLookup' ) )
			->will( $this->returnValue( $proximityPropertyValueLookup ) );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new Browse(
			$this->apiFactory->newApiMain( [] ),
			'smwbrowse'
		);

		$this->assertInstanceOf(
			Browse::class,
			$instance
		);
	}

	/**
	 * @dataProvider browseIdProvider
	 */
	public function testExecute( $id, $parameters = [] ) {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( false ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$resultWrapper = $this->getMockBuilder( '\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$connection->expects( $this->any() )
			->method( 'query' )
			->will( $this->returnValue( $resultWrapper ) );

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getSQLOptions' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->testEnvironment->registerObject( 'Cache', $cache );
		$this->testEnvironment->registerObject( 'Store', $this->store );

		$instance = new Browse(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwbrowse',
					'browse'   => $id,
					'params'   => json_encode( [ 'search' => 'Foo' ] + $parameters )
				]
			),
			'smwbrowse'
		);

		$instance->execute();
	}

	public function testExecute_Subject() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [] ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );

		$instance = new Browse(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwbrowse',
					'browse'   => 'subject',
					'params'   => json_encode( [ 'subject' => 'Bar', 'ns' => 0 ] )
				]
			),
			'smwbrowse'
		);

		$instance->execute();
	}

	public function browseIdProvider() {

		$provider[] = [
			'property'
		];

		$provider[] = [
			'category'
		];

		$provider[] = [
			'concept'
		];

		$provider[] = [
			'page'
		];

		$provider[] = [
			'pvalue',
			[ 'property' => 'Bar' ]
		];

		$provider[] = [
			'psubject',
			[ 'property' => 'Bar' ]
		];

		return $provider;
	}

}
