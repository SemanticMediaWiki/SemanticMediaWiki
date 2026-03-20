<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Export\Exporter;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ConjunctionInterpreterTest extends TestCase {

	private $descriptionInterpreterFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	public function testCanConstruct() {
		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ConjunctionInterpreter::class,
			new ConjunctionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( Conjunction::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConjunctionInterpreter( $conditionBuilder );

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

		$instance = new ConjunctionInterpreter( $conditionBuilder );

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
		$conditionType = TrueCondition::class;

		$description = new Conjunction();

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

		# 1
		$conditionType = FalseCondition::class;

		$description = new Conjunction( [
			new ValueDescription( new WikiPage( 'Bar', NS_MAIN ) )
		] );

		$description = new Conjunction( [
			new ValueDescription( new WikiPage( 'Foo', NS_MAIN ) ),
			$description
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
		$conditionType = TrueCondition::class;

		$description = new Conjunction( [ new ThingDescription() ] );

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

		# 3
		$conditionType = WhereCondition::class;

		$description = new SomeProperty(
			new Property( 'Foo' ),
			new ThingDescription()
		);

		$description = new Conjunction( [
			$description,
			new NamespaceDescription( NS_HELP )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 4
		$conditionType = SingletonCondition::class;

		$description = new Conjunction( [
			new NamespaceDescription( NS_MAIN ),
			new ValueDescription( new WikiPage( 'SomePageValue', NS_MAIN ) )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '{ wiki:SomePageValue swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 5
		$conditionType = WhereCondition::class;

		$description = new ValueDescription(
			new Blob( 'SomePropertyBlobValue' ),
			new Property( 'Foo' ),
			SMW_CMP_LESS
		);

		$description = new SomeProperty(
			new Property( 'Foo' ),
			$description
		);

		$description = new Conjunction( [
			$description,
			new ValueDescription( new Blob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LESS ),
			new ValueDescription( new Blob( 'YetAnotherPropertyBlobValue' ), null, SMW_CMP_GRTR ),
			new NamespaceDescription( NS_MAIN )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( 'FILTER( ?result < "SomeOtherPropertyBlobValue" && ?result > "YetAnotherPropertyBlobValue" )' )
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 6
		$conditionType = SingletonCondition::class;

		$description = new ValueDescription(
			new Blob( 'SomePropertyBlobValue' ),
			new Property( 'Foo' ),
			SMW_CMP_LESS
		);

		$description = new SomeProperty(
			new Property( 'Foo' ),
			$description
		);

		$description = new Conjunction( [
			$description,
			new ValueDescription( new Blob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LIKE ),
			new ValueDescription( new WikiPage( 'SomePropertyPageValue', NS_MAIN ) ),
			new NamespaceDescription( NS_MAIN )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( 'wiki:SomePropertyPageValue property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '{ wiki:SomePropertyPageValue swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomeOtherPropertyBlobValue$", "s") )' )
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 7
		$conditionType = FilterCondition::class;

		$description = new Conjunction( [
			new ValueDescription( new Blob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LIKE ),
			new ValueDescription( new Blob( 'YetAnotherPropertyBlobValue' ), new Property( 'Foo' ), SMW_CMP_NLKE ),
			new ThingDescription()
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomeOtherPropertyBlobValue$", "s") && ' )
			->addString( '!regex( ?result, "^YetAnotherPropertyBlobValue$", "s") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 8
		$conditionType = WhereCondition::class;

		$propertyValue = new WikiPage( 'SomePropertyPageValue', NS_HELP );

		$propertyValueName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $propertyValue )
		);

		$description = new SomeProperty(
			new Property( 'Foo' ),
			new ValueDescription( $propertyValue )
		);

		$category = new WikiPage( 'Bar', NS_CATEGORY );

		$categoryName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$description = new Conjunction( [
			$description,
			new ClassDescription( $category )
		] );

		$orderByProperty = new Property( 'Foo' );
		$sortkeys = [ 'Foo' => 'ASC' ];

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "?result property:Foo $propertyValueName ." )->addNewLine()
			->addString( '{ ?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryName . }" )->addNewLine()
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
