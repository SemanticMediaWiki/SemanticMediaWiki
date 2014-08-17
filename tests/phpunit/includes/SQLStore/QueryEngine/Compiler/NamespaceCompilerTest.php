<?php

namespace SMW\Tests\SQLStore\QueryEngine\Compiler;

use SMW\Tests\Util\Validator\QueryContainerValidator;

use SMW\SQLStore\QueryEngine\Compiler\NamespaceCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;

/**
 * @covers \SMW\SQLStore\QueryEngine\Compiler\NamespaceCompiler
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NamespaceCompilerTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SQLStore\QueryEngine\Compiler\NamespaceCompiler',
			new NamespaceCompiler( $queryBuilder )
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
			->method( 'getDatabase' )
			->will( $this->returnValue( $connection ) );

		$queryBuilder = new QueryBuilder( $store );

		$description = new NamespaceDescription( NS_HELP );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$instance = new NamespaceCompiler( $queryBuilder );

		$this->assertTrue( $instance->canCompileDescription( $description ) );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

}
