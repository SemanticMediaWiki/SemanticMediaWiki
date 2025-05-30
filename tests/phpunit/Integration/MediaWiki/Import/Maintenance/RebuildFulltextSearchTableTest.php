<?php

namespace SMW\Tests\Integration\MediaWiki\Import\Maintenance;

use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group large
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class RebuildFulltextSearchTableTest extends SMWIntegrationTestCase {

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory  = $this->testEnvironment->getUtilityFactory()->newRunnerFactory();
		$this->titleValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newTitleValidator();

		// Remove any predisposed settings
		$this->testEnvironment->tearDown();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/../Fixtures/cicero-de-finibus.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown(): void {
		$this->testEnvironment->flushPages( $this->importedTitles );
		parent::tearDown();
	}

	public function testCanRun() {
		$this->importedTitles = [
			'De Finibus Bonorum et Malorum'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( '\SMW\Maintenance\rebuildFulltextSearchTable' );
		$maintenanceRunner->setQuiet()->run();
	}

}
