<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ValueConditionBuilder;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ValueDescription;

use SMW\DIProperty;
use SMW\DIWikiPage;

use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilder\ValueConditionBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ValueConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ValueConditionBuilder',
			new ValueConditionBuilder( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ValueConditionBuilder( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canBuildConditionFor( $description )
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

		$instance = new ValueConditionBuilder( $compoundConditionBuilder );

		$description = new ValueDescription(
			$dataItem,
			null
		);

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition',
			$instance->buildCondition( $description, $resultVariable, null )
		);
	}

	/**
	 * @dataProvider comparatorProvider
	 */
	public function testValueConditionForDifferentComparators( $description, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );

		$instance = new ValueConditionBuilder();
		$instance->setCompoundConditionBuilder( $compoundConditionBuilder );

		$condition = $instance->buildCondition( $description, $resultVariable, null );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
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

		# 10 Regex on a non blob value
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

		# 11 Unknown comparator operator
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
