<?php

namespace SMW\Tests\SQLStore\QueryEngine\Compiler;

use SMW\Tests\Util\Validator\QueryContainerValidator;

use SMW\SQLStore\QueryEngine\Compiler\DisjunctionConjunctionCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\Disjunction;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\SQLStore\QueryEngine\Compiler\DisjunctionConjunctionCompiler
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DisjunctionConjunctionCompilerTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SQLStore\QueryEngine\Compiler\DisjunctionConjunctionCompiler',
			new DisjunctionConjunctionCompiler( $queryBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testCompileDescription( $description, $expected ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $connection ) );

		$instance = new DisjunctionConjunctionCompiler( new QueryBuilder( $store ) );

		$this->assertTrue( $instance->canCompileDescription( $description ) );
		$this->assertEmpty( $instance->getErrors() );

		$this->queryContainerValidator->assertThatContainerHasProperties(
			$expected,
			$instance->compileDescription( $description )
		);
	}

	public function descriptionProvider() {

		#0 Disjunction
		$description = new Disjunction();
		$description->addDescription( new NamespaceDescription( NS_HELP ) );
		$description->addDescription( new NamespaceDescription( NS_MAIN ) );

		$expectedDisjunction = new \stdClass;
		$expectedDisjunction->type = 3;
		$expectedDisjunction->components = array( 1 => true, 2 => true );

		$provider[] = array(
			$description,
			$expectedDisjunction
		);

		#1 Conjunction
		$description = new Conjunction();
		$description->addDescription( new NamespaceDescription( NS_HELP ) );
		$description->addDescription( new NamespaceDescription( NS_MAIN ) );

		$expectedConjunction = new \stdClass;
		$expectedConjunction->type = 4;
		$expectedConjunction->components = array( 1 => true, 2 => true );

		$provider[] = array(
			$description,
			$expectedConjunction
		);

		#2 No query
		$description = new Conjunction();
		$description->addDescription( new ThingDescription() );

		$expectedConjunction = new \stdClass;
		$expectedConjunction->type = 0;
		$expectedConjunction->components = array();

		$provider[] = array(
			$description,
			$expectedConjunction
		);

		return $provider;
	}

}
