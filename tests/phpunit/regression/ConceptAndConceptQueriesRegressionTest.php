<?php

namespace SMW\Test;

use SMW\DIWikiPage;
use SMW\DIProperty;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group ConceptRegressionTest
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class ConceptAndConceptQueriesRegressionTest extends MwRegressionTestCase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'ConceptAndConceptQueriesRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			array( 'Has concept', SMW_NS_PROPERTY ),
			array( 'Has conceptA', SMW_NS_PROPERTY ),
			array( 'Has conceptB', SMW_NS_PROPERTY ),
			'ConceptRegressionTestWithProperty-A',
			'ConceptRegressionTestWithProperty-B',
			'ConceptRegressionTestWithCategory-A',
			'ConceptRegressionTestWithCategory-B',
			'ConceptRegressionTestWithCategory-AA',
			'ConceptRegressionTestWithProperty-BB',
			'ConceptRegressionTestWithProperty-C',
			'ConceptRegressionTestWithProperty-D',
			array( 'RegressionTestWithPropertyA', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithPropertyB', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithCategoryA', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithCategoryB', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithCategoryDisjunction', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithPropertyDisjunction', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithConceptDisjunction', SMW_NS_CONCEPT ),
			array( 'RegressionTestWithConceptAndPropertyDisjunction', SMW_NS_CONCEPT ),
			array( 'ConceptAndConceptQueriesRegressionTest', NS_MAIN )
		);
	}

	public function assertDataImport() {

		$title = Title::newFromText( 'ConceptQueries' );

		$expectedWithCategory = array(
			'beforeRefresh' => array( 'empty', 0, 1, 2, 0 ),
			'afterRefresh'  => array( 'full' , 0, 1, 2, 2 ),
			'afterDelete'   => array( 'empty', 0, 1, 2, 0 )
		);

	//	$this->assertConcept( $expectedWithCategory, $this->makeConceptTitle( 'PageWithCategory' ) );

		$expectedWithProperty = array(
			'beforeRefresh' => array( 'empty', 1, 2, 1, 0 ),
			'afterRefresh'  => array( 'full' , 1, 2, 1, 0 ),
			'afterDelete'   => array( 'empty', 1, 2, 1, 0 )
		);

	//	$this->assertConcept( $expectedWithProperty, $this->makeConceptTitle( 'PageWithProperty' ) );

	}

	private function assertConcept( $expected, Title $concept ) {
		$this->assertConceptInProcess( $expected['beforeRefresh'], $concept );

		$this->assertCount( 0, $this->getStore()->refreshConceptCache( $concept ) );
		$this->assertConceptInProcess( $expected['afterRefresh'], $concept );

		$this->getStore()->deleteConceptCache( $concept );
		$this->assertConceptInProcess( $expected['afterDelete'], $concept );
	}

	private function assertConceptInProcess( $expected, Title $concept ) {
		$concept = $this->getStore()->getConceptCacheStatus( $concept );

		$this->assertEquals( $expected[0], $concept->getCacheStatus() );
		$this->assertEquals( $expected[1], $concept->getDepth() );
		$this->assertEquals( $expected[2], $concept->getSize() );
		$this->assertEquals( $expected[3], $concept->getQueryFeatures() );
		$this->assertEquals( $expected[4], $concept->getCacheCount() );

	}

	private function makeConceptTitle( $title ) {
		return Title::newFromText( $title, SMW_NS_CONCEPT );
	}

}
