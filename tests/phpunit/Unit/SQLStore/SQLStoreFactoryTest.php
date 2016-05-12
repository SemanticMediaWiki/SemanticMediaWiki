<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\SQLStoreFactory;
use SMW\Store;
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

	protected function setUp() {
		parent::setUp();

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
			'\SMW\SQLStore\QueryEngine\QueryEngine',
			$instance->newSlaveQueryEngine()
		);
	}

	public function testNewMasterQueryEngineReturnType() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryEngine',
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

	public function testCanConstrucCachedValueLookupStore() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\Lookup\CachedValueLookupStore',
			$instance->newCachedValueLookupStore()
		);
	}

	public function testCanConstructPropertyTableIdReferenceFinder() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\SQLStore\PropertyTableIdReferenceFinder',
			$instance->newPropertyTableIdReferenceFinder()
		);
	}

	public function testCanConstructDeferredCachedListLookupUpdate() {

		$instance = new SQLStoreFactory( $this->store );

		$this->assertInstanceOf(
			'SMW\DeferredCallableUpdate',
			$instance->newDeferredCallableCachedListLookupUpdate()
		);
	}

}
