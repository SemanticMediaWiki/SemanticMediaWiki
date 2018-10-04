<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\NamespaceDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NamespaceDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter',
			new NamespaceDescriptionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\NamespaceDescription' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new NamespaceDescriptionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testNamespaceCondition( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new NamespaceDescriptionInterpreter( $conditionBuilder );

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

	public function namespaceProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description =  new NamespaceDescription( NS_MAIN );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
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
