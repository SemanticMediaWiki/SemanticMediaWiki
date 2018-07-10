<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ClassDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $descriptionInterpreterFactory;

	protected function setUp() {
		parent::setUp();

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	public function testCanConstruct() {

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter',
			new ClassDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ClassDescription' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ClassDescriptionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider categoryProvider
	 */
	public function testClassConditionForCategories( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ClassDescriptionInterpreter( $conditionBuilder );

		$condition = $instance->interpretDescription( $description );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	public function testHierarchyPattern() {

		$engineOptions = new EngineOptions();
		$engineOptions->set( 'smwgSparqlQFeatures', SMW_SPARQL_QF_SUBC );

		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$categoryName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$hierarchyLookup = $this->getMockBuilder( '\SMW\HierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyLookup->expects( $this->once() )
			->method( 'hasSubcategory' )
			->with( $this->equalTo( $category ) )
			->will( $this->returnValue( true ) );

		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory, $engineOptions );
		$conditionBuilder->setHierarchyLookup( $hierarchyLookup );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ClassDescriptionInterpreter( $conditionBuilder );

		$condition = $instance->interpretDescription(
			new ClassDescription( $category )
		);

		$expected = UtilityFactory::getInstance()->newStringBuilder()
			->addString( '{' )->addNewLine()
			->addString( "?sc1 rdfs:subClassOf* $categoryName ." )->addNewLine()
			->addString( '?result rdf:type ?sc1 . }' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expected,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	public function categoryProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new ClassDescription( [] );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		];

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$categoryName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$description = new ClassDescription( $category );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( "{ ?result rdf:type $categoryName . }" )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$conditionType,
			$expected,
		];

		# 2
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$categoryFoo = new DIWikiPage( 'Foo', NS_CATEGORY );

		$categoryFooName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $categoryFoo )
		);

		$categoryBar = new DIWikiPage( 'Bar', NS_CATEGORY );

		$categoryBarName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $categoryBar )
		);

		$description = new ClassDescription( [
			$categoryFoo,
			$categoryBar
		] );

		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( "{ ?result rdf:type $categoryFooName . }" )->addNewLine()
			->addString( 'UNION' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryBarName . }" )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		];

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ClassDescription( [
			$categoryFoo,
			$categoryBar
		] );

		$orderByProperty = new DIProperty( 'Foo' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryFooName . }" )->addNewLine()
			->addString( 'UNION' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryBarName . }" )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		];

		return $provider;
	}

}
