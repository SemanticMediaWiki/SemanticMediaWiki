<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\UtilityFactory;

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
 * @group semantic-mediawiki-database
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RdfXmlSerializationExportDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $pageCreator;
	private $stringValidator;

	private $smwgNamespace;

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
				'{{#subobject:|RdfXmlSerializationForSubobjectProperty=I--11--O|@sortkey=X99Y}}' );

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( array( __METHOD__ ) );
		$output = ob_get_clean();

		$expectedOutputContent = array(
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
			'<swivt:wikiPageSortKey>SMW\Tests\Integration\RdfXmlSerializationExportDBIntegrationTest::testRdfXmlSerializationPrintoutForDatePropertyAnnotation# edab041aad45f1b607fc26802f7c7cd2</swivt:wikiPageSortKey>',
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

}
