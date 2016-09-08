<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\Query\Language\ThingDescription;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ThingDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $descriptionInterpreterFactory;

	protected function setUp() {
		parent::setUp();

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter',
			new ThingDescriptionInterpreter( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ThingDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ThingDescriptionInterpreter( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testThingDescriptionInterpreter( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder( $this->descriptionInterpreterFactory );
		$compoundConditionBuilder->setResultVariable( $resultVariable );
		$compoundConditionBuilder->setJoinVariable( $resultVariable );
		$compoundConditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ThingDescriptionInterpreter( $compoundConditionBuilder );

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

		$description =  new ThingDescription();
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

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description =  new ThingDescription();
		$orderByProperty = new DIProperty( 'Foo' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
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
