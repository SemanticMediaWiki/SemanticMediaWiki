<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\Tests\Util\UtilityFactory;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

use SMW\Query\Language\ConceptDescription;

use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConceptConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder',
			new ConceptConditionBuilder( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ConceptDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptConditionBuilder( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canBuildConditionFor( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testConceptConditionBuilder( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );

		$instance = new ConceptConditionBuilder();
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
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description =  new ConceptDescription( new DIWikiPage( 'Foo', SMW_NS_CONCEPT ) );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
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
