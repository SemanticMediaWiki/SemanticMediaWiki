<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\ApplicationFactory;

use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;

use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-export
 *
 * @group semantic-mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RdfXmlSerializationExportDBIntegrationTest extends MwDBaseUnitTestCase {

	private $pageCreator;
	private $stringValidator;

	private $smwgNamespace;
	private $subjects = array();

	protected function setUp() {
		parent::setUp();

		$this->pageCreator = UtilityFactory::getInstance()->newpageCreator();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		// FIXME
		// ApplicationFactory::getInstance()->getSettings()->set( 'smwgNamespace', "http://example.org/id/" );
		$this->smwgNamespace = $GLOBALS['smwgNamespace'];
		$GLOBALS['smwgNamespace'] = "http://example.org/id/";
	}

	protected function tearDown() {

		UtilityFactory::getInstance()->newPageDeleter()->doDeletePoolOfPages( $this->subjects );

		$GLOBALS['smwgNamespace'] = $this->smwgNamespace;
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWExportController',
			new ExportController( new RDFXMLSerializer() )
		);
	}

	public function testRdfXmlSerializationPrintoutForPagePropertyAnnotation() {

		$this->pageCreator
			->createPage( Title::newFromText( 'RdfXmlSerializationForPageProperty', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit(
				'{{#set:|RdfXmlSerializationForPageProperty=I--99--O|SomeOtherProperty=11PP33}}' );

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( array( __METHOD__ ) );
		$output = ob_get_clean();

		$expectedOutputContent = array(
			'<swivt:wikiNamespace rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">0</swivt:wikiNamespace>',
			'<property:RdfXmlSerializationForPageProperty rdf:resource="&wiki;I-2D-2D99-2D-2DO"/>',
			'<property:SomeOtherProperty rdf:resource="&wiki;11PP33"/>'
		);

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$output
		);
	}

	public function testRdfXmlSerializationPrintoutForSubobjectPropertyAnnotation() {

		$this->pageCreator
			->createPage( Title::newFromText( 'RdfXmlSerializationForSubobjectProperty', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit(
				'{{#subobject:|RdfXmlSerializationForSubobjectProperty=I--11--O|@sortkey=X99Y}}'.
				'{{#subobject:Caractères spéciaux|RdfXmlSerializationForSubobjectProperty={({[[&,,;-]]})} }}' );

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( array( __METHOD__ ) );
		$output = ob_get_clean();

		$expectedOutputContent = array(
			'<property:Has_subobject-23aux rdf:resource="&wiki;SMW-5CTests-5CIntegration-5CRdfXmlSerializationExportDBIntegrationTest-3A-3AtestRdfXmlSerializationPrintoutForSubobjectPropertyAnnotation-23_f5f4c4cfaded72e14bf120d9da479b6c"/>',
			'<property:Has_subobject-23aux rdf:resource="&wiki;SMW-5CTests-5CIntegration-5CRdfXmlSerializationExportDBIntegrationTest-3A-3AtestRdfXmlSerializationPrintoutForSubobjectPropertyAnnotation-23Caract-C3-A8res_sp-C3-A9ciaux"/>',

			'<property:RdfXmlSerializationForSubobjectProperty rdf:resource="&wiki;I-2D-2D11-2D-2DO"/>',
			'<swivt:wikiPageSortKey rdf:datatype="http://www.w3.org/2001/XMLSchema#string">X99Y</swivt:wikiPageSortKey>'
		);

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$output
		);
	}

	public function testRdfXmlSerializationPrintoutForDatePropertyAnnotation() {

		$this->pageCreator
			->createPage( Title::newFromText( 'RdfXmlSerializationForDateProperty', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Date]]' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit(
				'{{#subobject:|RdfXmlSerializationForDateProperty=1/1/1970}}' .
				'[[RdfXmlSerializationForDateProperty::31/12/2014]]' );

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( array( __METHOD__ ) );
		$output = ob_get_clean();

		// Could write a XPath validator to make the assert of elements, attributes
		// a bit more sane but currently there is no need and the string validation
		// works as well
		$expectedOutputContent = array(
			'<property:RdfXmlSerializationForDateProperty rdf:datatype="http://www.w3.org/2001/XMLSchema#date">2014-12-31Z</property:RdfXmlSerializationForDateProperty>',
			'<property:RdfXmlSerializationForDateProperty-23aux rdf:datatype="http://www.w3.org/2001/XMLSchema#double">2457022.5</property:RdfXmlSerializationForDateProperty-23aux>',

			// Subobject
			'<swivt:wikiPageSortKey rdf:datatype="http://www.w3.org/2001/XMLSchema#string">SMW\Tests\Integration\RdfXmlSerializationExportDBIntegrationTest::testRdfXmlSerializationPrintoutForDatePropertyAnnotation</swivt:wikiPageSortKey>',

			'<swivt:wikiPageSortKey rdf:datatype="http://www.w3.org/2001/XMLSchema#string">SMW\Tests\Integration\RdfXmlSerializationExportDBIntegrationTest::testRdfXmlSerializationPrintoutForDatePropertyAnnotation</swivt:wikiPageSortKey>',
			'<swivt:wikiNamespace rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">0</swivt:wikiNamespace>',
			'<property:RdfXmlSerializationForDateProperty rdf:datatype="http://www.w3.org/2001/XMLSchema#date">1970-01-01Z</property:RdfXmlSerializationForDateProperty>',
			'<property:RdfXmlSerializationForDateProperty-23aux rdf:datatype="http://www.w3.org/2001/XMLSchema#double">2440587.5</property:RdfXmlSerializationForDateProperty-23aux>',

			'<owl:DatatypeProperty rdf:about="http://example.org/id/Property-3ARdfXmlSerializationForDateProperty" />',
			'<owl:DatatypeProperty rdf:about="http://example.org/id/Property-3ARdfXmlSerializationForDateProperty-23aux" />',
		);

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$output
		);
	}

	// #795
	public function testRdfXmlSerializationForPageTypeProperty() {

		$this->pageCreator
			->createPage( Title::newFromText( 'RdfXmlSerializationForPageTypeProperty', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$output = $this->fetchSerializedRdfOutputFor(
			array( 'Property:RdfXmlSerializationForPageTypeProperty' )
		);

		$expectedOutputContent = array(
			'<owl:ObjectProperty rdf:about="http://example.org/id/Property-3ARdfXmlSerializationForPageTypeProperty">',
			'<rdfs:label>RdfXmlSerializationForPageTypeProperty</rdfs:label>',
			'<swivt:type rdf:resource="http://semantic-mediawiki.org/swivt/1.0#_wpg"/>'
		);

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$output
		);
	}

	private function fetchSerializedRdfOutputFor( array $pages ) {

		$this->subjects = $pages;

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( $pages );
		return ob_get_clean();
	}

}
