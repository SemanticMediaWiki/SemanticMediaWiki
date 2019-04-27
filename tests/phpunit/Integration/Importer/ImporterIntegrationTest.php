<?php

namespace SMW\Tests\Integration\Importer;

use SMW\ApplicationFactory;
use SMW\Tests\MwDBaseUnitTestCase;

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
	private $importerServiceFactory;
	private $stringValidator;
	private $fixtures;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();
		$this->fixtures = __DIR__ . '/../../Fixtures/Importer';

		$this->importerServiceFactory = ApplicationFactory::getInstance()->create( 'ImporterServiceFactory' );
		$this->spyMessageReporter = $utilityFactory->newSpyMessageReporter();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
	}

	public function testValidTextContent() {

		$importer = $this->importerServiceFactory->newImporter(
			$this->importerServiceFactory->newJsonContentIterator( [ $this->fixtures . '/ValidTextContent' ] )
		);

		$importer->setMessageReporter( $this->spyMessageReporter );
		$importer->setReqVersion( 1 );

		$importer->doImport();

		$this->stringValidator->assertThatStringContains(
			[
				'Smw import foaf',
				'Foaf:knows'
			],
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testValidXmlContent() {

		if ( !interface_exists( '\ImportSource' ) ) {
			$this->markTestSkipped( "ImportSource interface is unknown (MW 1.25-)" );
		}

		$importer = $this->importerServiceFactory->newImporter(
			$this->importerServiceFactory->newJsonContentIterator( [ $this->fixtures . '/ValidXmlContent' ] )
		);

		$importer->setMessageReporter( $this->spyMessageReporter );
		$importer->setReqVersion( 1 );

		$importer->doImport();

		$this->stringValidator->assertThatStringContains(
			[
				'ImportTest'
			],
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
