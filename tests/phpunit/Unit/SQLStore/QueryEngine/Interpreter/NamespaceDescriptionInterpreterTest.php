<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\Interpreter\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\NamespaceDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class NamespaceDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;
	private $descriptionFactory;

	protected function setUp() {
		parent::setUp();

		$this->descriptionFactory = new DescriptionFactory();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\NamespaceDescriptionInterpreter',
			new NamespaceDescriptionInterpreter( $querySegmentListBuilder )
		);
	}

	public function testInterpretDescription() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$queryEngineFactory = new QueryEngineFactory( $store );

		$description = $this->descriptionFactory->newNamespaceDescription(
			NS_HELP
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$instance = new NamespaceDescriptionInterpreter(
			$queryEngineFactory->newQuerySegmentListBuilder()
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

}
