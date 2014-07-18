<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\InSemanticDataFetcher;
use SMW\Tests\Util\JobQueueRunner;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageRefresher;
use SMW\Test\MwRegressionTestCase;

use SMW\DIWikiPage;
use SMW\DIProperty;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-regression
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class SimplePageRedirectRegressionTest extends MwRegressionTestCase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'SimplePageRedirectRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'SimplePageRedirectRegressionTest',
			'ToBeSimplePageRedirect'
		);
	}

	public function assertDataImport() {

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

		$pageRefresher = new PageRefresher( $this->getStore() );
		$pageRefresher->doRefresh( $main );

		$jobQueueRunner = new JobQueueRunner( 'SMW\UpdateJob' );
		$jobQueueRunner->run();

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

		$semanticDataValidator = new SemanticDataValidator();

		foreach ( $semanticDataBatches as $semanticData ) {
			$semanticDataValidator->assertThatCategoriesAreSet(
				$expectedCategoryAsWikiValue,
				$semanticData
			);
		}
	}

	protected function assertThatPropertiesAreSet( $expectedSomeProperties, $semanticDataBatches ) {

		$semanticDataValidator = new SemanticDataValidator();

		foreach ( $semanticDataBatches as $semanticData ) {
			$semanticDataValidator->assertThatPropertiesAreSet(
				$expectedSomeProperties,
				$semanticData
			);
		}
	}

	protected function assertThatSemanticDataValuesForPropertyAreSet( $expected, $semanticData ) {

		$semanticDataValidator = new SemanticDataValidator();

		$runValueAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->equals( $expected['property'] ) ) {

				$runValueAssert = true;
				$semanticDataValidator->assertThatPropertyValuesAreSet(
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

		$pageCreator = new PageCreator();
		$pageCreator
			->createPage( Title::newFromText( $source ) )
			->doEdit( "#REDIRECT [[{$target}]]" );

		return $pageCreator->getPage();
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
