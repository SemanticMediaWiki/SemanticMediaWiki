<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConjunctionInterpreterTest extends TestCase {

	private DescriptionFactory $descriptionFactory;
	private $conditionBuilder;

	public function setUp(): void {
		$this->descriptionFactory = new DescriptionFactory();

		$this->conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'interpretDescription' ] )
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

		$description = $this->getMockBuilder( Description::class )
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
