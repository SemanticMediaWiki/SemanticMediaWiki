<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\ApplicationFactory;
use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;
use Title;

/**
 * @group semantic-mediawiki-integration
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

	/**
	 * MW GLOBALS to be restored after the test
	 */
	private $wgFileExtensions;
	private $wgEnableUploads;
	private $wgVerifyMimeType;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = array(
			'smwgPageSpecialProperties' => array( '_MEDIA', '_MIME' ),
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true, NS_FILE => true ),
			'smwgCacheType' => 'hash',
			'smwgExportBCAuxiliaryUse' => true
		);

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		// Ensure that the DB creates the extra tables for MEDIA/MINE
		$this->getStore()->clear();
		$this->getStore()->setupStore( false );

		$this->wgEnableUploads  = $GLOBALS['wgEnableUploads'];
		$this->wgFileExtensions = $GLOBALS['wgFileExtensions'];
		$this->wgVerifyMimeType = $GLOBALS['wgVerifyMimeType'];

		$GLOBALS['wgEnableUploads'] = true;
		$GLOBALS['wgFileExtensions'] = array( 'txt' );
		$GLOBALS['wgVerifyMimeType'] = true;

		\SMWExporter::clear();
	}

	protected function tearDown() {

		$GLOBALS['wgEnableUploads'] = $this->wgEnableUploads;
		$GLOBALS['wgFileExtensions'] = $this->wgFileExtensions;
		$GLOBALS['wgVerifyMimeType'] = $this->wgVerifyMimeType;

		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {

		$subject = new DIWikiPage( 'RdfLinkedFile.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNamespaceTextById( NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'RdfLinkedFile.txt' );
		$dummyTextFile->doUpload( '[[HasFile::File:RdfLinkedFile.txt]]' );

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
