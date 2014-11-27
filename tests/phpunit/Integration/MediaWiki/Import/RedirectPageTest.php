<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\Tests\Utils\InSemanticDataFetcher;

use SMW\DIWikiPage;
use SMW\DIProperty;

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

	private $importedTitles = array();
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;
	private $pageRefresher;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->pageRefresher = UtilityFactory::getInstance()->newPageRefresher();
		$this->pageCreator = UtilityFactory::getInstance()->newPageCreator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/'. 'Fixtures/' . 'RedirectPageTest-Mw-1-19-7.xml'
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

	public function testPageImportToCreateRedirect() {

		$this->importedTitles = array(
			'SimplePageRedirectRegressionTest',
			'ToBeSimplePageRedirect'
		);

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$main = Title::newFromText( 'SimplePageRedirectRegressionTest' );

		$expectedCategoryAsWikiValue = array(
			'property' => new DIProperty( DIProperty::TYPE_CATEGORY ),
			'propertyValues' => array(
				'Regression test',
				'Redirect test',
				'Simple redirect test'
			)
		);

		$expectedSomeProperties = array(
			'properties' => array(
				new DIProperty( 'Has regression test' )
			)
		);

		$expectedRedirectAsWikiValue = array(
			'property' => new DIProperty( '_REDI' ),
			'propertyValues' => array(
				'ToBeSimplePageRedirect',
				'NewPageRedirectRegressionTest',
				'NewTargetPageRedirectRegressionTest'
			)
		);

		$newRedirectPage = $this->createPageWithRedirectFor(
			'NewPageRedirectRegressionTest',
			'SimplePageRedirectRegressionTest'
		);

		$this->movePageToTargetRedirect(
			$newRedirectPage,
			'NewTargetPageRedirectRegressionTest'
		);

		$this->pageRefresher->doRefreshPoolOfPages( array(
			$main,
			$newRedirectPage,
			'NewTargetPageRedirectRegressionTest'
		) );

		$semanticDataBatches = array(
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $main ) ),
		);

		$this->assertThatCategoriesAreSet(
			$expectedCategoryAsWikiValue,
			$semanticDataBatches
		);

		$this->assertThatPropertiesAreSet(
			$expectedSomeProperties,
			$semanticDataBatches
		);

		$inSemanticDataFetcher = new InSemanticDataFetcher( $this->getStore() );
		$inSemanticData = $inSemanticDataFetcher->getSemanticData( DIWikiPage::newFromTitle( $main ) );

		// When running sqlite, the database select returns an empty result which
		// is probably due to some DB-prefix issues in MW's DatabaseBaseSqlite
		// implementation and for non-sqlite see #212 / bug 62856
		if ( $inSemanticData->getProperties() === array() ) {
			$this->markTestSkipped(
				"Skipping test either because of sqlite or MW-{$GLOBALS['wgVersion']} / bug 62856"
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

		$moveToTargetTitle = Title::newFromText( $target );

		return $page->getTitle()->moveTo(
			$moveToTargetTitle,
			false,
			'create redirect',
			true
		);
	}

}
