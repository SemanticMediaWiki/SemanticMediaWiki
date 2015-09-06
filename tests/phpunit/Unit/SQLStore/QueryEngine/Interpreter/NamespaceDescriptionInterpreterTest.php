<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SQLStore\QueryEngine\Interpreter\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;

use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;

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

	protected function setUp() {
		parent::setUp();

		$this->querySegmentValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQuerySegmentValidator();
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

	public function testCompileDescription() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$querySegmentListBuilder = new QuerySegmentListBuilder( $store );

		$description = new NamespaceDescription( NS_HELP );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$instance = new NamespaceDescriptionInterpreter( $querySegmentListBuilder );

		$this->assertTrue( $instance->canInterpretDescription( $description ) );

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

}
