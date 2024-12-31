<?php

namespace SMW\Tests\SQLStore;

use Onoi\Cache\FixedInMemoryLruCache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\SQLStore\EntityStore\EntityIdFinder;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\EntityStore\EntityIdManager;

/**
 * @covers \SMW\SQLStore\EntityStore\EntityIdManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class EntityIdManagerTest extends \PHPUnit\Framework\TestCase {

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
	private Database $connection;

	protected function setUp(): void {
		$idCacheManager = new IdCacheManager(
			[
				'entity.id' => new FixedInMemoryLruCache(),
				'entity.sort' => new FixedInMemoryLruCache(),
				'entity.lookup' => new FixedInMemoryLruCache(),
				'propertytable.hash' => new FixedInMemoryLruCache()
			]
		);

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->idEntityFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdEntityFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->cacheWarmer = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CacheWarmer' )
			->disableOriginalConstructor()
			->getMock();

		$this->duplicateFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DuplicateFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableFieldUpdater = $this->getMockBuilder( '\SMW\SQLStore\TableFieldUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableHashes = $this->getMockBuilder( '\SMW\SQLStore\PropertyTable\PropertyTableHashes' )
			->disableOriginalConstructor()
			->getMock();

		$this->sequenceMapFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\SequenceMapFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->auxiliaryFields = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\AuxiliaryFields' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$redirectStore = new RedirectStore( $this->store );

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
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

		$this->entityIdFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdFinder' )
			->setConstructorArgs( [ $this->connection, $this->propertyTableHashes, $idCacheManager ] )
			->onlyMethods( [] )
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newEntityIdFinder' )
			->willReturn( $this->entityIdFinder );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\EntityIdManager',
			new EntityIdManager( $this->store, $this->factory )
		);
	}

	public function testRedirectInfoRoundtrip() {
		$subject = new DIWikiPage( 'Foo', 9001 );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
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
		$selectRow = new \stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sort = '';
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_hash = '___hash___';

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( $selectRow );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory
		);

		$result = $instance->getSMWPropertyID( new DIProperty( 'Foo' ) );

		$this->assertEquals( 9999, $result );
	}

	/**
	 * @dataProvider pageIdandSortProvider
	 */
	public function testGetSMWPageIDandSort( $parameters ) {
		$selectRow = new \stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sort = '';
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_proptable_hash = serialize( 'Foo' );
		$selectRow->smw_hash = '___hash___';

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( $selectRow );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory
		);

		$sortkey = $parameters['sortkey'];

		$result  = $instance->getSMWPageIDandSort(
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
		$selectRow = new \stdClass;
		$selectRow->smw_id = 0;
		$selectRow->o_id = 0;
		$selectRow->smw_sort = '';
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_proptable_hash = serialize( 'Foo' );
		$selectRow->smw_hash = '___hash___';

		$this->connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( $selectRow );

		$this->connection->expects( $this->once() )
			->method( 'insertId' )
			->willReturn( 9999 );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory
		);

		$instance->setEqualitySupport( SMW_EQ_SOME );

		$sortkey = $parameters['sortkey'];

		$result  = $instance->makeSMWPageID(
			$parameters['title'],
			$parameters['namespace'],
			$parameters['iw'],
			$parameters['subobjectName'],
			$sortkey,
			$parameters['canonical'],
			$parameters['fetchHashes']
		);

		$this->assertEquals( 9999, $result );
	}

	public function testGetDataItemById() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->idEntityFinder->expects( $this->once() )
			->method( 'getDataItemById' )
			->with( 42 )
			->willReturn( new DIWikiPage( 'Foo', NS_MAIN ) );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemById( 42 )
		);
	}

	public function testUpdateInterwikiField() {
		$this->tableFieldUpdater->expects( $this->once() )
			->method( 'updateIwField' )
			->with(
				42,
				'Bar',
				'8ba1886210e332a1fbaf28c38e43d1e89dc761db' );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
		);

		$instance->updateInterwikiField(
			42,
			new DIWikiPage( 'Foo', NS_MAIN, 'Bar' )
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

		$this->duplicateFinder->expects( $this->at( 0 ) )
			->method( 'findDuplicates' )
			->with( \SMW\SQLStore\SQLStore::ID_TABLE )
			->willReturn( [ $row ] );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
		);

		$this->assertEquals(
			[
				'smw_object_ids' => [ $expected ],
				'smw_fpt_redi' => null,
				'smw_di_wikipage' => null
			],
			$instance->findDuplicates( \SMW\SQLStore\SQLStore::ID_TABLE )
		);
	}

	public function testGetIDOnPredefinedProperty() {
		$row = new \stdClass;
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( $row );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new EntityIdManager(
			$store,
			$this->factory
		);

		$this->assertEquals(
			29,
			$instance->getId( new DIWikiPage( '_MDAT', SMW_NS_PROPERTY ) )
		);

		$this->assertEquals(
			42,
			$instance->getId( new DIWikiPage( '_MDAT', SMW_NS_PROPERTY, '', 'Foo' ) )
		);
	}

	public function testWarmUpCache() {
		$list = [
			new DIWikiPage( 'Bar', NS_MAIN )
		];

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->cacheWarmer->expects( $this->once() )
			->method( 'prepareCache' )
			->with( $list );

		$factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdManager(
			$store,
			$factory
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

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->willReturn( $idCacheManager );

		$factory->expects( $this->any() )
			->method( 'newIdEntityFinder' )
			->willReturn( $this->idEntityFinder );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new EntityIdManager(
			$store,
			$factory
		);

		$this->assertEquals(
			1001,
			$instance->findAssociatedRev( 'Foo', NS_MAIN, '', '' )
		);
	}

	public function testPreload() {
		$subjects = [
			DIWikiPage::newFromText( 'Foo' )
		];

		$fieldList = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\FieldList' )
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
			$this->factory
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
			$this->factory
		);

		$instance->updateFieldMaps( 42, [ 'Foo' ], [ 'F' => 1 ] );
	}

	public function testGetSequenceMap() {
		$this->sequenceMapFinder->expects( $this->once() )
			->method( 'findMapById' )
			->with( 1001 );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
		);

		$instance->getSequenceMap( 1001 );
	}

	public function testLoadSequenceMap() {
		$this->sequenceMapFinder->expects( $this->once() )
			->method( 'prefetchSequenceMap' )
			->with( $this->equalTo( [ 42, 1001 ] ) );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
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
