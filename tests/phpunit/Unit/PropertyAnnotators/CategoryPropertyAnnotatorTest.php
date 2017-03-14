<?php

namespace SMW\Tests\PropertyAnnotators;

use ParserOutput;
use SMW\DIWikiPage;
use SMW\ParserData;
use SMW\PropertyAnnotators\CategoryPropertyAnnotator;
use SMW\PropertyAnnotators\NullPropertyAnnotator;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\PropertyAnnotators\CategoryPropertyAnnotator
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
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			[]
		);

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotators\CategoryPropertyAnnotator',
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

		$this->testEnvironment->registerObject(
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

		$provider = [];

		// Standard category
		$provider[] = [
			[
				'namespace'  => NS_MAIN,
				'categories' => [ 'Foo', 'Bar' ],
				'settings'   => [
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => true
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_INST',
				'propertyValues' => [ 'Foo',  'Bar' ],
			]
		];

		// Category hierarchy or Sub-category
		$provider[] = [
			[
				'namespace'  => NS_CATEGORY,
				'categories' => [ 'Foo', 'Bar' ],
				'settings'   => [
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false,
					'smwgShowHiddenCategories'  => true
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_SUBC',
				'propertyValues' => [ 'Foo',  'Bar' ],
			]
		];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function hiddenCategoriesDataProvider() {

		$provider = [];

		$hidCategory = MockTitle::buildMock( __METHOD__ );

		$hidCategory->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_CATEGORY ) );

		$hidCategory->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( 'Bar' ) );

		// #0 Standard category, show hidden category
		$provider[] = [
			[
				'namespace'     => NS_MAIN,
				'categories'    => [ 'Foo', 'Bar' ],
				'hidCategories' => [ $hidCategory ],
				'settings'   => [
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => true
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_INST',
				'propertyValues' => [ 'Foo', 'Bar' ],
			]
		];

		// #1 Standard category, omit hidden category
		$provider[] = [
			[
				'namespace'     => NS_MAIN,
				'categories'    => [ 'Foo', 'Bar' ],
				'hidCategories' => [ $hidCategory ],
				'settings'   => [
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true,
					'smwgShowHiddenCategories'  => false
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_INST',
				'propertyValues' => [ 'Foo' ],
			]
		];

		// #2 Category hierarchy or Sub-category, show hidden category
		$provider[] = [
			[
				'namespace'     => NS_CATEGORY,
				'categories'    => [ 'Foo', 'Bar' ],
				'hidCategories' => [ $hidCategory ],
				'settings'   => [
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false,
					'smwgShowHiddenCategories'  => true
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_SUBC',
				'propertyValues' => [ 'Foo', 'Bar' ],
			]
		];

		// #3 Category hierarchy or Sub-category, omit hidden category
		$provider[] = [
			[
				'namespace'     => NS_CATEGORY,
				'categories'    => [ 'Foo', 'Bar' ],
				'hidCategories' => [ $hidCategory ],
				'settings'   => [
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false,
					'smwgShowHiddenCategories'  => false
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_SUBC',
				'propertyValues' => [ 'Foo' ],
			]
		];

		return $provider;
	}

}
