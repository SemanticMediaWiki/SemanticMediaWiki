<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Interpreter;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Interpreter\ConjunctionInterpreter;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Interpreter\ConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConjunctionInterpreterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Interpreter\ConjunctionInterpreter',
			new ConjunctionInterpreter( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Conjunction' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConjunctionInterpreter( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testConjunctionCondition( $description, $orderByProperty, $sortkeys, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setSortKeys( $sortkeys );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );
		$compoundConditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ConjunctionInterpreter( $compoundConditionBuilder );

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

	public function descriptionProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = new Conjunction();

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new Conjunction( array(
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) )
		) );

		$description = new Conjunction( array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			$description
		) );

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

		# 2
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = new Conjunction( array( new ThingDescription() ) );

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
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

		$description = new Conjunction( array(
			$description,
			new NamespaceDescription( NS_HELP )
		) );

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 4
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new Conjunction( array(
			new NamespaceDescription( NS_MAIN ),
			new ValueDescription( new DIWikiPage( 'SomePageValue', NS_MAIN ) )
		) );

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '{ wiki:SomePageValue swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
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

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyBlobValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo'),
			$description
		);

		$description = new Conjunction( array(
			$description,
			new ValueDescription( new DIBlob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LESS ),
			new ValueDescription( new DIBlob( 'YetAnotherPropertyBlobValue' ), null, SMW_CMP_GRTR ),
			new NamespaceDescription( NS_MAIN )
		) );

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( 'FILTER( ?result < "SomeOtherPropertyBlobValue" && ?result > "YetAnotherPropertyBlobValue" )' )
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 6
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyBlobValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo'),
			$description
		);

		$description = new Conjunction( array(
			$description,
			new ValueDescription( new DIBlob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LIKE ),
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_MAIN ) ),
			new NamespaceDescription( NS_MAIN )
		) );

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( 'wiki:SomePropertyPageValue property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '{ wiki:SomePropertyPageValue swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomeOtherPropertyBlobValue$", "s") )' )
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		);

		# 7
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new Conjunction( array(
			new ValueDescription( new DIBlob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LIKE ),
			new ValueDescription( new DIBlob( 'YetAnotherPropertyBlobValue' ), new DIProperty( 'Foo'), SMW_CMP_NLKE ),
			new ThingDescription()
		) );

		$orderByProperty = null;
		$sortkeys = array();

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomeOtherPropertyBlobValue$", "s") && ' )
			->addString( '!regex( ?result, "^YetAnotherPropertyBlobValue$", "s") )' )->addNewLine()
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

		$propertyValue = new DIWikiPage( 'SomePropertyPageValue', NS_HELP );

		$propertyValueName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $propertyValue )
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ValueDescription( $propertyValue )
		);

		$category = new DIWikiPage( 'Bar', NS_CATEGORY );

		$categoryName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$description = new Conjunction(array(
			$description,
			new ClassDescription( $category )
		) );

		$orderByProperty = new DIProperty( 'Foo' );
		$sortkeys = array( 'Foo' => 'ASC' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "?result property:Foo $propertyValueName ." )->addNewLine()
			->addString( '{ ?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryName . }" )->addNewLine()
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
