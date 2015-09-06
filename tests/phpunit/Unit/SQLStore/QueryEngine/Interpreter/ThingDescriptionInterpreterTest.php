<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Tests\Utils\UtilityFactory;
use SMW\SQLStore\QueryEngine\Interpreter\ThingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\ThingDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ThingDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;

	protected function setUp() {
		parent::setUp();

		$this->querySegmentValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\ThingDescriptionInterpreter',
			new ThingDescriptionInterpreter( $querySegmentListBuilder )
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

		$instance = new ThingDescriptionInterpreter( new QuerySegmentListBuilder( $store ) );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

}
