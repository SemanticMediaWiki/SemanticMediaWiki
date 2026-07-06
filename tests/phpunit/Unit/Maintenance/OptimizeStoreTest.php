<?php

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\optimizeStore;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Maintenance\optimizeStore
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.1.0
 */
class OptimizeStoreTest extends TestCase {

	private $testEnvironment;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			optimizeStore::class,
			new optimizeStore()
		);
	}

	public function testExecuteOptimizesSqlStore() {
		$store = $this->createMock( SQLStore::class );

		$store->expects( $this->once() )
			->method( 'optimize' );

		$instance = new optimizeStore();
		$instance->setStore( $store );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->execute()
		);
	}

	public function testExecuteOptimizesSqlBaseStoreOfNonSqlStore() {
		$sqlStore = $this->createMock( SQLStore::class );

		$sqlStore->expects( $this->once() )
			->method( 'optimize' );

		$store = $this->createMock( SPARQLStore::class );
		$store->baseStore = $sqlStore;

		$instance = new optimizeStore();
		$instance->setStore( $store );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->execute()
		);
	}

	public function testExecuteSkipsNonSqlStore() {
		$store = $this->createMock( Store::class );

		$instance = new optimizeStore();
		$instance->setStore( $store );
		$instance->setMessageReporter( $this->spyMessageReporter );

		$this->assertTrue(
			$instance->execute()
		);

		$this->assertStringContainsString(
			'skipping',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
