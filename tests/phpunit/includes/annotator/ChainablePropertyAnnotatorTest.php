<?php

namespace SMW\Test;

use SMW\PredefinedPropertyAnnotator;
use SMW\CategoryPropertyAnnotator;
use SMW\SortKeyPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\PredefinedPropertyAnnotator
 * @covers \SMW\CategoryPropertyAnnotator
 * @covers \SMW\SortKeyPropertyAnnotator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ChainablePropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @dataProvider annotationDataProvider
	 *
	 * @since 1.9
	 */
	public function testChainableDecoratorAnnotation( array $setup, array $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( $this->newTitle( $setup['namespace'] ) )
		);

		$settings = $this->newSettings( $setup['settings'] );

		$context  = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ),
			$setup['categories']
		);

		$instance = new SortKeyPropertyAnnotator(
			$instance,
			$setup['sortkey']
		);

		$instance = new PredefinedPropertyAnnotator(
			$instance,
			$this->newMockBuilder()->newObject( 'WikiPage', $setup['wikiPage'] ),
			$this->newMockBuilder()->newObject( 'Revision', $setup['revision'] ),
			$this->newMockBuilder()->newObject( 'User', $setup['user'] )
		);

		$instance->addAnnotation();

		$this->assertSemanticData(
			$instance->getSemanticData(),
			$expected,
			'Asserts that addAnnotation() adds expected triples'
		);

	}

	/**
	 * @return array
	 */
	public function annotationDataProvider() {

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'namespace'  => NS_MAIN,
				'categories' => array( 'Foo', 'Bar' ),
				'sortkey'    => 'Lala',
				'wikiPage'   => array( 'getTimestamp' => 1272508903 ),
				'revision'   => array(),
				'user'       => array(),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				)
			),
			array(
				'propertyCount' => 3,
				'propertyKey'   => array( '_INST', '_MDAT', '_SKEY' ),
				'propertyValue' => array( 'Foo',  'Bar', '2010-04-29T02:41:43', 'Lala' ),
			)
		);

		return $provider;
	}

}
