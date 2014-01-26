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

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance->getSemanticData() );

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

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance->getSemanticData() );

		$this->assertEquals(
			$instance->verifyObserverWasCalled,
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

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $recreateParserDataFromOutput->getData() );

	}

	/**
	 * @dataProvider hiddenCategoriesDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddCategoriesWithHiddenCategories( array $setup, array $expected ) {

		$semanticData = new SemanticData(
			$this->newSubject( $this->newTitle( $setup['namespace'] ) )
		);

		$instance = $this->newInstance( $semanticData, $setup['settings'], $setup['categories'] );
		$reflector = $this->newReflector();

		$hiddenCategories = $reflector->getProperty( 'hiddenCategories' );
		$hiddenCategories->setAccessible( true );
		$hiddenCategories->setValue( $instance, $setup['hidCategories'] );

		$instance->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $semanticData );

	}

	/**
	 * @since 1.9.0.2
	 */
	public function testPublicizeHiddenCategorieUsingWikiPagefactory() {

		$semanticData = new SemanticData(
			$this->newSubject( $this->newTitle() )
		);

		$instance = $this->newInstance( $semanticData );
		$reflector = $this->newReflector();

		$isHiddenCategory = $reflector->getMethod( 'isHiddenCategory' );
		$isHiddenCategory->setAccessible( true );

		$this->assertFalse(
			$isHiddenCategory->invoke( $instance, 'Foo' ),
			'Asserts that false is returned for non-existing hidden categories'
		);

	}

	/**
	 * @since 1.9
	 */
	public function updateOutputCallback( $instance ) {

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );

		return $instance->verifyObserverWasCalled = 'updateOutputCallback';
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
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => true
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_INST',
				'propertyValues' => array( 'Foo',  'Bar' ),
			)
		);

		// Category hierarchy or Sub-category
		$provider[] = array(
			array(
				'namespace'  => NS_CATEGORY,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false,
					'smwgShowHiddenCategories'  => true
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SUBC',
				'propertyValues' => array( 'Foo',  'Bar' ),
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function hiddenCategoriesDataProvider() {

		$provider = array();

		$hidCategory = $this->newMockBuilder()->newObject( 'Title', array(
			'getNamespace' => NS_CATEGORY,
			'getText'      => 'Bar'
		) );

		// #0 Standard category, show hidden category
		$provider[] = array(
			array(
				'namespace'     => NS_MAIN,
				'categories'    => array( 'Foo', 'Bar' ),
				'hidCategories' => array( $hidCategory ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => true
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_INST',
				'propertyValues' => array( 'Foo', 'Bar' ),
			)
		);

		// #1 Standard category, omit hidden category
		$provider[] = array(
			array(
				'namespace'     => NS_MAIN,
				'categories'    => array( 'Foo', 'Bar' ),
				'hidCategories' => array( $hidCategory ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => false
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_INST',
				'propertyValues' => array( 'Foo' ),
			)
		);

		// #2 Category hierarchy or Sub-category, show hidden category
		$provider[] = array(
			array(
				'namespace'     => NS_CATEGORY,
				'categories'    => array( 'Foo', 'Bar' ),
				'hidCategories' => array( $hidCategory ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false,
					'smwgShowHiddenCategories'  => true
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SUBC',
				'propertyValues' => array( 'Foo', 'Bar' ),
			)
		);

		// #3 Category hierarchy or Sub-category, omit hidden category
		$provider[] = array(
			array(
				'namespace'     => NS_CATEGORY,
				'categories'    => array( 'Foo', 'Bar' ),
				'hidCategories' => array( $hidCategory ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false,
					'smwgShowHiddenCategories'  => false
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SUBC',
				'propertyValues' => array( 'Foo' ),
			)
		);

		return $provider;
	}

}
