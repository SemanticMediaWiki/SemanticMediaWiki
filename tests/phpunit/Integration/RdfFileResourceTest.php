<?php

namespace SMW\Tests\Integration;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Tests\MwDBaseUnitTestCase;
use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class RdfFileResourceTest extends MwDBaseUnitTestCase {

	private $fixturesFileProvider;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();

		$this->testEnvironment->withConfiguration( array(
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true, NS_FILE => true ),
			'smwgCacheType' => 'hash',
			'smwgExportBCAuxiliaryUse' => true
		) );

		// Ensure that the DB creates the extra tables for MEDIA/MINE
		$this->getStore()->clear();
		$this->getStore()->setupStore( false );

		// MW GLOBALS to be restored after the test
		$this->testEnvironment->withConfiguration( array(
			'wgEnableUploads'  => true,
			'wgFileExtensions' => array( 'txt' ),
			'wgVerifyMimeType' => true
		) );

		\SMWExporter::clear();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {
		Localizer::getInstance()->clear();

		$subject = new DIWikiPage( 'RdfLinkedFile.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNamespaceTextById( NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile(
			'RdfLinkedFile.txt'
		);

		$dummyTextFile->doUpload(
			'[[HasFile::File:RdfLinkedFile.txt]]'
		);

		$exportController = new ExportController( new RDFXMLSerializer() );
		$exportController->enableBacklinks( false );

		ob_start();

		$exportController->printPages(
			array( $subject->getTitle()->getPrefixedDBKey() )
		);

		$output = ob_get_clean();

		$expected = array(
			"<rdfs:label>{$fileNS}:RdfLinkedFile.txt</rdfs:label>",
			'<swivt:file rdf:resource="' . $dummyTextFile->getLocalFile()->getFullURL() . '"/>',
			'<property:Media_type-23aux rdf:datatype="http://www.w3.org/2001/XMLSchema#string">TEXT</property:Media_type-23aux>',
			'<property:MIME_type-23aux rdf:datatype="http://www.w3.org/2001/XMLSchema#string">text/plain</property:MIME_type-23aux>'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$output
		);
	}

}
