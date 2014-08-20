<?php

namespace SMW\Tests\SQLStore\QueryEngine\Compiler;

use SMW\Tests\Util\Validator\QueryContainerValidator;

use SMW\SQLStore\QueryEngine\Compiler\ThingDescriptionCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\SQLStore\QueryEngine\Compiler\ThingDescriptionCompiler
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ThingDescriptionCompilerTest extends \PHPUnit_Framework_TestCase {

	private $queryContainerValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryContainerValidator = new QueryContainerValidator();
	}

	public function testCanConstruct() {

		$queryBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Compiler\ThingDescriptionCompiler',
			new ThingDescriptionCompiler( $queryBuilder )
		);
	}

	public function testCompileDescription() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( '\SMW\Query\Language\ThingDescription' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$expected = new \stdClass;
		$expected->type = 0;
		$expected->queryNumber = 0;

		$instance = new ThingDescriptionCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

}
