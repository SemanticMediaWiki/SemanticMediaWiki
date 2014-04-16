<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\ByPageSemanticDataFinder;
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
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class CategoryInstanceAndCategoryHierarchyRegressionTest extends MwRegressionTestCase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'CategoryInstanceAndCategoryHierarchyRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'Category:Regression test',
			'Category:Regression test category',
			'Category:Regression test sub category',
			'Category:Regression test sub sub category',
			'CategoryInstanceAndCategoryHierarchyRegressionTest/WithSubpage',
			'CategoryInstanceAndCategoryHierarchyRegressionTest/WithSubpage/WithSubSubpage',
			'CategoryInstanceAndCategoryHierarchyRegressionTest'
		);
	}

	public function assertDataImport() {

		$title = Title::newFromText( 'CategoryInstanceAndCategoryHierarchyRegressionTest' );

		$expectedCategoryAsWikiValue = array(
			'property' => new DIProperty( '_INST' ),
			'propertyValues' => array(
				'Regression test',
				'Regression test category',
				'Regression test sub category',
				'Regression test sub sub category',
				'Category regression test'
			)
		);

		$this->semanticDataValidator = new SemanticDataValidator;

		$semanticDataFinder = new ByPageSemanticDataFinder;
		$semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataBatches = array(
			$semanticDataFinder->fetchFromOutput(),
			$semanticDataFinder->fetchFromStore()
		);

		foreach ( $semanticDataBatches as $semanticData ) {

			$this->semanticDataValidator->assertThatCategoriesAreSet(
				$expectedCategoryAsWikiValue,
				$semanticData
			);

		}

	}

}
