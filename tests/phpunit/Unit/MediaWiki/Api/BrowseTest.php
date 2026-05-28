<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use MediaWiki\Title\TitleFactory;
use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Api\Browse;
use SMW\MediaWiki\Connection\Database;
use SMW\Settings;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
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

	use MockSelectQueryBuilderTrait;

	private $store;
	private $apiFactory;
	private $testEnvironment;
	private $titleFactory;

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

		$this->titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Browse(
			$this->apiFactory->newApiMain( [] ),
			'smwbrowse',
			$this->store,
			$settings,
			$cache,
			$this->titleFactory
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

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'query' )
			->willReturn( $resultWrapper );

		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );

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

		$instance = new Browse(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwbrowse',
					'browse'   => $id,
					'params'   => json_encode( [ 'search' => 'Foo' ] + $parameters )
				]
			),
			'smwbrowse',
			$this->store,
			Settings::newFromArray( [ 'smwgCacheUsage' => [ 'api.browse' => true ] ] ),
			$cache,
			$this->titleFactory
		);

		$instance->execute();
	}

	public function testExecute_Subject() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		// SubjectLookup::doSerialize still resolves the Store through
		// ApplicationFactory directly; register the mock so the inner
		// getSemanticData() call hits the same store as the injected one.
		$this->testEnvironment->registerObject( 'Store', $this->store );

		$instance = new Browse(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwbrowse',
					'browse'   => 'subject',
					'params'   => json_encode( [ 'subject' => 'Bar', 'ns' => 0 ] )
				]
			),
			'smwbrowse',
			$this->store,
			$settings,
			$cache,
			$this->titleFactory
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
