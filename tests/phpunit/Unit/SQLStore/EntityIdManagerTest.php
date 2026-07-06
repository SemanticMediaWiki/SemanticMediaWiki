<?php

namespace SMW\Tests\Unit\SQLStore;

use MediaWiki\JobQueue\JobFactory;
use MediaWiki\Title\TitleFactory;
use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\Settings;
use SMW\SQLStore\EntityStore\AuxiliaryFields;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\SQLStore\EntityStore\DuplicateFinder;
use SMW\SQLStore\EntityStore\EntityIdFinder;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\FieldList;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\EntityStore\EntityIdManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9.1
 *
 * @author mwjames
 */
class EntityIdManagerTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $store;
	private $cache;
	private IdEntityFinder $idEntityFinder;
	private CacheWarmer $cacheWarmer;
	private PropertyTableHashes $propertyTableHashes;
	private SequenceMapFinder $sequenceMapFinder;
	private EntityIdFinder $entityIdFinder;
	private $duplicateFinder;
	private $tableFieldUpdater;
	private $auxiliaryFields;
	private $factory;
	private Settings $settings;
	private Database $connection;

	protected function setUp(): void {
		$idCacheManager = new IdCacheManager(
			[
				'entity.id' => new InMemoryLruCache(),
				'entity.sort' => new InMemoryLruCache(),
				'entity.lookup' => new InMemoryLruCache(),
				'propertytable.hash' => new InMemoryLruCache()
			]
		);

		$this->cache = new InMemoryLruCache();

		$propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idEntityFinder = $this->getMockBuilder( IdEntityFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cacheWarmer = $this->getMockBuilder( CacheWarmer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->duplicateFinder = $this->getMockBuilder( DuplicateFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->tableFieldUpdater = $this->getMockBuilder( TableFieldUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableHashes = $this->getMockBuilder( PropertyTableHashes::class )
			->disableOriginalConstructor()
			->getMock();

		$this->sequenceMapFinder = $this->getMockBuilder( SequenceMapFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->auxiliaryFields = $this->getMockBuilder( AuxiliaryFields::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// EntityIdFinder::deferHashUpdate() (fired through real EntityIdFinder
		// via setMethods(null) below) calls newUpdateQueryBuilder() on the
		// connection. Default to an empty builder so tests that don't
		// override don't NPE on the ->update()->set()->where() chain.
		$this->connection->method( 'newUpdateQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockUpdateQueryBuilder() );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$redirectStore = new RedirectStore( $this->store, $titleFactory, $jobFactory );

		$this->settings = $this->getMockBuilder( Settings::class )
			->disableOriginalConstructor()
			->getMock();

		$this->factory = $this->getMockBuilder( SQLStoreFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newDuplicateFinder' )
			->willReturn( $this->duplicateFinder );

		$this->factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->willReturn( $idCacheManager );

		$this->factory->expects( $this->any() )
			->method( 'newRedirectStore' )
			->willReturn( $redirectStore );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyStatisticsStore' )
			->willReturn( $propertyStatisticsStore );

		$this->factory->expects( $this->any() )
			->method( 'newidEntityFinder' )
			->willReturn( $this->idEntityFinder );

		$this->factory->expects( $this->any() )
			->method( 'newTableFieldUpdater' )
			->willReturn( $this->tableFieldUpdater );

		$this->factory->expects( $this->any() )
			->method( 'newCacheWarmer' )
			->willReturn( $this->cacheWarmer );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyTableHashes' )
			->willReturn( $this->propertyTableHashes );

		$this->factory->expects( $this->any() )
			->method( 'newSequenceMapFinder' )
			->willReturn( $this->sequenceMapFinder );

		$this->factory->expects( $this->any() )
			->method( 'newAuxiliaryFields' )
			->willReturn( $this->auxiliaryFields );

		$this->entityIdFinder = $this->getMockBuilder( EntityIdFinder::class )
			->setConstructorArgs( [ $this->connection, $this->propertyTableHashes, $idCacheManager ] )
			->setMethods( null )
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newEntityIdFinder' )
			->willReturn( $this->entityIdFinder );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EntityIdManager::class,
			new EntityIdManager( $this->store, $this->factory, $this->settings )
		);
	}

	public function testRedirectInfoRoundtrip() {
		$subject = new WikiPage( 'Foo', 9001 );

		// This test exercises real RedirectStore (via EntityIdManager), so
		// the converted RedirectStore::select()/insert()/delete() chains need
		// chainable mock builders to avoid NPEing on `null->from(...)` etc.
		// Returned rows are empty — RedirectStore's cache fronts the DB after
		// addRedirect(), so the assertions don't depend on DB row content.
		$this->connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );
		$this->connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockInsertQueryBuilder() );
		$this->connection->method( 'newDeleteQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockDeleteQueryBuilder() );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$this->assertFalse(
			$instance->isRedirect( $subject )
		);

		$instance->addRedirect( 42, 'Foo', 9001 );

		$this->assertEquals(
			42,
			$instance->findRedirect( 'Foo', 9001 )
		);

		$this->assertTrue(
			$instance->isRedirect( $subject )
		);

		$instance->deleteRedirect( 'Foo', 9001 );

		$this->assertSame(
			0,
			$instance->findRedirect( 'Foo', 9001 )
		);

		$this->assertFalse(
			$instance->isRedirect( $subject )
		);
	}

	public function testGetPropertyId() {
		$selectRow = new stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sort = '';
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_hash = '___hash___';

		$qb = $this->createMockSelectQueryBuilder( [ $selectRow ] );
		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory,
			$this->settings
		);

		$result = $instance->getSMWPropertyID( new Property( 'Foo' ) );

		$this->assertEquals( 9999, $result );
	}

	/**
	 * @dataProvider pageIdandSortProvider
	 */
	public function testGetSMWPageIDandSort( $parameters ) {
		$selectRow = new stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sort = '';
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_proptable_hash = serialize( 'Foo' );
		$selectRow->smw_hash = '___hash___';

		$qb = $this->createMockSelectQueryBuilder( [ $selectRow ] );
		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory,
			$this->settings
		);

		$sortkey = $parameters['sortkey'];

		$result = $instance->getSMWPageIDandSort(
			$parameters['title'],
			$parameters['namespace'],
			$parameters['iw'],
			$parameters['subobjectName'],
			$sortkey, // pass-by-reference
			$parameters['canonical'],
			$parameters['fetchHashes']
		);

		$this->assertEquals( 9999, $result );
	}

	/**
	 * @dataProvider pageIdandSortProvider
	 */
	public function testMakeSMWPageID( $parameters ) {
		$selectRow = new stdClass;
		$selectRow->smw_id = 0;
		$selectRow->o_id = 0;
		$selectRow->smw_sort = '';
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_proptable_hash = serialize( 'Foo' );
		$selectRow->smw_hash = '___hash___';

		$qb = $this->createMockSelectQueryBuilder( [ $selectRow ] );
		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$insertTables = $insertRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$this->connection->expects( $this->once() )
			->method( 'insertId' )
			->willReturn( 9999 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory,
			$this->settings
		);

		$instance->setEqualitySupport( SMW_EQ_SOME );

		$sortkey = $parameters['sortkey'];

		$result = $instance->makeSMWPageID(
			$parameters['title'],
			$parameters['namespace'],
			$parameters['iw'],
			$parameters['subobjectName'],
			$sortkey,
			$parameters['canonical'],
			$parameters['fetchHashes']
		);

		$this->assertEquals( 9999, $result );
		$this->assertSame( [ SQLStore::ID_TABLE ], $insertTables );
		$this->assertCount( 1, $insertRows );
	}

	public function testGetDataItemById() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->idEntityFinder->expects( $this->once() )
			->method( 'getDataItemById' )
			->with( 42 )
			->willReturn( new WikiPage( 'Foo', NS_MAIN ) );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$this->assertInstanceOf(
			WikiPage::class,
			$instance->getDataItemById( 42 )
		);
	}

	public function testUpdateInterwikiField() {
		$this->tableFieldUpdater->expects( $this->once() )
			->method( 'updateIwField' )
			->with(
				42,
				'Bar',
				sha1( json_encode( [ 'Foo', 0, 'Bar', '' ] ), true ) );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$instance->updateInterwikiField(
			42,
			new WikiPage( 'Foo', NS_MAIN, 'Bar' )
		);
	}

	public function testFindDuplicateEntries() {
		$expected = [
			'count' => 2,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$row = $expected;

		$this->duplicateFinder->expects( $this->atLeastOnce() )
			->method( 'findDuplicates' )
			->willReturnCallback( static function ( $table ) use ( $row ) {
				if ( $table === SQLStore::ID_TABLE ) {
					return [ $row ];
				}
				return [];
			} );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$this->assertEquals(
			[
				'smw_object_ids' => [ $expected ],
				'smw_fpt_redi' => [],
				'smw_di_wikipage' => []
			],
			$instance->findDuplicates( SQLStore::ID_TABLE )
		);
	}

	public function testGetIDOnPredefinedProperty() {
		$row = new stdClass;
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$qb = $this->createMockSelectQueryBuilder( [ $row ] );
		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory,
			$this->settings
		);

		$this->assertEquals(
			29,
			$instance->getId( new WikiPage( '_MDAT', SMW_NS_PROPERTY ) )
		);

		$this->assertEquals(
			42,
			$instance->getId( new WikiPage( '_MDAT', SMW_NS_PROPERTY, '', 'Foo' ) )
		);
	}

	public function testWarmUpCache() {
		$list = [
			new WikiPage( 'Bar', NS_MAIN )
		];

		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cacheWarmer->expects( $this->once() )
			->method( 'prepareCache' )
			->with( $list );

		$factory = $this->getMockBuilder( SQLStoreFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->willReturn( $idCacheManager );

		$factory->expects( $this->any() )
			->method( 'newIdEntityFinder' )
			->willReturn( $this->idEntityFinder );

		$factory->expects( $this->any() )
			->method( 'newCacheWarmer' )
			->willReturn( $this->cacheWarmer );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdManager(
			$store,
			$factory,
			$this->settings
		);

		$instance->warmUpCache( $list );
	}

	public function testFindAssociatedRev() {
		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'Foo',
			'smw_sort' => '',
			'smw_rev' => 1001,
		];

		$idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$factory = $this->getMockBuilder( SQLStoreFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->willReturn( $idCacheManager );

		$factory->expects( $this->any() )
			->method( 'newIdEntityFinder' )
			->willReturn( $this->idEntityFinder );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ], $whereConditions );
		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new EntityIdManager(
			$store,
			$factory,
			$this->settings
		);

		$this->assertEquals(
			1001,
			$instance->findAssociatedRev( 'Foo', NS_MAIN, '', '' )
		);

		$this->assertSame(
			[ [
				'smw_title' => 'Foo',
				'smw_namespace' => NS_MAIN,
				'smw_iw' => '',
				'smw_subobject' => '',
			] ],
			$whereConditions
		);
	}

	public function testPreload() {
		$subjects = [
			WikiPage::newFromText( 'Foo' )
		];

		$fieldList = $this->getMockBuilder( FieldList::class )
			->disableOriginalConstructor()
			->getMock();

		$fieldList->expects( $this->any() )
			->method( 'getHashList' )
			->willReturn( [] );

		$this->auxiliaryFields->expects( $this->once() )
			->method( 'prefetchFieldList' )
			->with( $subjects )
			->willReturn( $fieldList );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$instance->preload( $subjects );
	}

	public function testUpdateFieldMaps() {
		$this->auxiliaryFields->expects( $this->once() )
			->method( 'setFieldMaps' )
			->with(
				42,
				[ 'Foo' ] );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$instance->updateFieldMaps( 42, [ 'Foo' ], [ 'F' => 1 ] );
	}

	public function testGetSequenceMap() {
		$this->sequenceMapFinder->expects( $this->once() )
			->method( 'findMapById' )
			->with( 1001 );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$instance->getSequenceMap( 1001 );
	}

	public function testLoadSequenceMap() {
		$this->sequenceMapFinder->expects( $this->once() )
			->method( 'prefetchSequenceMap' )
			->with( $this->equalTo( [ 42, 1001 ] ) );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory,
			$this->settings
		);

		$instance->loadSequenceMap( [ 42, 1001 ] );
	}

	public function pageIdandSortProvider() {
		$provider[] = [ 'Foo', NS_MAIN, '', '', 'FOO', false, false ];
		$provider[] = [ 'Foo', NS_MAIN, '', '', 'FOO', true, false ];
		$provider[] = [ 'Foo', NS_MAIN, '', '', 'FOO', true, true ];
		$provider[] = [ 'Foo', NS_MAIN, 'quy', '', 'FOO', false, false ];
		$provider[] = [ 'Foo', NS_MAIN, 'quy', 'xwoo', 'FOO', false, false ];

		$provider[] = [ 'pro', SMW_NS_PROPERTY, '', '', 'PRO', false, false ];
		$provider[] = [ 'pro', SMW_NS_PROPERTY, '', '', 'PRO', true, false ];
		$provider[] = [ 'pro', SMW_NS_PROPERTY, '', '', 'PRO', true, true ];

		return $this->createAssociativeArrayFromProviderDefinition( $provider );
	}

	private function createAssociativeArrayFromProviderDefinition( $definitions ) {
		foreach ( $definitions as $map ) {
			$provider[] = [ [
				'title'         => $map[0],
				'namespace'     => $map[1],
				'iw'            => $map[2],
				'subobjectName' => $map[3],
				'sortkey'       => $map[4],
				'canonical'     => $map[5],
				'fetchHashes'   => $map[6]
			] ];
		}

		return $provider;
	}

}
