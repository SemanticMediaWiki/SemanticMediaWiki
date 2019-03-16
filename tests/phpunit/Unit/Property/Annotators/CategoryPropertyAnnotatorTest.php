<?php

namespace SMW\Tests\Property\Annotators;

use ParserOutput;
use SMW\DIWikiPage;
use SMW\ParserData;
use SMW\Property\Annotators\CategoryPropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\Property\Annotators\CategoryPropertyAnnotator
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
			[]
		);

		$this->assertInstanceOf(
			'\SMW\Property\Annotators\CategoryPropertyAnnotator',
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
			->setMethods( [ 'getRedirectTarget' ] )
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
			[ 'Bar' ]
		);

		$instance->useCategoryRedirect(
			true
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 1,
			'propertyKeys'   => '_ERRC'
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
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
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => true,
					'categoriesAsInstances' => false,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => false,
					'categoriesAsInstances' => true,
					'showHiddenCategories'  => false
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
					'categoryHierarchy'  => true,
					'categoriesAsInstances' => false,
					'showHiddenCategories'  => true
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
					'categoryHierarchy'  => true,
					'categoriesAsInstances' => false,
					'showHiddenCategories'  => false
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
