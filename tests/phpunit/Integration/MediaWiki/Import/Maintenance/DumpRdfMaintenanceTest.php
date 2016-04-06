<?php

namespace SMW\Tests\Integration\MediaWiki\Import\Maintenance;

use SMW\ApplicationFactory;
use SMW\EventHandler;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DumpRdfMaintenanceTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = array();
	private $runnerFactory;
	private $titleValidator;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( "Skipping this test, MW 1.19 doesn't clean-up the title cache correctly." );
		}

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		ApplicationFactory::getInstance()->getSettings()->set( 'smwgExportBCAuxiliaryUse', true );
		EventHandler::getInstance()->getEventDispatcher()->dispatch( 'exporter.reset' );

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/../Fixtures/' . 'GenericLoremIpsumTest-Mw-1-19-7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown() {
		ApplicationFactory::getInstance()->clear();

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->importedTitles );

		parent::tearDown();
	}

	public function testMaintenanceRdfOutput() {

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->importedTitles = array(
			'Category:Lorem ipsum',
			'Lorem ipsum',
			'Elit Aliquam urna interdum',
			'Platea enim hendrerit',
			'Property:Has Url',
			'Property:Has annotation uri',
			'Property:Has boolean',
			'Property:Has date',
			'Property:Has email',
			'Property:Has number',
			'Property:Has page',
			'Property:Has quantity',
			'Property:Has temperature',
			'Property:Has text'
		);

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( 'SMW\Maintenance\DumpRdf' );
		$maintenanceRunner->setQuiet();

		$this->doExportForDefaultOptions( $maintenanceRunner );
		$this->doExportForPageOption( $maintenanceRunner );
	}

	private function doExportForDefaultOptions( $maintenanceRunner ) {

		$expectedOutputContent = array(
		//	'<rdf:type rdf:resource="&wiki;Category-3ALorem_ipsum"/>',
			'<rdfs:label>Lorem ipsum</rdfs:label>',
			'<rdfs:label>Has annotation uri</rdfs:label>',
			'<rdfs:label>Has boolean</rdfs:label>',
			'<rdfs:label>Has date</rdfs:label>',
			'<rdfs:label>Has email</rdfs:label>',
			'<rdfs:label>Has number</rdfs:label>',
			'<rdfs:label>Has page</rdfs:label>',
			'<rdfs:label>Has quantity</rdfs:label>',
			'<rdfs:label>Has temperature</rdfs:label>',
			'<rdfs:label>Has text</rdfs:label>',
			'<rdfs:label>Has Url</rdfs:label>',
		);

		$maintenanceRunner->run();

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$maintenanceRunner->getOutput()
		);
	}

	private function doExportForPageOption( $maintenanceRunner ) {

		$expectedOutputContent = array(
			'<rdfs:label>Lorem ipsum</rdfs:label>',
			'<swivt:masterPage rdf:resource="&wiki;Lorem_ipsum"/>',
			'<property:Has_subobject-23aux rdf:resource="&wiki;Lorem_ipsum-23_017ced50ca5208f4cc77f90c43a0d4a9"/>',
			'<swivt:wikiPageSortKey rdf:datatype="http://www.w3.org/2001/XMLSchema#string">Lorem ipsum</swivt:wikiPageSortKey>'
		);

		$maintenanceRunner
			->setOptions( array( 'page' => 'Lorem ipsum' ) )
			->run();

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$maintenanceRunner->getOutput()
		);
	}

}
