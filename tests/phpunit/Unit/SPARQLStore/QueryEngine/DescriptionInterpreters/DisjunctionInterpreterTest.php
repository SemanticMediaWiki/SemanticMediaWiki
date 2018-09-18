<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DisjunctionInterpreterTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter',
			new DisjunctionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Disjunction' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DisjunctionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testConjunctionCondition( $description, $orderByProperty, $sortkeys, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setSortKeys( $sortkeys );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new DisjunctionInterpreter( $conditionBuilder );

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

	public function descriptionProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new Disjunction();

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new Disjunction( [
			new ThingDescription(),
			new ThingDescription()
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 2
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new Disjunction( [
			new NamespaceDescription( NS_MAIN ),
			new NamespaceDescription( NS_HELP )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '{' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->addString( '}' )
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new Disjunction( [
			new NamespaceDescription( NS_MAIN )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 4
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyPageValue', NS_MAIN ), null, SMW_CMP_LIKE
		);

		$description = new Disjunction( [
			$description
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( 'FILTER( regex( ?v1, "^SomePropertyPageValue$", "s") )' )->addNewLine()
			->addString( '?result swivt:wikiPageSortKey ?v1 .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 5
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new Disjunction( [
			new NamespaceDescription( NS_MAIN ),
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ), new DIProperty( 'Foo' ), SMW_CMP_LESS )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'OPTIONAL { {' )->addNewLine()
			->addString( '{ ?v1 swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( '} }' )->addNewLine()
			->addString( ' FILTER( ?result < "SomePropertyBlobValue" || ?result = ?v1 )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 6
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

		$description = new Disjunction( [
			new NamespaceDescription( NS_MAIN ),
			$description
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '{' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '}' )
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 7
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyBlobValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_EQ
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo'),
			$description
		);

		$description = new Disjunction( [
			new NamespaceDescription( NS_MAIN ),
			$description,
			new ValueDescription( new DINumber( 42 ), null, SMW_CMP_EQ )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'OPTIONAL { {' )->addNewLine()
			->addString( '{ ?v2 swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '?v2 property:Foo "SomePropertyBlobValue" .' )->addNewLine()
			->addString( '} }' )->addNewLine()
			->addString( ' FILTER( ?result = "42"^^xsd:double || ?result = ?v2 )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 8
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = new Disjunction( [
			new ValueDescription( new DINumber( 12 ), null, SMW_CMP_EQ )
		] );

		$description = new Disjunction( [
			$description,
			new ValueDescription( new DINumber( 42 ), null, SMW_CMP_LIKE )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 9
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new Disjunction( [
			new ValueDescription( new DINumber( 12 ), null, SMW_CMP_EQ ),
			new ValueDescription( new DIBlob( 'Bar' ), null, SMW_CMP_LIKE )
		] );

		$description = new Disjunction( [
			$description,
			new SomeProperty(
				new DIProperty( 'Foo' ),
				new ValueDescription( new DINumber( 42 ), null, SMW_CMP_LIKE )
			)
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'OPTIONAL { {' )->addNewLine()
			->addString( '?v2 property:Foo ?v1 .' )->addNewLine()
			->addString( '} }' )->addNewLine()
			->addString( ' FILTER( ?result = "12"^^xsd:double || regex( ?result, "^Bar$", "s") || ?result = ?v2 )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 10
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new Disjunction( [
			new Conjunction( [
				new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ ),
				new SomeProperty(
					new DIProperty( 'Bar' ),
					new ThingDescription()
				)
			] ),
			new ValueDescription( new DIBlob( 'Yui' ), null, SMW_CMP_LIKE )
		] );

		$orderByProperty = null;
		$sortkeys = [ 'Bar' => 'ASC' ];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'OPTIONAL { {' )->addNewLine()
			->addString( '?v2 property:Bar ?v1 .' )->addNewLine()
			->addString( '{ ?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( ' FILTER( ?v2 = wiki:Foo ) } }' )->addNewLine()
			->addString( ' FILTER( regex( ?result, "^Yui$", "s") || ?result = ?v2 )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 11
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new Disjunction( [
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ ),
			new ValueDescription( new DIBlob( 'Yui' ), null, SMW_CMP_LIKE )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result = wiki:Foo || regex( ?result, "^Yui$", "s") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 12
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new Conjunction( [
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) )
		] );

		$description = new Conjunction( [
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			$description
		] );

		$description = new Disjunction( [
			$description,
			new ClassDescription( [] )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		return $provider;
	}

}
