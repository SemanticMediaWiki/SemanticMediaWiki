<?php

namespace SMW\Tests\Integration\MediaWiki\Import\Maintenance;

use SMW\DIProperty;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\ByPageSemanticDataFinder;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class RebuildDataMaintenanceTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = array();
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

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

	public function testRebuildData() {

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

		$main = Title::newFromText( 'Lorem ipsum' );

		$expectedSomeProperties = array(
			'properties' => array(
				new DIProperty( 'Has boolean' ),
				new DIProperty( 'Has date' ),
				new DIProperty( 'Has email' ),
				new DIProperty( 'Has number' ),
				new DIProperty( 'Has page' ),
				new DIProperty( 'Has quantity' ),
				new DIProperty( 'Has temperature' ),
				new DIProperty( 'Has text' ),
				new DIProperty( 'Has Url' ),
				new DIProperty( 'Has annotation uri' )
			)
		);

		$this->maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( 'SMW\Maintenance\RebuildData' );
		$this->maintenanceRunner->setQuiet();

		$this->semanticDataFinder = new ByPageSemanticDataFinder;
		$this->semanticDataFinder->setTitle( $main )->setStore( $this->getStore() );

		$this->assertRunWithoutOptions( $expectedSomeProperties );
		$this->assertRunWithFullDeleteOption( $expectedSomeProperties );
		$this->assertRunWithIdRangeOption( $expectedSomeProperties );
		$this->assertRunWithCategoryOption( $expectedSomeProperties );
		$this->assertRunWithSparqlStoreForPropertyOption( $expectedSomeProperties );
		$this->assertRunWithSparqlStoreForQueryOption( $expectedSomeProperties );
	}

	protected function assertRunWithoutOptions( $expectedSomeProperties ) {
		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->run()
		);
	}

	protected function assertRunWithFullDeleteOption( $expectedSomeProperties ) {

		$options = array(
			'f' => true,
			'no-cache' => true,
			'debug' => true,
			'report-runtime' => true
		);

		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->setOptions( $options )->run()
		);
	}

	protected function assertRunWithIdRangeOption( $expectedSomeProperties ) {
		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->setOptions( array( 's' => 1, 'e' => 10 ) )->run()
		);
	}

	protected function assertRunWithCategoryOption( $expectedSomeProperties ) {
		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->setOptions( array( 'c' => true ) )->run()
		);
	}

	protected function assertRunWithSparqlStoreForPropertyOption( $expectedSomeProperties ) {
		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->setOptions( array(
				'p' => true,
				'b' => 'SMWSparqlStore' ) )->run()
		);
	}

	protected function assertRunWithSparqlStoreForQueryOption( $expectedSomeProperties ) {
		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->setOptions( array(
				'query' => '[[Has Url::+]]',
				'b' => 'SMWSparqlStore' ) )->run()
		);
	}

	private function assertThatPropertiesAreSet( $expectedSomeProperties, $runner ) {

		$this->assertTrue( $runner );

		$runPropertiesAreSetAssert = $this->semanticDataValidator->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->semanticDataFinder->fetchFromStore()
		);

		$this->assertTrue( $runPropertiesAreSetAssert );
	}

}
