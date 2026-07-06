<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine\DescriptionInterpreters;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\Query\Language\ThingDescription;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use stdClass;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ThingDescriptionInterpreterTest extends TestCase {

	private $store;
	private $conditionBuilder;
	private $querySegmentValidator;

	protected function setUp(): void {
		parent::setUp();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->conditionBuilder = $this->getMockBuilder( ConditionBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ThingDescriptionInterpreter::class,
			new ThingDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	public function testInterpretDescription() {
		$description = $this->getMockBuilder( ThingDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$expected = new stdClass;
		$expected->type = 0;
		$expected->queryNumber = 0;

		$queryEngineFactory = new QueryEngineFactory(
			$this->store
		);

		$instance = new ThingDescriptionInterpreter(
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
