<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWDIUri as DIUri;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ValueDescriptionInterpreterTest extends TestCase {

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
			ValueDescriptionInterpreter::class,
			new ValueDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueDescriptionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider notSupportedDataItemTypeProvider
	 */
	public function testCreateFalseConditionForNotSupportedDataItemType( $dataItem ) {
		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->setConstructorArgs( [ $this->descriptionInterpreterFactory ] )
			->setMethods( [ 'isSetFlag' ] )
			->getMock();

		$conditionBuilder->expects( $this->once() )
			->method( 'isSetFlag' )
			->willReturn( false );

		$instance = new ValueDescriptionInterpreter( $conditionBuilder );

		$description = new ValueDescription(
			$dataItem,
			null
		);

		$this->assertInstanceOf(
			FalseCondition::class,
			$instance->interpretDescription( $description )
		);
	}

	/**
	 * @dataProvider comparatorProvider
	 */
	public function testValueConditionForDifferentComparators( $description, $expectedConditionType, $expectedConditionString ) {
		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ValueDescriptionInterpreter( $conditionBuilder );

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

	public function testValueConditionOnRediret() {
		$resultVariable = 'result';

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->setConstructorArgs( [ $this->descriptionInterpreterFactory ] )
			->setMethods( [ 'tryToFindRedirectVariableForDataItem' ] )
			->getMock();

		$conditionBuilder->expects( $this->once() )
			->method( 'tryToFindRedirectVariableForDataItem' )
			->willReturn( '?r1' );

		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ValueDescriptionInterpreter( $conditionBuilder );

		$description = new ValueDescription(
			new DIWikiPage( 'Foo', NS_MAIN ),
			null
		);

		$condition = $instance->interpretDescription( $description );

		$expectedConditionType = FilterCondition::class;

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		// The redirect pattern add by conditionBuilder at th end of
		// the mapping
		$expected = UtilityFactory::getInstance()->newStringBuilder()
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( 'FILTER( ?result = ?r1 )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expected,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	/**
	 * @dataProvider noCaseDescritionProvider
	 */
	public function testValueConditionOnNoCase( $description, $expected ) {
		$engineOptions = new EngineOptions();
		$engineOptions->set( 'smwgSparqlQFeatures', SMW_SPARQL_QF_NOCASE );

		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory, $engineOptions );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ValueDescriptionInterpreter( $conditionBuilder );

		$condition = $instance->interpretDescription( $description );

		$this->assertEquals(
			$expected,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	public function comparatorProvider() {
		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = SingletonCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_EQ
		);

		$expected = $stringBuilder
			->addString( '"SomePropertyValue" swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 1
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result < "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 2 Less for a non-blob (DIWikiPage type) value
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyValuePage', NS_MAIN ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( 'FILTER( ?resultsk < "SomePropertyValuePage" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 3
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIProperty( 'SomeProperty' ),
			null,
			SMW_CMP_LESS
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result < property:SomeProperty )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 4
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_GRTR
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result > "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 5
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LEQ
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result <= "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 6
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_GEQ
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result >= "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 7
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NEQ
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result != "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 8
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LIKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomePropertyValue$", "s") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 9
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NLKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( !regex( ?result, "^SomePropertyValue$", "s") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 10 Regex on a non-blob value
		$conditionType = TrueCondition::class;

		$description = new ValueDescription(
			new DINumber( 42 ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NLKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 11 Regex on a non-blob (DIWikiPage type) value
		$conditionType = SingletonCondition::class;

		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyValuePage', NS_MAIN ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LIKE
		);

		$expected = $stringBuilder
			->addString( 'FILTER( regex( ?v1, "^SomePropertyValuePage$", "s") )' )->addNewLine()
			->addString( '?result swivt:wikiPageSortKey ?v1 .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 12 Regex on a non-blob (DIUri type) value
		$conditionType = FilterCondition::class;

		$description = new ValueDescription(
			new DIUri( 'http', '//example.org', '', '' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LIKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( str( ?result ), "^.*//example\\\.org$", "i") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		# 13 Unknown comparator operator
		$conditionType = TrueCondition::class;

		$description = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$conditionType,
			$expected
		];

		return $provider;
	}

	public function noCaseDescritionProvider() {
		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NLKE
		);

		$expected = $stringBuilder->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( !regex( ?result, "^SomePropertyValue$", "i") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$expected
		];

		# 1
		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_PRIM_LIKE
		);

		$expected = $stringBuilder->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomePropertyValue$", "i") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$expected
		];

		# 2
		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_EQ
		);

		$expected = $stringBuilder->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( lcase(str(?result) ) = "somepropertyvalue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$expected
		];

		# 3
		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NEQ
		);

		$expected = $stringBuilder->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( lcase(str(?result) ) != "somepropertyvalue" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$expected
		];

		# 4
		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyValuePage', NS_MAIN ),
			new DIProperty( 'Foo' ),
			SMW_CMP_EQ
		);

		$expected = $stringBuilder->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( 'FILTER( lcase(str(?resultsk) ) = "somepropertyvaluepage" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$expected
		];

		# 5
		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyValuePage', NS_MAIN ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NEQ
		);

		$expected = $stringBuilder->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( 'FILTER( lcase(str(?resultsk) ) != "somepropertyvaluepage" )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$expected
		];

		return $provider;
	}

	public function notSupportedDataItemTypeProvider() {
		$dataItem = $this->getMockBuilder( '\SMWDIGeoCoord' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$dataItem
		];

		$dataItem = $this->getMockBuilder( DIConcept::class )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$dataItem
		];

		return $provider;
	}

}
