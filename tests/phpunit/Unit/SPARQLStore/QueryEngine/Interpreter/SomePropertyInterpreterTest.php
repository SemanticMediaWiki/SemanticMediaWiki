<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Interpreter;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Interpreter\SomePropertyInterpreter;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWDITime as DITime;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Interpreter\SomePropertyInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SomePropertyInterpreterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Interpreter\SomePropertyInterpreter',
			new SomePropertyInterpreter( $compoundConditionBuilder )
		);
	}

	public function testCanInterpretDescription() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\SomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SomePropertyInterpreter( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testSomeProperty( $description, $orderByProperty, $sortkeys, $expectedConditionType, $expectedConditionString ) {

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setPropertyHierarchyLookup( $propertyHierarchyLookup );
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setSortKeys( $sortkeys );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );
		$compoundConditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new SomePropertyInterpreter( $compoundConditionBuilder );

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

		$property = new DIProperty( 'Foo' );

		$propertyHierarchyLookup = $this->getMockBuilder( '\SMW\PropertyHierarchyLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertyHierarchyLookup->expects( $this->once() )
			->method( 'hasSubpropertyFor' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( true ) );

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setPropertyHierarchyLookup( $propertyHierarchyLookup );
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );

		$instance = new SomePropertyInterpreter( $compoundConditionBuilder );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$condition = $instance->interpretDescription( $description );

		$expected = UtilityFactory::getInstance()->newStringBuilder()
			->addString( '?result ?sp2 ?v1 .' )->addNewLine()
			->addString( '{ ' )->addNewLine()
			->addString( '?sp2 rdfs:subPropertyOf* property:Foo .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expected,
			$compoundConditionBuilder->convertConditionToString( $condition )
		);
	}

	public function descriptionProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description =  new SomeProperty(
			new DIProperty( 'Foo'),
			new Disjunction()
		);

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description =  new SomeProperty(
			new DIProperty( 'Foo'),
			new ThingDescription()
		);

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 2 Inverse
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new SomeProperty(
			new DIProperty( 'Foo', true ),
			new ThingDescription()
		);

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?v1 property:Foo ?result .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description =  new SomeProperty(
			new DIProperty( 'Foo'),
			new ThingDescription()
		);

		$orderByProperty = new DIProperty( 'Foo');
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 4
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ) )
		);

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result property:Foo "SomePropertyBlobValue" .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 5
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ) )
		);

		$orderByProperty = $property;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Foo "SomePropertyBlobValue" .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 6
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$propertyValue = new DIWikiPage( 'SomePropertyPageValue', NS_HELP );

		$propertyValueName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $propertyValue )
		);

		$description = new SomeProperty(
			$property,
			new ValueDescription( $propertyValue )
		);

		$orderByProperty = $property;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "?result property:Foo $propertyValueName ." )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 7
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_HELP ), $property, SMW_CMP_LEQ )
		);

		$orderByProperty = new DIProperty( 'SomePropertyPageValue' );
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1sk <= "SomePropertyPageValue" )' )->addNewLine()
			->addString( '?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 8
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_HELP ), $property, SMW_CMP_LEQ )
		);

		$description = new SomeProperty(
			new DIProperty( 'Bar' ),
			$description
		);

		$orderByProperty = new DIProperty( 'Bar' );
		$sortkeys = array( 'Foo' => 'ASC' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Bar ?v1 .' )->addNewLine()
			->addString( '{ ?v1 property:Foo ?v2 .' )->addNewLine()
			->addString( 'FILTER( ?v2sk <= "SomePropertyPageValue" )' )->addNewLine()
			->addString( '?v2 swivt:wikiPageSortKey ?v2sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 9 Inverse -> ?v1 property:Foo ?v2 vs. ?v2 property:Foo ?v1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo', true );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_HELP ), $property, SMW_CMP_LEQ )
		);

		$description = new SomeProperty(
			new DIProperty( 'Bar' ),
			$description
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Bar ?v1 .' )->addNewLine()
			->addString( '{ ?v2 property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v2sk <= "SomePropertyPageValue" )' )->addNewLine()
			->addString( '?v2 swivt:wikiPageSortKey ?v2sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 10
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( '_MDAT' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$sortkeys = array( '_MDAT' => 'ASC' );
		$propertyLabel = str_replace( ' ', '_', $property->getLabel() );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "?result property:{$propertyLabel}-23aux ?v1 ." )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 11, issue 556
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new Disjunction( array(
				new ValueDescription( new DIBlob( 'Bar' ) ),
				new ValueDescription( new DIBlob( 'Baz' ) )
			) )
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 = "Bar" || ?v1 = "Baz" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 12 use the rdf/owl equivalent for a predefined property
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( '_SUBC' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'Bar' ) )
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result rdfs:subClassOf "Bar" .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 13
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( '_SUBP' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'Bar', SMW_NS_PROPERTY ) )
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result rdfs:subPropertyOf property:Bar .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 14 aux-property
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$property = new DIProperty( '_MDAT' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DITime( 1, 1970, 01, 01, 1, 1 ) )
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Modification_date-23aux "2440587.5423611"^^xsd:double .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		return $provider;
	}

}
