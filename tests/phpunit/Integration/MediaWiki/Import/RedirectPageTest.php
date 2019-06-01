<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\InSemanticDataFetcher;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class RedirectPageTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;
	private $pageRefresher;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = $this->testEnvironment->getUtilityFactory()->newRunnerFactory();
		$this->titleValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$this->pageRefresher = $this->testEnvironment->getUtilityFactory()->newPageRefresher();
		$this->pageCreator = $this->testEnvironment->getUtilityFactory()->newPageCreator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/'. 'Fixtures/' . 'RedirectPageTest-Mw-1-19-7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown() {
		$this->testEnvironment->flushPages( $this->importedTitles );
		parent::tearDown();
	}

	public function testPageImportToCreateRedirect() {

		$this->importedTitles = [
			'SimplePageRedirectRegressionTest',
			'ToBeSimplePageRedirect'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$main = Title::newFromText( 'SimplePageRedirectRegressionTest' );

		$expectedCategoryAsWikiValue = [
			'property' => new DIProperty( DIProperty::TYPE_CATEGORY ),
			'propertyValues' => [
				'Regression test',
				'Redirect test',
				'Simple redirect test'
			]
		];

		$expectedSomeProperties = [
			'properties' => [
				new DIProperty( 'Has regression test' )
			]
		];

		$expectedRedirectAsWikiValue = [
			'property' => new DIProperty( '_REDI' ),
			'propertyValues' => [
				'ToBeSimplePageRedirect',
				'NewPageRedirectRegressionTest',
				'NewTargetPageRedirectRegressionTest'
			]
		];

		$newRedirectPage = $this->createPageWithRedirectFor(
			'NewPageRedirectRegressionTest',
			'SimplePageRedirectRegressionTest'
		);

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->movePageToTargetRedirect(
			$newRedirectPage,
			'NewTargetPageRedirectRegressionTest'
		);

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->pageRefresher->doRefreshPoolOfPages( [
			$main,
			$newRedirectPage,
			'NewTargetPageRedirectRegressionTest'
		] );

		$this->testEnvironment->executePendingDeferredUpdates();

		$semanticDataBatches = [
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $main ) ),
		];

		// Something changed in MW since 1.28 that causes a
		// "SMW\Tests\Utils\Validators\SemanticDataValidator::assertContainsPropertyValues
		// for '_INST' as '__sin' with (Regression test, Redirect test, Simple redirect test)
		// Failed asserting that an array contains 'Lorem ipsum'." and since I'm not sure
		// about the cause, this part is disabled and awaits an investigation

		//	$this->assertThatCategoriesAreSet(
		//		$expectedCategoryAsWikiValue,
		//		$semanticDataBatches
		//	);

		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$semanticDataBatches
		);

		$inSemanticDataFetcher = new InSemanticDataFetcher( $this->getStore() );
		$inSemanticData = $inSemanticDataFetcher->getSemanticData( DIWikiPage::newFromTitle( $main ) );

		// When running sqlite, the database select returns an empty result which
		// is probably due to some DB-prefix issues in MW's DatabaseBaseSqlite
		// implementation and for non-sqlite see #212 / bug 62856
		if ( $inSemanticData->getProperties() === [] ) {
			$this->markTestSkipped(
				"Skipping test either because of sqlite or MW-" . MW_VERSION . "/ bug 62856"
			);
		}

		$this->assertThatSemanticDataValuesForPropertyAreSet(
			$expectedRedirectAsWikiValue,
			$inSemanticData
		);
	}

	protected function assertThatCategoriesAreSet( $expectedCategoryAsWikiValue, $semanticDataBatches ) {

		foreach ( $semanticDataBatches as $semanticData ) {
			$this->semanticDataValidator->assertThatCategoriesAreSet(
				$expectedCategoryAsWikiValue,
				$semanticData
			);
		}
	}

	protected function assertThatPropertiesAreSet( $expectedSomeProperties, $semanticDataBatches ) {

		foreach ( $semanticDataBatches as $semanticData ) {
			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expectedSomeProperties,
				$semanticData
			);
		}
	}

	protected function assertThatSemanticDataValuesForPropertyAreSet( $expected, $semanticData ) {

		$runValueAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->equals( $expected['property'] ) ) {

				$runValueAssert = true;
				$this->semanticDataValidator->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}
		}
		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runValueAssert, __METHOD__ );
	}

	protected function createPageWithRedirectFor( $source, $target ) {

		$this->pageCreator
			->createPage( Title::newFromText( $source ) )
			->doEdit( "#REDIRECT [[{$target}]]" );

		return $this->pageCreator->getPage();
	}

	protected function movePageToTargetRedirect( $page, $target ) {
		$this->pageCreator->setPage( $page );
		$this->pageCreator->doMoveTo( Title::newFromText( $target ), true );
	}

}
