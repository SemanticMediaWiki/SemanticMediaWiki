<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\SQLStoreFactory;
use SMW\Store;
use SMWSQLStore3;

/**
 * @covers \SMW\SQLStore\SQLStoreFactory
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactoryTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp(){
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

	public function testCanConstructUsageStatisticsListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\UsageStatisticsListLookup',
			$instance->newUsageStatisticsListLookup()
		);
	}

	public function testCanConstructPropertyUsageListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\PropertyUsageListLookup',
			$instance->newPropertyUsageListLookup( null )
		);
	}

	public function testCanConstructUnusedPropertyListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\UnusedPropertyListLookup',
			$instance->newUnusedPropertyListLookup( null )
		);
	}

	public function testCanConstructUndeclaredPropertyListLookup() {

		$instance = new SQLStoreFactory( new SMWSQLStore3() );

		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup',
			$instance->newUndeclaredPropertyListLookup( null, '_foo' )
		);
	}

	public function testCanConstructCachedListLookup() {

		$instance = new SQLStoreFactory( $this->store );

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\CachedListLookup',
			$instance->newCachedListLookup( $listLookup, true, 42 )
		);
	}

}
