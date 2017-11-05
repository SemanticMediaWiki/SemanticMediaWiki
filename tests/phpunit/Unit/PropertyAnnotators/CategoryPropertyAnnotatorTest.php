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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
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
			array()
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

		$instance->showHiddenCategories(
			$parameters['settings']['showHiddenCategories']
		);

		$instance->useCategoryInstance(
			$parameters['settings']['categoriesAsInstances']
		);

		$instance->useCategoryHierarchy(
			$parameters['settings']['categoryHierarchy']
		);

		$instance->useCategoryRedirect(
			false
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

		$instance->showHiddenCategories(
			$parameters['settings']['showHiddenCategories']
		);

		$instance->useCategoryInstance(
			$parameters['settings']['categoriesAsInstances']
		);

		$instance->useCategoryHierarchy(
			$parameters['settings']['categoryHierarchy']
		);

		$instance->useCategoryRedirect(
			false
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

		$expectedPageLookup = $parameters['settings']['showHiddenCategories'] ? $this->never() : $this->atLeastOnce();

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

		$instance->showHiddenCategories(
			$parameters['settings']['showHiddenCategories']
		);

		$instance->useCategoryInstance(
			$parameters['settings']['categoriesAsInstances']
		);

		$instance->useCategoryHierarchy(
			$parameters['settings']['categoryHierarchy']
		);

		$instance->useCategoryRedirect(
			false
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);
	}

	public function testAddCategoryOnInvalidRedirect() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getRedirectTarget' ) )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( new DIWikiPage( 'Foo', NS_MAIN ) ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( __METHOD__, NS_MAIN ) )
			->newEmptySemanticData();

		$instance = new CategoryPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			array( 'Bar' )
		);

		$instance->useCategoryRedirect(
			true
		);

		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => '_ERRC'
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
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
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => true,
					'categoriesAsInstances' => false,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => false
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
					'categoryHierarchy'  => true,
					'categoriesAsInstances' => false,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => true,
					'categoriesAsInstances' => false,
					'showHiddenCategories'  => false
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
