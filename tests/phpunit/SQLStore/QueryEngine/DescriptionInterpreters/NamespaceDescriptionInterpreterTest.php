<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Validators\QuerySegmentValidator;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class NamespaceDescriptionInterpreterTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $conditionBuilder;
	private $descriptionFactory;
	private QuerySegmentValidator $querySegmentValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->descriptionFactory = new DescriptionFactory();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NamespaceDescriptionInterpreter::class,
			new NamespaceDescriptionInterpreter( $this->store, $this->conditionBuilder )
		);
	}

	public function testInterpretDescription() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$queryEngineFactory = new QueryEngineFactory(
			$this->store
		);

		$description = $this->descriptionFactory->newNamespaceDescription(
			NS_HELP
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$instance = new NamespaceDescriptionInterpreter(
			$this->store,
			$queryEngineFactory->newConditionBuilder()
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
