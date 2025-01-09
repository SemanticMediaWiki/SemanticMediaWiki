<?php

namespace SMW\Tests\Integration\Importer;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ImporterIntegrationTest extends SMWIntegrationTestCase {

	private $spyMessageReporter;
	private $importerServiceFactory;
	private $stringValidator;
	private $fixtures;

	protected function setUp(): void {
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

		$importer->runImport();

		$this->stringValidator->assertThatStringContains(
			[
				'Smw import foaf',
				'Foaf:knows'
			],
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testValidXmlContent() {
		$importer = $this->importerServiceFactory->newImporter(
			$this->importerServiceFactory->newJsonContentIterator( [ $this->fixtures . '/ValidXmlContent' ] )
		);

		$importer->setMessageReporter( $this->spyMessageReporter );
		$importer->setReqVersion( 1 );

		$importer->runImport();

		$this->stringValidator->assertThatStringContains(
			[
				'ImportTest'
			],
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
