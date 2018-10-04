<?php

namespace SMW\Tests\SQLStore;

use SMW\Options;
use SMW\SQLStore\SQLStoreFactory;
use SMW\Tests\TestEnvironment;
use SMWSQLStore3;

/**
 * @covers \SMW\SQLStore\SQLStoreFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStoreFactory',
			new SQLStoreFactory( $this->store )
		);
	}

	public function testNewSlaveQueryEngineReturnType() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newSlaveQueryEngine()
		);
	}

	public function testNewMasterQueryEngineReturnType() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'\SMW\QueryEngine',
			$instance->newMasterQueryEngine()
		);
	}

	public function testNewMasterConceptCache() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ConceptCache',
			$instance->newMasterConceptCache()
		);
	}

	public function testNewSlaveConceptCache() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ConceptCache',
			$instance->newSlaveConceptCache()
		);
	}

	public function testCanConstractEntityIdManager() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMWSql3SmwIds',
			$instance->newEntityTable()
		);
	}

	public function testCanConstructUsageStatisticsCachedListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newUsageStatisticsCachedListLookup()
		);
	}

	public function testCanConstructPropertyUsageCachedListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newPropertyUsageCachedListLookup( null )
		);
	}

	public function testCanConstructUnusedPropertyCachedListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newUnusedPropertyCachedListLookup( null )
		);
	}

	public function testCanConstructUndeclaredPropertyCachedListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newUndeclaredPropertyCachedListLookup( null, '_foo' )
		);
	}

	public function testCanConstructCachedListLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedListLookup',
			$instance->newCachedListLookup( $listLookup, true, 42 )
		);
	}

	public function testCanConstructEntityLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->testEnvironment->addConfiguration( 'smwgEntityLookupCacheType', CACHE_NONE );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\NativeEntityLookup',
			$instance->newEntityLookup()
		);

		$this->testEnvironment->addConfiguration( 'smwgEntityLookupCacheType', 'hash' );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\CachingEntityLookup',
			$instance->newEntityLookup()
		);
	}

	public function testCanConstructPropertyTableIdReferenceFinder() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\PropertyTableIdReferenceFinder',
			$instance->newPropertyTableIdReferenceFinder()
		);
	}

	public function testCanConstructDataItemHandlerDispatcher() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\DataItemHandlerDispatcher',
			$instance->newDataItemHandlerDispatcher()
		);
	}

	public function testCanConstructDeferredCachedListLookupUpdate() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\DeferredCallableUpdate',
			$instance->newDeferredCallableCachedListLookupUpdate()
		);
	}

	public function testCanConstructInstaller() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue( 'mysql' ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getOptions' )
			->will( $this->returnValue( new Options() ) );

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SQLStoreFactory( $store );

		$this->assertInstanceOf(
			'SMW\SQLStore\Installer',
			$instance->newInstaller()
		);
	}

	public function testGetLogger() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\Psr\Log\LoggerInterface',
			$instance->getLogger()
		);
	}

	public function testCanConstrucTraversalPropertyLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\TraversalPropertyLookup',
			$instance->newTraversalPropertyLookup()
		);
	}

	public function testCanConstrucPropertySubjectsLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\PropertySubjectsLookup',
			$instance->newPropertySubjectsLookup()
		);
	}

	public function testCanConstructPropertyStatisticsStore() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyStatisticsStore',
			$instance->newPropertyStatisticsStore()
		);
	}

	public function testCanConstructIdCacheManager() {

		$instance = new SQLStoreFactory( $this->store );

		$params = [
			'entity.id' => '',
			'entity.sort' => '',
			'entity.lookup' => '',
			'table.hash' => ''
		];

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\IdCacheManager',
			$instance->newIdCacheManager( 'foo', $params )
		);
	}

	public function testCanConstrucPropertyTableRowDiffer() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableRowDiffer',
			$instance->newPropertyTableRowDiffer()
		);
	}

	public function testCanConstructIdEntityFinder() {

		$idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\IdEntityFinder',
			$instance->newIdEntityFinder( $idCacheManager )
		);
	}

	public function testCanConstructIdChanger() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\IdChanger',
			$instance->newIdChanger()
		);
	}

	public function testCanConstructUniquenessLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\UniquenessLookup',
			$instance->newUniquenessLookup()
		);
	}

	public function testCanConstructHierarchyLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\HierarchyLookup',
			$instance->newHierarchyLookup()
		);
	}

	public function testCanConstructSubobjectListFinder() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\SubobjectListFinder',
			$instance->newSubobjectListFinder()
		);
	}

	public function testCanConstructSemanticDataLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\CachingSemanticDataLookup',
			$instance->newSemanticDataLookup()
		);
	}

	public function testCanConstructTableFieldUpdater() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableFieldUpdater',
			$instance->newTableFieldUpdater()
		);
	}

	public function testCanConstructRedirectStore() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\RedirectStore',
			$instance->newRedirectStore()
		);
	}

	public function testCanConstructChangePropListener() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\ChangePropListener',
			$instance->newChangePropListener()
		);
	}

	public function testCanConstructChangeOp() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\ChangeOp\ChangeOp',
			$instance->newChangeOp( $subject )
		);
	}

	public function testCanConstructProximityPropertyValueLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\ProximityPropertyValueLookup',
			$instance->newProximityPropertyValueLookup()
		);
	}

	public function testCanConstructEntityValueUniquenessConstraintChecker() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityValueUniquenessConstraintChecker',
			$instance->newEntityValueUniquenessConstraintChecker()
		);
	}

	public function testCanConstructServicesContainer() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\Services\ServicesContainer',
			$instance->newServicesContainer()
		);
	}

}
