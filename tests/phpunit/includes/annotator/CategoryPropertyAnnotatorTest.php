<?php

namespace SMW\Test;

use SMW\CategoryPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIWikiPage;

/**
 * @covers \SMW\CategoryPropertyAnnotator
 * @covers \SMW\PropertyAnnotatorDecorator
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
class CategoryPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CategoryPropertyAnnotator';
	}

	/**
	 * @since 1.9
	 *
	 * @return Observer
	 */
	private function newObserver() {

		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'updateOutputCallback' )
		) );

	}

	/**
	 * @since 1.9
	 *
	 * @return CategoryPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $settings = array(), $categories = array() ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		$settings = $this->newSettings( $settings );

		$context  = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		return new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ),
			$categories
		);

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddCategoriesWithOutObserver( array $setup, array $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( $this->newTitle( $setup['namespace'] ) )
		);

		$instance = $this->newInstance( $semanticData, $setup['settings'], $setup['categories'] );
		$instance->addAnnotation();

		$this->assertSemanticData(
			$instance->getSemanticData(),
			$expected,
			'Asserts that addAnnotation() adds expected triples'
		);

	}

	/**
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddCategoriesOnMockObserver( array $setup, array $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( $this->newTitle( $setup['namespace'] ) )
		);

		$instance = $this->newInstance( $semanticData, $setup['settings'], $setup['categories'] );
		$instance->attach( $this->newObserver() )->addAnnotation();

		$this->assertSemanticData(
			$instance->getSemanticData(),
			$expected,
			'Asserts that addAnnotation() adds expected triples'
		);

		$this->assertEquals(
			$instance->verifyCallback,
			'updateOutputCallback',
			'Asserts that the invoked Observer was notified'
		);

	}

	/**
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddCategoriesParserDataObserverIntegration( array $setup, array $expected ) {

		$title        = $this->newTitle( $setup['namespace'] );
		$parserOutput = new \ParserOutput();
		$parserData   = new \SMW\ParserData( $title, $parserOutput );

		$instance = $this->newInstance( $parserData->getData(), $setup['settings'], $setup['categories'] );
		$instance->attach( $parserData )->addAnnotation();

		$recreateParserDataFromOutput = new \SMW\ParserData( $title, $parserOutput );

		$this->assertSemanticData(
			$recreateParserDataFromOutput->getData(),
			$expected,
			'Asserts that addAnnotation() adds expected triples'
		);

	}

	/**
	 * Verify that the Observer is reachable
	 *
	 * @since 1.9
	 */
	public function updateOutputCallback( $instance ) {

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );

		return $instance->verifyCallback = 'updateOutputCallback';
	}

	/**
	 * @return array
	 */
	public function categoriesDataProvider() {

		$provider = array();

		// Standard category
		$provider[] = array(
			array(
				'namespace'  => NS_MAIN,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_INST',
				'propertyValue' => array( 'Foo',  'Bar' ),
			)
		);

		// Category hierarchy or Sub-category
		$provider[] = array(
			array(
				'namespace'  => NS_CATEGORY,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SUBC',
				'propertyValue' => array( 'Foo',  'Bar' ),
			)
		);

		return $provider;
	}

}
