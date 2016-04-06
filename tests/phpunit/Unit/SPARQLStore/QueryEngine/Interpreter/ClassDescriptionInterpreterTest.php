<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Interpreter;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ClassDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter',
			new ClassDescriptionInterpreter( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ClassDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ClassDescriptionInterpreter( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider categoryProvider
	 */
	public function testClassConditionForCategories( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );
		$compoundConditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ClassDescriptionInterpreter( $compoundConditionBuilder );

		$condition = $instance->interpretDescription( $description );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$compoundConditionBuilder->convertConditionToString( $condition )
		);
	}

	public function testHierarchyPattern() {

		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$categoryName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertyHierarchyLookup->expects( $this->once() )
			->method( 'hasSubcategoryFor' )
			->with( $this->equalTo( $category ) )
			->will( $this->returnValue( true ) );

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setPropertyHierarchyLookup( $propertyHierarchyLookup );
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ClassDescriptionInterpreter( $compoundConditionBuilder );

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
			$compoundConditionBuilder->convertConditionToString( $condition )
		);
	}

	public function categoryProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new ClassDescription( array() );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

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

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected,
		);

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

		$description = new ClassDescription( array(
			$categoryFoo,
			$categoryBar
		) );

		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( "{ ?result rdf:type $categoryFooName . }" )->addNewLine()
			->addString( 'UNION' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryBarName . }" )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ClassDescription( array(
			$categoryFoo,
			$categoryBar
		) );

		$orderByProperty = new DIProperty( 'Foo' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryFooName . }" )->addNewLine()
			->addString( 'UNION' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryBarName . }" )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

		return $provider;
	}

}
