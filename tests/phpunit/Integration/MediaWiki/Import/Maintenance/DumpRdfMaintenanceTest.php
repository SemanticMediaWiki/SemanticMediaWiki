<?php

namespace SMW\Tests\Integration\MediaWiki\Import\Maintenance;

use SMW\Tests\Util\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

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

	protected $databaseToBeExcluded = array( 'postgres' );
	protected $destroyDatabaseTablesOnEachRun = true;

	private $importedTitles = array();
	private $runnerFactory;
	private $titleValidator;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

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

	public function testMaintenanceRdfOutput() {

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

		$expectedOutputContent = array(
			'<rdf:type rdf:resource="&wiki;Category-3ALorem_ipsum"/>',
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

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( 'SMW\Maintenance\DumpRdf' );
		$maintenanceRunner->setQuiet()->run();

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$maintenanceRunner->getOutput()
		);
	}

}
