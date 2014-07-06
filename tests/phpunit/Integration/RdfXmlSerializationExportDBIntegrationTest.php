<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageDeleter;

use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-rdf
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RdfXmlSerializationExportDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWExportController',
			new ExportController( new RDFXMLSerializer() )
		);
	}

	public function testPrintRdfXmlForPageWithPropertyAnnotation() {

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( Title::newFromText( 'TestPrintRdfXmlForPageWithPropertyAnnotation', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit(
				'{{#set:|TestPrintRdfXmlForPageWithPropertyAnnotation=I--99--O|SomeOtherProperty=11PP33}}' );

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( array( __METHOD__ ) );
		$output = ob_get_clean();

		$expectedOutputContent = array(
			'<swivt:wikiNamespace rdf:datatype="http://www.w3.org/2001/XMLSchema#integer">0</swivt:wikiNamespace>',
			'<property:TestPrintRdfXmlForPageWithPropertyAnnotation rdf:resource="&wiki;I-2D-2D99-2D-2DO"/>',
			'<property:SomeOtherProperty rdf:resource="&wiki;11PP33"/>'
		);

		$this->assertThatOutputContains(
			$expectedOutputContent,
			$output
		);
	}

	public function testPrintRdfXmlForPageWithSubobjectPropertyAnnotation() {

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( Title::newFromText( 'TestPrintRdfXmlForPageWithSubobjectPropertyAnnotation', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::Page]]' );

		$pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit(
				'{{#subobject:|TestPrintRdfXmlForPageWithSubobjectPropertyAnnotation=I--11--O|@sortkey=X99Y}}' );

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( array( __METHOD__ ) );
		$output = ob_get_clean();

		$expectedOutputContent = array(
			'<property:TestPrintRdfXmlForPageWithSubobjectPropertyAnnotation rdf:resource="&wiki;I-2D-2D11-2D-2DO"/>',
			'<swivt:wikiPageSortKey rdf:datatype="http://www.w3.org/2001/XMLSchema#string">X99Y</swivt:wikiPageSortKey>'
		);

		$this->assertThatOutputContains(
			$expectedOutputContent,
			$output
		);
	}

	private function assertThatOutputContains( array $content, $output ) {
		foreach ( $content as $item ) {
			$this->assertContains( $item, $output );
		}
	}

}
