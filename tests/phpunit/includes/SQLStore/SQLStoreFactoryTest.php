<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\SQLStoreFactory;
use SMW\Store;
use SMWSQLStore3;

/**
 * @covers SMW\SQLStore\SQLStoreFactory
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactoryTest extends \PHPUnit_Framework_TestCase {

	private function newInstance() {
		return new SQLStoreFactory( new SMWSQLStore3() );
	}

	public function testNewSalveQueryEngineReturnType() {
		$this->assertInstanceOf(
			'SMWSQLStore3QueryEngine',
			$this->newInstance()->newSalveQueryEngine()
		);
	}

	public function testNewMasterQueryEngineReturnType() {
		$this->assertInstanceOf(
			'SMWSQLStore3QueryEngine',
			$this->newInstance()->newMasterQueryEngine()
		);
	}

	public function testNewSlaveConceptCacheReturnType() {
		$this->assertInstanceOf(
			'SMW\SQLStore\QueryEngine\ConceptCache',
			$this->newInstance()->newSlaveConceptCache()
		);
	}

	public function testCanConstructUsageStatisticsListLookup() {
		$this->assertInstanceOf(
			'SMW\SQLStore\UsageStatisticsListLookup',
			$this->newInstance()->newUsageStatisticsListLookup()
		);
	}

	public function testCanConstructPropertyUsageListLookup() {
		$this->assertInstanceOf(
			'SMW\SQLStore\PropertyUsageListLookup',
			$this->newInstance()->newPropertyUsageListLookup( null )
		);
	}

}
