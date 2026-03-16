<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintCheckRunner;
use SMW\Constraint\ConstraintRegistry;
use SMW\Constraint\Constraints\MandatoryPropertiesConstraint;
use SMW\Constraint\Constraints\MustExistsConstraint;
use SMW\Constraint\Constraints\NamespaceConstraint;
use SMW\Constraint\Constraints\NonNegativeIntegerConstraint;
use SMW\Constraint\Constraints\NullConstraint;
use SMW\Constraint\Constraints\ShapeConstraint;
use SMW\Constraint\Constraints\SingleValueConstraint;
use SMW\Constraint\Constraints\UniqueValueConstraint;
use SMW\Constraint\ConstraintSchemaCompiler;
use SMW\ConstraintFactory;
use SMW\Exception\ClassNotFoundException;
use SMW\Store;
use SMW\Tests\Fixtures\PlainClass;

/**
 * @covers \SMW\ConstraintFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConstraintFactory::class,
			new ConstraintFactory()
		);
	}

	public function testCanConstructConstraintRegistry() {
		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			ConstraintRegistry::class,
			$instance->newConstraintRegistry()
		);
	}

	public function testCanConstructConstraintCheckRunner() {
		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			ConstraintCheckRunner::class,
			$instance->newConstraintCheckRunner()
		);
	}

	public function testCanConstructNullConstraint() {
		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			NullConstraint::class,
			$instance->newNullConstraint()
		);
	}

	public function testCanConstructConstraintSchemaCompiler() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			ConstraintSchemaCompiler::class,
			$instance->newConstraintSchemaCompiler( $store )
		);
	}

	public function testNewConstraintByClassInvalidClassThrowsException() {
		$instance = new ConstraintFactory();

		$this->expectException( ClassNotFoundException::class );
		$instance->newConstraintByClass( 'ThisIsNotAClass' );
	}

	public function testNewConstraintByClassNonConstraintClassThrowsException() {
		$instance = new ConstraintFactory();

		$this->expectException( '\RuntimeException' );
		$instance->newConstraintByClass( PlainClass::class );
	}

	/**
	 * @dataProvider constraintByClassProvider
	 */
	public function testCanConstructConstraintByClass( $class, $expected ) {
		$instance = new ConstraintFactory();

		$this->assertInstanceOf(
			Constraint::class,
			$instance->newConstraintByClass( $class )
		);

		$this->assertInstanceOf(
			$expected,
			$instance->newConstraintByClass( $class )
		);
	}

	public function constraintByClassProvider() {
		yield [
			NullConstraint::class,
			NullConstraint::class
		];

		yield [
			NamespaceConstraint::class,
			NamespaceConstraint::class
		];

		yield [
			UniqueValueConstraint::class,
			UniqueValueConstraint::class
		];

		yield [
			NonNegativeIntegerConstraint::class,
			NonNegativeIntegerConstraint::class
		];

		yield [
			SingleValueConstraint::class,
			SingleValueConstraint::class
		];

		yield [
			MustExistsConstraint::class,
			MustExistsConstraint::class
		];

		yield [
			MandatoryPropertiesConstraint::class,
			MandatoryPropertiesConstraint::class
		];

		yield [
			ShapeConstraint::class,
			ShapeConstraint::class
		];
	}

}
