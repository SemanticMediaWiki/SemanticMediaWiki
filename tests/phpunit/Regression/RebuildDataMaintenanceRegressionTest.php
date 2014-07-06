<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\ByPageSemanticDataFinder;
use SMW\Tests\Util\MaintenanceRunner;
use SMW\Test\MwRegressionTestCase;

use SMW\DIProperty;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-regression
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class RebuildDataMaintenanceRegressionTest extends MwRegressionTestCase {

	protected $maintenanceRunner = null;
	protected $semanticDataValidator = null;
	protected $semanticDataFinder = null;

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'GenericLoremIpsumTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
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
	}

	public function assertDataImport() {

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

		$this->maintenanceRunner = new MaintenanceRunner( 'SMW\Maintenance\RebuildData' );
		$this->maintenanceRunner->setQuiet();

		$this->semanticDataValidator = new SemanticDataValidator;

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
		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$this->maintenanceRunner->setOptions( array( 'f' => true ) )->run()
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
