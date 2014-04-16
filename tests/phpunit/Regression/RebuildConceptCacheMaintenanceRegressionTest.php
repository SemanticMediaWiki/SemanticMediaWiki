<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\MaintenanceRunner;
use SMW\Tests\Util\PageCreator;
use SMW\Test\MwRegressionTestCase;

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
class RebuildConceptCacheMaintenanceRegressionTest extends MwRegressionTestCase {

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

		$conceptPage = $this->createConceptPage( 'Lorem ipsum concept', '[[Category:Lorem ipsum]]' );

		$maintenanceRunner = new MaintenanceRunner( 'SMW\Maintenance\RebuildConceptCache' );
		$maintenanceRunner->setQuiet();

		$maintenanceRunner->setOptions( array( 'status' => true ) )->run();
		$this->assertConceptCacheStatus( $conceptPage );

		$maintenanceRunner->setOptions( array( 'create' => true ) )->run();
		$maintenanceRunner->setOptions( array( 'delete' => true ) )->run();
		$maintenanceRunner->setOptions( array( 'create' => true, 's' => 1 ) )->run();
		$maintenanceRunner->setOptions( array( 'create' => true, 's' => 1, 'e' => 100 ) )->run();
		$maintenanceRunner->setOptions( array( 'create' => true, 'update' => true, 'old' => 1 ) )->run();
		$maintenanceRunner->setOptions( array( 'delete' => true, 'concept' => 'Lorem ipsum concept' ) )->run();
	}

	protected function assertConceptCacheStatus( $conceptPage ) {

		$concept = $this->getStore()->getConceptCacheStatus( $conceptPage->getTitle() );

		$this->assertInstanceOf( 'SMW\DIConcept', $concept );
	}

	protected function createConceptPage( $name, $condition ) {

		$pageCreator = new PageCreator();
		$pageCreator
			->createPage( Title::newFromText( $name, SMW_NS_CONCEPT ) )
			->doEdit( "{{#concept: {$condition} }}" );

		return $pageCreator->getPage();
	}

}
