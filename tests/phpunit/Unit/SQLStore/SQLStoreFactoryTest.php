<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\SQLStoreFactory;
use SMW\Store;
use SMW\Options;
use SMWSQLStore3;
use SMW\Tests\TestEnvironment;

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

	public function testCanConstractIdTableManager() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMWSql3SmwIds',
			$instance->newIdTableManager()
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

	public function testCanConstructRequestOptionsProcessor() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\RequestOptionsProcessor',
			$instance->newRequestOptionsProcessor()
		);
	}

	public function testCanConstructEntityLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$this->testEnvironment->addConfiguration( 'smwgValueLookupCacheType', CACHE_NONE );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\DirectEntityLookup',
			$instance->newEntityLookup()
		);

		$this->testEnvironment->addConfiguration( 'smwgValueLookupCacheType', 'hash' );

		$this->assertInstanceOf(
			'SMW\SQLStore\EntityStore\CachedEntityLookup',
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

	public function testCanConstrucPropertyStatisticsTable() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyStatisticsTable',
			$instance->newPropertyStatisticsTable()
		);
	}

}
