<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\PropertyConditionBuilder;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\Disjunction;

use SMW\DIWikiPage;
use SMW\DIProperty;

use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilder\PropertyConditionBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PropertyConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\PropertyConditionBuilder',
			new PropertyConditionBuilder( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\SomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyConditionBuilder( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canBuildConditionFor( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testNamespaceCondition( $description, $orderByProperty, $sortkeys, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setSortKeys( $sortkeys );

		$instance = new PropertyConditionBuilder();
		$instance->setCompoundConditionBuilder( $compoundConditionBuilder );

		$condition = $instance->buildCondition( $description, $resultVariable, $orderByProperty );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
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

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_HELP ) )
		);

		$orderByProperty = $property;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Foo wiki:Help-3ASomePropertyPageValue .' )->addNewLine()
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

		return $provider;
	}

}
