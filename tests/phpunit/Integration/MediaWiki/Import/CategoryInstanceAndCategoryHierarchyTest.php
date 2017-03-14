<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use SMW\DIProperty;
use SMW\Tests\MwDBaseUnitTestCase;
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
class CategoryInstanceAndCategoryHierarchyTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/'. 'Fixtures/' . 'CategoryInstanceAndCategoryHierarchyTest-Mw-1-19-7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown() {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->importedTitles );

		parent::tearDown();
	}

	public function testCategoryHierarchies() {

		$this->importedTitles = [
			'Category:Regression test',
			'Category:Regression test category',
			'Category:Regression test sub category',
			'Category:Regression test sub sub category',
			'CategoryInstanceAndCategoryHierarchyRegressionTest/WithSubpage',
			'CategoryInstanceAndCategoryHierarchyRegressionTest/WithSubpage/WithSubSubpage',
			'CategoryInstanceAndCategoryHierarchyRegressionTest'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$title = Title::newFromText( 'CategoryInstanceAndCategoryHierarchyRegressionTest' );

		$expectedCategoryAsWikiValue = [
			'property' => new DIProperty( '_INST' ),
			'propertyValues' => [
				'Regression test',
				'Regression test category',
				'Regression test sub category',
				'Regression test sub sub category',
				'Category regression test'
			]
		];

		$semanticDataFinder = new ByPageSemanticDataFinder;
		$semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataBatches = [
			$semanticDataFinder->fetchFromOutput(),
			$semanticDataFinder->fetchFromStore()
		];

		foreach ( $semanticDataBatches as $semanticData ) {

			$this->semanticDataValidator->assertThatCategoriesAreSet(
				$expectedCategoryAsWikiValue,
				$semanticData
			);
		}
	}

}
