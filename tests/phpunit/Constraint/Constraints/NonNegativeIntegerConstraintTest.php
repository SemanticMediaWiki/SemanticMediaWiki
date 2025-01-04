<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\NonNegativeIntegerConstraint;
use SMW\Tests\PHPUnitCompat;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Constraint\Constraints\NonNegativeIntegerConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class NonNegativeIntegerConstraintTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NonNegativeIntegerConstraint::class,
			new NonNegativeIntegerConstraint()
		);
	}

	public function testGetType() {
		$instance = new NonNegativeIntegerConstraint();

		$this->assertEquals(
			NonNegativeIntegerConstraint::TYPE_INSTANT,
			$instance->getType()
		);
	}

	public function testHasViolation() {
		$instance = new NonNegativeIntegerConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_non_negative_integer() {
		$constraint = [
			'non_negative_integer' => true
		];

		$expectedErrMsg = 'smw-constraint-violation-non-negative-integer';

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'addError' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function ( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDINumber( -1 ) );

		$instance = new NonNegativeIntegerConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_non_negative_integer_ThrowsException() {
		$constraint = [
			'non_negative_integer' => true
		];

		$instance = new NonNegativeIntegerConstraint();

		$this->expectException( '\RuntimeException' );
		$instance->checkConstraint( $constraint, 'Foo' );
	}

	public function checkConstraintError( $error, $expectedErrMsg ) {
		if ( strpos( $error->__toString(), $expectedErrMsg ) !== false ) {
			return true;
		}

		return false;
	}

}
