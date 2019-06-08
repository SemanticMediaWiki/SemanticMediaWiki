<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\MandatoryPropertiesConstraint;
use SMW\Tests\PHPUnitCompat;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Constraint\Constraints\MandatoryPropertiesConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MandatoryPropertiesConstraintTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp() {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MandatoryPropertiesConstraint::class,
			new MandatoryPropertiesConstraint()
		);
	}

	public function testGetType() {

		$instance = new MandatoryPropertiesConstraint();

		$this->assertEquals(
			MandatoryPropertiesConstraint::TYPE_INSTANT,
			$instance->getType()
		);
	}

	public function testHasViolation() {

		$instance = new MandatoryPropertiesConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_mandatory_properties() {

		$constraint = [
			'mandatory_properties' => [ 'Foo' ]
		];

		$expectedErrMsg = 'smw-constraint-violation-class-mandatory-properties-constraint';

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIProperty( 'Foobar' ) ] ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'addError', 'getCallable' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getCallable' )
			->will( $this->returnValue( function() use( $semanticData ) { return $semanticData; } ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$instance = new MandatoryPropertiesConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_mandatory_properties_ThrowsException() {

		$constraint = [
			'mandatory_properties' => true
		];

		$instance = new MandatoryPropertiesConstraint();

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
