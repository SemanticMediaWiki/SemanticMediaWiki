<?php

namespace SMW\Tests\MediaWiki\Api;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DIWikiPage;
use SMW\MediaWiki\Api\Browse;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\SemanticData;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\MediaWiki\Api\Browse
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class BrowseTest extends TestCase {

	private $store;
	private $apiFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgCacheUsage' => [ 'api.browse' => true ]
			]
		);

		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();

		$proximityPropertyValueLookup = $this->getMockBuilder( ProximityPropertyValueLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'ProximityPropertyValueLookup' )
			->willReturn( $proximityPropertyValueLookup );
	}

	protected function tearDown(): void {
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
			->willReturn( false );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->willReturn( false );

		$resultWrapper = $this->getMockBuilder( FakeResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $query );

		$connection->expects( $this->any() )
			->method( 'query' )
			->willReturn( $resultWrapper );

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getSQLOptions' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

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
		$subject = $this->getMockBuilder( DIWikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

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
