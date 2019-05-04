<?php

namespace SMW\Tests;

use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\ConstraintFactory
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
			'\SMW\Constraint\ConstraintRegistry',
			$instance->newConstraintRegistry()
		);
	}

	public function testCanConstructConstraintCheckRunner() {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Constraint\ConstraintCheckRunner',
			$instance->newConstraintCheckRunner()
		);
	}

	public function testCanConstructNullConstraint() {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraints\NullConstraint',
			$instance->newNullConstraint()
		);
	}

	public function testCanConstructConstraintSchemaCompiler() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Constraint\ConstraintSchemaCompiler',
			$instance->newConstraintSchemaCompiler( $store )
		);
	}

	public function testCanConstructConstraintErrorFinder() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Constraint\ConstraintErrorFinder',
			$instance->newConstraintErrorFinder( $store )
		);
	}

	/**
	 * @dataProvider constraintByClass
	 */
	public function testCanConstructConstraintByClass( $class, $expected ) {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Constraint\Constraint',
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
			'\SMW\Constraint\Constraints\NullConstraint'
		];

		yield[
			'SMW\Constraint\Constraints\NamespaceConstraint',
			'\SMW\Constraint\Constraints\NamespaceConstraint'
		];

		yield[
			'SMW\Constraint\Constraints\UniqueValueConstraint',
			'\SMW\Constraint\Constraints\UniqueValueConstraint'
		];

		yield[
			'SMW\Constraint\Constraints\NonNegativeIntegerConstraint',
			'\SMW\Constraint\Constraints\NonNegativeIntegerConstraint'
		];
	}

}
