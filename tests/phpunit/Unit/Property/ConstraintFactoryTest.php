<?php

namespace SMW\Tests\Property;

use SMW\Property\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\ConstraintFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintFactory::class,
			new ConstraintFactory()
		);
	}

	public function testCanConstructConstraintRegistry() {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\ConstraintRegistry',
			$instance->newConstraintRegistry()
		);
	}

	public function testCanConstructConstraintCheckRunner() {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\ConstraintCheckRunner',
			$instance->newConstraintCheckRunner()
		);
	}

	public function testCanConstructNullConstraint() {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\Constraints\NullConstraint',
			$instance->newNullConstraint()
		);
	}

	public function testCanConstructConstraintSchemaCompiler() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\ConstraintSchemaCompiler',
			$instance->newConstraintSchemaCompiler( $store )
		);
	}

	public function testCanConstructConstraintErrorFinder() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\ConstraintErrorFinder',
			$instance->newConstraintErrorFinder( $store )
		);
	}

	/**
	 * @dataProvider constraintByClass
	 */
	public function testCanConstructConstraintByClass( $class, $expected ) {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Property\Constraint\Constraint',
			$instance->newConstraintByClass( $class )
		);

		$this->assertInstanceOf(
			$expected,
			$instance->newConstraintByClass( $class )
		);
	}

	public function constraintByClass() {

		yield[
			'null',
			'\SMW\Property\Constraint\Constraints\NullConstraint'
		];

		yield[
			'SMW\Property\Constraint\Constraints\CommonConstraint',
			'\SMW\Property\Constraint\Constraints\CommonConstraint'
		];
	}

}
