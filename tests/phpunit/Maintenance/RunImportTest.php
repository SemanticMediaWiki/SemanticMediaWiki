<?php

namespace SMW\Tests\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\runImport;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Maintenance\runImport
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class RunImportTest extends TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessagereporter();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			runImport::class,
			new runImport()
		);
	}

	public function testExecute() {
		$instance = new runImport();

		$instance->setMessageReporter(
			$this->spyMessageReporter
		);

		$instance->execute();

		$this->assertContains(
			'Import task(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
