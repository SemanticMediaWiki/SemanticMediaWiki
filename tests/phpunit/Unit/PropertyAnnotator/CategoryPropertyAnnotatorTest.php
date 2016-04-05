<?php

namespace SMW\Tests\PropertyAnnotator;

use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\ParserData;
use SMW\PropertyAnnotator\CategoryPropertyAnnotator;
use SMW\PropertyAnnotator\NullPropertyAnnotator;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\PropertyAnnotator\CategoryPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CategoryPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			array()
		);

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\CategoryPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider categoriesDataProvider
	 */
	public function testAddCategoriesAnnotation( array $parameters, array $expected ) {

		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( __METHOD__, $parameters['namespace'], '' ) )
			->newEmptySemanticData();

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$parameters['categories']
		);

		$instance->setShowHiddenCategoriesState(
			$parameters['settings']['smwgShowHiddenCategories']
		);

		$instance->setCategoryInstanceUsageState(
			$parameters['settings']['smwgCategoriesAsInstances']
		);

		$instance->setCategoryHierarchyUsageState(
			$parameters['settings']['smwgUseCategoryHierarchy']
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	/**
	 * @dataProvider categoriesDataProvider
	 */
	public function testAddCategoriesWithParserDataUpdate( array $parameters, array $expected ) {

		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( __METHOD__, $parameters['namespace'], '' ) )
			->newEmptySemanticData();

		$title        = $semanticData->getSubject()->getTitle();
		$parserOutput = new ParserOutput();
		$parserData   = new ParserData( $title, $parserOutput );

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $parserData->getSemanticData() ),
			$parameters['categories']
		);

		$instance->setShowHiddenCategoriesState(
			$parameters['settings']['smwgShowHiddenCategories']
		);

		$instance->setCategoryInstanceUsageState(
			$parameters['settings']['smwgCategoriesAsInstances']
		);

		$instance->setCategoryHierarchyUsageState(
			$parameters['settings']['smwgUseCategoryHierarchy']
		);

		$instance->addAnnotation();
		$parserData->pushSemanticDataToParserOutput();

		$parserDataAfterAnnotation = new ParserData( $title, $parserOutput );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserDataAfterAnnotation->getSemanticData()
		);
	}

	/**
	 * @dataProvider hiddenCategoriesDataProvider
	 */
	public function testAddCategoriesWithHiddenCategories( array $parameters, array $expected ) {

		$expectedPageLookup = $parameters['settings']['smwgShowHiddenCategories'] ? $this->never() : $this->atLeastOnce();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $expectedPageLookup )
			->method( 'getHiddenCategories' )
			->will( $this->returnValue( $parameters['hidCategories'] ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $expectedPageLookup )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( __METHOD__, $parameters['namespace'], '' ) )
			->newEmptySemanticData();

		$this->applicationFactory->registerObject(
			'PageCreator',
			$pageCreator
		);

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$parameters['categories']
		);

		$instance->setShowHiddenCategoriesState(
			$parameters['settings']['smwgShowHiddenCategories']
		);

		$instance->setCategoryInstanceUsageState(
			$parameters['settings']['smwgCategoriesAsInstances']
		);

		$instance->setCategoryHierarchyUsageState(
			$parameters['settings']['smwgUseCategoryHierarchy']
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);
	}

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

		$hidCategory = MockTitle::buildMock( __METHOD__ );

		$hidCategory->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_CATEGORY ) );

		$hidCategory->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( 'Bar' ) );

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
