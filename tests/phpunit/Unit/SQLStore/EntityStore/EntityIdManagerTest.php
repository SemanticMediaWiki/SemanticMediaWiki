<?php

namespace SMW\Tests\SQLStore;

use Onoi\Cache\FixedInMemoryLruCache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\IdCacheManager;
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
class EntityIdManagerTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $cache;
	private $idMatchFinder;
	private $duplicateFinder;
	private $tableFieldUpdater;
	private $auxiliaryFields;
	private $factory;

	protected function setUp() : void {

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

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$redirectStore = new RedirectStore( $this->store );

		$this->factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newDuplicateFinder' )
			->will( $this->returnValue( $this->duplicateFinder ) );

		$this->factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->will( $this->returnValue( $idCacheManager ) );

		$this->factory->expects( $this->any() )
			->method( 'newRedirectStore' )
			->will( $this->returnValue( $redirectStore ) );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyStatisticsStore' )
			->will( $this->returnValue( $propertyStatisticsStore ) );

		$this->factory->expects( $this->any() )
			->method( 'newidEntityFinder' )
			->will( $this->returnValue( $this->idEntityFinder ) );

		$this->factory->expects( $this->any() )
			->method( 'newTableFieldUpdater' )
			->will( $this->returnValue( $this->tableFieldUpdater ) );

		$this->factory->expects( $this->any() )
			->method( 'newCacheWarmer' )
			->will( $this->returnValue( $this->cacheWarmer ) );

		$this->factory->expects( $this->any() )
			->method( 'newPropertyTableHashes' )
			->will( $this->returnValue( $this->propertyTableHashes ) );

		$this->factory->expects( $this->any() )
			->method( 'newSequenceMapFinder' )
			->will( $this->returnValue( $this->sequenceMapFinder ) );

		$this->factory->expects( $this->any() )
			->method( 'newAuxiliaryFields' )
			->will( $this->returnValue( $this->auxiliaryFields ) );

		$this->entityIdFinder = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdFinder' )
			->setConstructorArgs( [ $this->connection, $this->propertyTableHashes, $idCacheManager ] )
			->setMethods( null )
			->getMock();

		$this->factory->expects( $this->any() )
			->method( 'newEntityIdFinder' )
			->will( $this->returnValue( $this->entityIdFinder ) );
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

		$this->assertEquals(
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
			->will( $this->returnValue( $selectRow ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

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
			->will( $this->returnValue( $selectRow ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

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
			->will( $this->returnValue( $selectRow ) );

		$this->connection->expects( $this->once() )
			->method( 'insertId' )
			->will( $this->returnValue( 9999 ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

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
			->will( $this->returnValue( $connection ) );

		$this->idEntityFinder->expects( $this->once() )
			->method( 'getDataItemById' )
			->with( $this->equalTo( 42 ) )
			->will( $this->returnValue( new DIWikiPage( 'Foo', NS_MAIN ) ) );

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
				$this->equalTo( 42 ),
				$this->equalTo( 'Bar' ),
				$this->equalTo( '8ba1886210e332a1fbaf28c38e43d1e89dc761db' ) );

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
			->with( $this->equalTo( \SMW\SQLStore\SQLStore::ID_TABLE ) )
			->will( $this->returnValue( [ $row ] ) );

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
			->will( $this->returnValue( $row ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

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
			->with( $this->equalTo( $list ) );

		$factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->will( $this->returnValue( $idCacheManager ) );

		$factory->expects( $this->any() )
			->method( 'newIdEntityFinder' )
			->will( $this->returnValue( $this->idEntityFinder ) );

		$factory->expects( $this->any() )
			->method( 'newCacheWarmer' )
			->will( $this->returnValue( $this->cacheWarmer ) );

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
			->will( $this->returnValue( $this->cache ) );

		$factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newIdCacheManager' )
			->will( $this->returnValue( $idCacheManager ) );

		$factory->expects( $this->any() )
			->method( 'newIdEntityFinder' )
			->will( $this->returnValue( $this->idEntityFinder ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

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
			->will( $this->returnValue( [] ) );

		$this->auxiliaryFields->expects( $this->once() )
			->method( 'prefetchFieldList' )
			->with( $this->equalTo( $subjects ) )
			->will( $this->returnValue( $fieldList ) );

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
				$this->equalTo( 42 ),
				$this->equalTo( [ 'Foo' ] ) );

		$instance = new EntityIdManager(
			$this->store,
			$this->factory
		);

		$instance->updateFieldMaps( 42, [ 'Foo' ], [ 'F' => 1 ] );
	}

	public function testGetSequenceMap() {

		$this->sequenceMapFinder->expects( $this->once() )
			->method( 'findMapById' )
			->with( $this->equalTo( 1001 ) );

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

		$provider[] = [ 'Foo', NS_MAIN, '' , '', 'FOO', false, false ];
		$provider[] = [ 'Foo', NS_MAIN, '' , '', 'FOO', true, false ];
		$provider[] = [ 'Foo', NS_MAIN, '' , '', 'FOO', true, true ];
		$provider[] = [ 'Foo', NS_MAIN, 'quy' , '', 'FOO', false, false ];
		$provider[] = [ 'Foo', NS_MAIN, 'quy' , 'xwoo', 'FOO', false, false ];

		$provider[] = [ 'pro', SMW_NS_PROPERTY, '' , '', 'PRO', false, false ];
		$provider[] = [ 'pro', SMW_NS_PROPERTY, '' , '', 'PRO', true, false ];
		$provider[] = [ 'pro', SMW_NS_PROPERTY, '' , '', 'PRO', true, true ];

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
