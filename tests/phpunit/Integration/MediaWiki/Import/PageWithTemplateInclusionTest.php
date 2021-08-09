<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use SMW\DIProperty;
use SMW\Tests\DatabaseTestCase;
use SMW\Tests\Utils\ByPageSemanticDataFinder;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class PageWithTemplateInclusionTest extends DatabaseTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;

	protected function setUp() : void {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/'. 'Fixtures/' . 'PageWithTemplateInclusionTest-Mw-1-19-7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown() : void {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->importedTitles );

		parent::tearDown();
	}

	public function testImportToVerifyAnnotationByTemplateInclusion() {

		$this->importedTitles = [
			'Foo-1-19-7',
			'Template:FooAsk',
			'Template:FooShow',
			'Template:FooSubobject',
			'Template:FooTemplate'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$expectedProperties = [
			'properties' => [
				DIProperty::newFromUserLabel( 'Foo' ),
				DIProperty::newFromUserLabel( 'Quux' ),
				new DIProperty( '_ASK' ),
				new DIProperty( '_MDAT' ),
				new DIProperty( '_SKEY' ),
				new DIProperty( '_SOBJ' ),
				new DIProperty( '_INST' )
			]
		];

		$title = Title::newFromText( 'Foo-1-19-7' );

		$semanticDataFinder = new ByPageSemanticDataFinder();
		$semanticDataFinder
			->setTitle( $title )
			->setStore( $this->getStore() );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expectedProperties,
			$semanticDataFinder->fetchFromOutput()
		);
	}

}
