<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Export\Exporter;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\HierarchyLookup;
use SMW\Query\Language\ClassDescription;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ClassDescriptionInterpreterTest extends TestCase {

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
			ClassDescriptionInterpreter::class,
			new ClassDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( ClassDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
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

		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$categoryName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$hierarchyLookup = $this->getMockBuilder( HierarchyLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$hierarchyLookup->expects( $this->once() )
			->method( 'hasSubcategory' )
			->with( $category )
			->willReturn( true );

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
		$conditionType = FalseCondition::class;

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
		$conditionType = WhereCondition::class;

		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$categoryName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $category )
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
		$conditionType = WhereCondition::class;

		$categoryFoo = new WikiPage( 'Foo', NS_CATEGORY );

		$categoryFooName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $categoryFoo )
		);

		$categoryBar = new WikiPage( 'Bar', NS_CATEGORY );

		$categoryBarName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $categoryBar )
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
		$conditionType = WhereCondition::class;

		$description = new ClassDescription( [
			$categoryFoo,
			$categoryBar
		] );

		$orderByProperty = new Property( 'Foo' );

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
