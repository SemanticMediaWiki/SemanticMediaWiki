<?php

namespace SMW\Tests\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\RunImport;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Maintenance\RunImport
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class RunImportTest extends TestCase {

	private $testEnvironment;
	private $spyMessageReporter;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessagereporter();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RunImport::class,
			new RunImport()
		);
	}

	public function testExecute() {

		$instance = new RunImport();

		$instance->setMessageReporter(
			$this->spyMessageReporter
		);

		$instance->execute();

		$this->assertContains(
			'Import tasks',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
