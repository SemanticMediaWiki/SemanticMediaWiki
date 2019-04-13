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

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

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

		$this->assertInstanceOf(
			'\SMW\Maintenance\DataRebuilder',
			$instance->newDataRebuilder( $this->store )
		);
	}

	public function testCanConstructConceptCacheRebuilder() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\ConceptCacheRebuilder',
			$instance->newConceptCacheRebuilder( $this->store )
		);
	}

	public function testCanConstructPropertyStatisticsRebuilder() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\PropertyStatisticsRebuilder',
			$instance->newPropertyStatisticsRebuilder( $this->store )
		);
	}

	public function testCanConstructRebuildPropertyStatistics() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\RebuildPropertyStatistics',
			$instance->newRebuildPropertyStatistics()
		);
	}

	public function testCanConstructDuplicateEntitiesDisposer() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\DuplicateEntitiesDisposer',
			$instance->newDuplicateEntitiesDisposer( $this->store )
		);
	}

	public function testCanConstructMaintenanceLogger() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceLogger',
			$instance->newMaintenanceLogger( 'Foo' )
		);
	}

	public function testCanConstructAutoRecovery() {

		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\AutoRecovery',
			$instance->newAutoRecovery( 'Foo' )
		);
	}

}
