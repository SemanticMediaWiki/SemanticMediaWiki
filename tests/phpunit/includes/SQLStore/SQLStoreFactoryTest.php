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

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStoreFactory',
			new SQLStoreFactory( $store )
		);
	}

	private function newInstance() {
		return new SQLStoreFactory( new SMWSQLStore3() );
	}

	public function testNewSlaveQueryEngineReturnType() {
		$this->assertInstanceOf(
			'SMWSQLStore3QueryEngine',
			$this->newInstance()->newSlaveQueryEngine()
		);
	}

	public function testNewMasterQueryEngineReturnType() {
		$this->assertInstanceOf(
			'SMWSQLStore3QueryEngine',
			$this->newInstance()->newMasterQueryEngine()
		);
	}

	public function testNewMasterConceptCache() {
		$this->assertInstanceOf(
			'SMW\SQLStore\ConceptCache',
			$this->newInstance()->newMasterConceptCache()
		);
	}

	public function testNewSlaveConceptCache() {
		$this->assertInstanceOf(
			'SMW\SQLStore\ConceptCache',
			$this->newInstance()->newSlaveConceptCache()
		);
	}

	public function testCanConstructUsageStatisticsListLookup() {
		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\UsageStatisticsListLookup',
			$this->newInstance()->newUsageStatisticsListLookup()
		);
	}

	public function testCanConstructPropertyUsageListLookup() {
		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\PropertyUsageListLookup',
			$this->newInstance()->newPropertyUsageListLookup( null )
		);
	}

	public function testCanConstructUnusedPropertyListLookup() {
		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\UnusedPropertyListLookup',
			$this->newInstance()->newUnusedPropertyListLookup( null )
		);
	}

	public function testCanConstructUndeclaredPropertyListLookup() {
		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup',
			$this->newInstance()->newUndeclaredPropertyListLookup( null, '_foo' )
		);
	}

	public function testCanConstructCachedListLookup() {

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\SQLStore\ListLookup\CachedListLookup',
			$this->newInstance()->newCachedListLookup( $listLookup, true, 42 )
		);
	}

}
