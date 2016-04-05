<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Interpreter;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWDIUri as DIUri;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ValueDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter',
			new ValueDescriptionInterpreter( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueDescriptionInterpreter( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider notSupportedDataItemTypeProvider
	 */
	public function testCreateFalseConditionForNotSupportedDataItemType( $dataItem ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueDescriptionInterpreter( $compoundConditionBuilder );

		$description = new ValueDescription(
			$dataItem,
			null
		);

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition',
			$instance->interpretDescription( $description )
		);
	}

	/**
	 * @dataProvider comparatorProvider
	 */
	public function testValueConditionForDifferentComparators( $description, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ValueDescriptionInterpreter( $compoundConditionBuilder );

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

	public function testValueConditionForRediret() {

		$resultVariable = 'result';

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->setMethods( array( 'tryToFindRedirectVariableForDataItem' ) )
			->getMock();

		$compoundConditionBuilder->expects( $this->once() )
			->method( 'tryToFindRedirectVariableForDataItem' )
			->will( $this->returnValue( '?r1' ) );

		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );

		$instance = new ValueDescriptionInterpreter( $compoundConditionBuilder );

		$description = new ValueDescription(
			new DIWikiPage( 'Foo', NS_MAIN ),
			null
		);

		$condition = $instance->interpretDescription( $description );

		$expectedConditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		// The redirect pattern add by compoundConditionBuilder at th end of
		// the mapping
		$expected = UtilityFactory::getInstance()->newStringBuilder()
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( 'FILTER( ?result = ?r1 )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expected,
			$compoundConditionBuilder->convertConditionToString( $condition )
		);
	}

	public function comparatorProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_EQ
		);

		$expected = $stringBuilder
			->addString( '"SomePropertyValue" swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result < "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 2 Less for a non-blob (DIWikiPage type) value
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyValuePage', NS_MAIN ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( 'FILTER( ?resultsk < "SomePropertyValuePage" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIProperty( 'SomeProperty' ),
			null,
			SMW_CMP_LESS
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result < property:SomeProperty )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 4
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_GRTR
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result > "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 5
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LEQ
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result <= "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 6
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_GEQ
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result >= "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 7
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NEQ
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( ?result != "SomePropertyValue" )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 8
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LIKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomePropertyValue$", "s") )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 9
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NLKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( !regex( ?result, "^SomePropertyValue$", "s") )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 10 Regex on a non-blob value
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = new ValueDescription(
			new DINumber( 42 ),
			new DIProperty( 'Foo' ),
			SMW_CMP_NLKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 11 Regex on a non-blob (DIWikiPage type) value
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new ValueDescription(
			new DIWikiPage( 'SomePropertyValuePage', NS_MAIN ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LIKE
		);

		$expected = $stringBuilder
			->addString( 'FILTER( regex( ?v1, "^SomePropertyValuePage$", "s") )' )->addNewLine()
			->addString( '?result swivt:wikiPageSortKey ?v1 .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 12 Regex on a non-blob (DIUri type) value
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new ValueDescription(
			new DIUri( 'http', '//example.org', '', '' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LIKE
		);

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( str( ?result ), "^//example\\\.org$", "i") )' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		# 13 Unknown comparator operator
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$conditionType,
			$expected
		);

		return $provider;
	}

	public function notSupportedDataItemTypeProvider() {

		$dataItem = $this->getMockBuilder( '\SMWDIGeoCoord' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = array(
			$dataItem
		);

		$dataItem = $this->getMockBuilder( '\SMW\DIConcept' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = array(
			$dataItem
		);

		return $provider;
	}

}
