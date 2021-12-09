<?php

namespace SMW\Tests\Integration;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Tests\DatabaseTestCase;
use SMW\Exporter\ExporterFactory;
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
class RdfFileResourceTest extends DatabaseTestCase {

	private $fixturesFileProvider;
	private $stringValidator;

	protected function setUp() : void {
		parent::setUp();

		if ( $GLOBALS['wgLanguageCode'] !== 'en' ) {
			$this->markTestSkipped( 'Property title produces different representation!' );
		}

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();

		$this->testEnvironment->withConfiguration( [
			'smwgPageSpecialProperties' => [ '_MEDIA', '_MIME' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, NS_FILE => true ],
			'smwgMainCacheType' => 'hash',
			'smwgExportBCAuxiliaryUse' => true
		] );

		// Ensure that the DB creates the extra tables for MEDIA/MINE
		$this->getStore()->clear();
		$this->getStore()->setupStore( false );

		// MW GLOBALS to be restored after the test
		$this->testEnvironment->withConfiguration( [
			'wgEnableUploads'  => true,
			'wgFileExtensions' => [ 'txt' ],
			'wgVerifyMimeType' => true
		] );

		\SMWExporter::clear();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {
		Localizer::getInstance()->clear();

		$subject = new DIWikiPage( 'RdfLinkedFile.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNsText( NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile(
			'RdfLinkedFile.txt'
		);

		$dummyTextFile->doUpload(
			'[[HasFile::File:RdfLinkedFile.txt]]'
		);

		$this->testEnvironment->executePendingDeferredUpdates();

		$exporterFactory = new ExporterFactory();

		$exportController = $exporterFactory->newExportController(
			$exporterFactory->newRDFXMLSerializer()
		);

		$exportController->enableBacklinks( false );

		ob_start();

		$exportController->printPages(
			[ $subject->getTitle()->getPrefixedDBKey() ]
		);

		$output = ob_get_clean();

		$expected = [
			"<rdfs:label>{$fileNS}:RdfLinkedFile.txt</rdfs:label>",
			'<swivt:file rdf:resource="' . $dummyTextFile->getLocalFile()->getFullURL() . '"/>',
			'<property:Media_type-23aux rdf:datatype="http://www.w3.org/2001/XMLSchema#string">TEXT</property:Media_type-23aux>',
			'<property:MIME_type-23aux rdf:datatype="http://www.w3.org/2001/XMLSchema#string">text/plain</property:MIME_type-23aux>'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$output
		);
	}

}
