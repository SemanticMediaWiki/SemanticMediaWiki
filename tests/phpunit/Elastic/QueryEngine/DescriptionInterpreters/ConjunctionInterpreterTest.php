<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\DIWikiPage;
use SMW\Query\DescriptionFactory;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConjunctionInterpreterTest extends \PHPUnit\Framework\TestCase {

	private DescriptionFactory $descriptionFactory;
	private $conditionBuilder;

	public function setUp(): void {
		$this->descriptionFactory = new DescriptionFactory();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'interpretDescription' ] )
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConjunctionInterpreter::class,
			new ConjunctionInterpreter( $this->conditionBuilder )
		);
	}

	public function testInterpretDescription_Empty() {
		$instance = new ConjunctionInterpreter(
			$this->conditionBuilder
		);

		$condition = $instance->interpretDescription(
			$this->descriptionFactory->newConjunction( [] )
		);

		$this->assertEquals(
			[],
			$condition
		);
	}

	public function testInterpretDescription_NotEmpty() {
		$this->conditionBuilder->expects( $this->any() )
			->method( 'interpretDescription' )
			->willReturn( $this->conditionBuilder->newCondition( [ 'Foo' ] ) );

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->any() )
			->method( 'getPrintRequests' )
			->willReturn( [] );

		$instance = new ConjunctionInterpreter(
			$this->conditionBuilder
		);

		$condition = $instance->interpretDescription(
			$this->descriptionFactory->newConjunction( [ $description ] )
		);

		$this->assertEquals(
			'{"bool":{"must":[{"bool":{"must":["Foo"]}}]}}',
			$condition
		);
	}

}
