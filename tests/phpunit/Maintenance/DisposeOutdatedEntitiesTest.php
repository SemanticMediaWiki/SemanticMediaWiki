<?php

namespace SMW\Tests\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\DisposeOutdatedEntities;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Maintenance\DisposeOutdatedEntities
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DisposeOutdatedEntitiesTest extends TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $spyMessageReporter;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessagereporter();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DisposeOutdatedEntities::class,
			new DisposeOutdatedEntities()
		);
	}

	public function testExecute() {

		$instance = new DisposeOutdatedEntities();

		$instance->setMessageReporter(
			$this->spyMessageReporter
		);

		$instance->execute();

		$this->assertContains(
			'Outdated entitie(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
