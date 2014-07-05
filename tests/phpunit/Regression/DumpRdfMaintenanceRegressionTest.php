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
 * @group semantic-mediawiki-maintenance
 * @group semantic-mediawiki-rdf
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DumpRdfMaintenanceRegressionTest extends MwRegressionTestCase {

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

		$this->maintenanceRunner = new MaintenanceRunner( 'SMW\Maintenance\DumpRdf' );
		$this->maintenanceRunner->setQuiet()->run();

		$this->assertThatOutputContains(
			$expectedOutputContent,
			$this->maintenanceRunner->getOutput()
		);
	}

	private function assertThatOutputContains( array $content, $output ) {
		foreach ( $content as $item ) {
			$this->assertContains( $item, $output );
		}
	}

}
