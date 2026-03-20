<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\Query\Language\ThingDescription;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ThingDescriptionInterpreterTest extends TestCase {

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
			ThingDescriptionInterpreter::class,
			new ThingDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( ThingDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ThingDescriptionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testThingDescriptionInterpreter( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {
		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ThingDescriptionInterpreter( $conditionBuilder );

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

		$description = new ThingDescription();
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		];

		# 1
		$conditionType = TrueCondition::class;

		$description = new ThingDescription();
		$orderByProperty = new Property( 'Foo' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
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
