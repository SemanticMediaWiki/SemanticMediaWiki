<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\SingleValueConstraint;
use SMW\Tests\PHPUnitCompat;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Constraint\Constraints\SingleValueConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SingleValueConstraintTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp() {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SingleValueConstraint::class,
			new SingleValueConstraint()
		);
	}

	public function testGetType() {

		$instance = new SingleValueConstraint();

		$this->assertEquals(
			SingleValueConstraint::TYPE_INSTANT,
			$instance->getType()
		);
	}

	public function testHasViolation() {

		$instance = new SingleValueConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_single_value_constraint() {

		$constraint = [
			'single_value_constraint' => true
		];

		$expectedErrMsg = 'smw-datavalue-constraint-violation-single-value';

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIProperty( 'Foobar' ) ] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'addError', 'getCallable' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getCallable' )
			->will( $this->returnValue( function() use( $semanticData ) { return $semanticData; } ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$instance = new SingleValueConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_single_value_constraint_ThrowsException() {

		$constraint = [
			'single_value_constraint' => true
		];

		$instance = new SingleValueConstraint();

		$this->setExpectedException( '\RuntimeException' );
		$instance->checkConstraint( $constraint, 'Foo' );
	}

	public function checkConstraintError( $error, $expectedErrMsg ) {

		if ( strpos( $error->__toString(), $expectedErrMsg ) !== false ) {
			return true;
		}

		return false;
	}

}
