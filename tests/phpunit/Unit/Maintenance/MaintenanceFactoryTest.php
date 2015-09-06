<?php

namespace SMW\Tests\Maintenance\Jobs;

use SMW\Maintenance\MaintenanceFactory;

/**
 * @covers \SMW\Maintenance\MaintenanceFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MaintenanceFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceFactory',
			new MaintenanceFactory()
		);
	}

	public function testCanConstructMaintenanceHelper() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceHelper',
			$instance->newMaintenanceHelper()
		);
	}

	public function testCanConstructDataRebuilder() {

		$instance = new MaintenanceFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\Maintenance\DataRebuilder',
			$instance->newDataRebuilder( $store )
		);
	}

	public function testCanConstructConceptCacheRebuilder() {

		$instance = new MaintenanceFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\Maintenance\ConceptCacheRebuilder',
			$instance->newConceptCacheRebuilder( $store )
		);
	}

	public function testCanConstructPropertyStatisticsRebuilder() {

		$instance = new MaintenanceFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\Maintenance\PropertyStatisticsRebuilder',
			$instance->newPropertyStatisticsRebuilder( $store, $propertyStatisticsStore )
		);
	}

}
