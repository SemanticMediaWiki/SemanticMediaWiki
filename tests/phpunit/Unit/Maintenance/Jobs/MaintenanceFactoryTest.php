<?php

namespace SMW\Tests\Unit\Maintenance\Jobs;

use PHPUnit\Framework\TestCase;
use SMW\Localizer\LocalMessageProvider;
use SMW\Maintenance\AutoRecovery;
use SMW\Maintenance\ConceptCacheRebuilder;
use SMW\Maintenance\DataRebuilder;
use SMW\Maintenance\DuplicateEntitiesDisposer;
use SMW\Maintenance\MaintenanceFactory;
use SMW\Maintenance\MaintenanceHelper;
use SMW\Maintenance\MaintenanceLogger;
use SMW\Maintenance\PropertyStatisticsRebuilder;
use SMW\Store;

/**
 * @covers \SMW\Maintenance\MaintenanceFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class MaintenanceFactoryTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MaintenanceFactory::class,
			new MaintenanceFactory()
		);
	}

	public function testCanConstructMaintenanceHelper() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			MaintenanceHelper::class,
			$instance->newMaintenanceHelper()
		);
	}

	public function testCanConstructLocalMessageProvider() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			LocalMessageProvider::class,
			$instance->newLocalMessageProvider( 'foo' )
		);
	}

	public function testCanConstructDataRebuilder() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			DataRebuilder::class,
			$instance->newDataRebuilder( $this->store )
		);
	}

	public function testCanConstructConceptCacheRebuilder() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			ConceptCacheRebuilder::class,
			$instance->newConceptCacheRebuilder( $this->store )
		);
	}

	public function testCanConstructPropertyStatisticsRebuilder() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			PropertyStatisticsRebuilder::class,
			$instance->newPropertyStatisticsRebuilder( $this->store )
		);
	}

	public function testCanConstructRebuildPropertyStatistics() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			'\SMW\Maintenance\rebuildPropertyStatistics',
			$instance->newRebuildPropertyStatistics()
		);
	}

	public function testCanConstructDuplicateEntitiesDisposer() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			DuplicateEntitiesDisposer::class,
			$instance->newDuplicateEntitiesDisposer( $this->store )
		);
	}

	public function testCanConstructMaintenanceLogger() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			MaintenanceLogger::class,
			$instance->newMaintenanceLogger( 'Foo' )
		);
	}

	public function testCanConstructAutoRecovery() {
		$instance = new MaintenanceFactory();

		$this->assertInstanceOf(
			AutoRecovery::class,
			$instance->newAutoRecovery( 'Foo' )
		);
	}

}
