<?php

namespace SMW\Tests;

use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Fixtures\PlainClass;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintFactory::class,
			new ConstraintFactory()
		);
	}

	public function testCanConstructConstraintOptions() {

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			'\SMW\Options',
			$instance->newConstraintOptions()
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

	public function testNewConstraintByClassInvalidClassThrowsException() {

		$instance = new ConstraintFactory();

		$this->setExpectedException( '\SMW\Exception\ClassNotFoundException' );
		$instance->newConstraintByClass( 'Foo' );
	}

	public function testNewConstraintByClassNonConstraintClassThrowsException() {

		$instance = new ConstraintFactory();

		$this->setExpectedException( '\RuntimeException' );
		$instance->newConstraintByClass( PlainClass::class );
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

		yield [
			'\SMW\Constraint\Constraints\NullConstraint',
			'\SMW\Constraint\Constraints\NullConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\NamespaceConstraint',
			'\SMW\Constraint\Constraints\NamespaceConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\UniqueValueConstraint',
			'\SMW\Constraint\Constraints\UniqueValueConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\NonNegativeIntegerConstraint',
			'\SMW\Constraint\Constraints\NonNegativeIntegerConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\SingleValueConstraint',
			'\SMW\Constraint\Constraints\SingleValueConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\MustExistsConstraint',
			'\SMW\Constraint\Constraints\MustExistsConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\MandatoryPropertiesConstraint',
			'\SMW\Constraint\Constraints\MandatoryPropertiesConstraint'
		];

		yield [
			'SMW\Constraint\Constraints\ShapeConstraint',
			'\SMW\Constraint\Constraints\ShapeConstraint'
		];
	}

}
