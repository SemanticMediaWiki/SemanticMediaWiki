<?php

namespace SMW\Tests\Integration\Importer;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\ApplicationFactory;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterIntegrationTest extends MwDBaseUnitTestCase {

	private $spyMessageReporter;
	private $importServicesFactory;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->importServicesFactory = ApplicationFactory::getInstance()->create( 'ImportServicesFactory' );
		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
	}

	public function testValidTextContent() {

		$importFileDir = $this->testEnvironment->getFixturesLocation( 'Importer/ValidTextContent' );

		$importer = $this->importServicesFactory->newImporter(
			$this->importServicesFactory->newJsonContentIterator( $importFileDir )
		);

		$importer->setMessageReporter( $this->spyMessageReporter );
		$importer->setReqVersion( 1 );

		$importer->doImport();

		$this->stringValidator->assertThatStringContains(
			array(
				'Smw import foaf',
				'Foaf:knows'
			),
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testValidXmlContent() {

		$this->skipTestForMediaWikiVersionLowerThan( '1.25', "ImportSource interface is unknown (MW 1.25-)" );

		$importFileDir = $this->testEnvironment->getFixturesLocation( 'Importer/ValidXmlContent' );

		$importer = $this->importServicesFactory->newImporter(
			$this->importServicesFactory->newJsonContentIterator( $importFileDir )
		);

		$importer->setMessageReporter( $this->spyMessageReporter );
		$importer->setReqVersion( 1 );

		$importer->doImport();

		$this->stringValidator->assertThatStringContains(
			array(
				'ImportTest'
			),
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
