<?php

namespace SMW\Tests\Integration\MediaWiki\Import\Maintenance;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class RebuildSearchFieldContentMaintenanceTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = array();
	private $runnerFactory;
	private $titleValidator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/../Fixtures/' . 'GenericLoremIpsumTest-Mw-1-19-7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown() {
		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->importedTitles );

		parent::tearDown();
	}

	public function testRebuild() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( 'SMW\Maintenance\RebuildSearchFieldContent' );
		$maintenanceRunner->setQuiet()->run();
	}

}
