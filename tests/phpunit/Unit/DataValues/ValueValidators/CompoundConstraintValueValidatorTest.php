<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\ValueValidators\CompoundConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CompoundConstraintValueValidatorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueValidators\CompoundConstraintValueValidator',
			new CompoundConstraintValueValidator()
		);
	}

	public function testHasConstraintViolation() {

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$constraintValueValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $this->equalTo( 'Foo' ) );

		$constraintValueValidator->expects( $this->once() )
			->method( 'hasConstraintViolation' )
			->will( $this->returnValue( true ) );

		$instance = new CompoundConstraintValueValidator();
		$instance->registerConstraintValueValidator( $constraintValueValidator );

		$instance->validate( 'Foo' );

		$this->assertTrue(
			$instance->hasConstraintViolation()
		);
	}

	public function testMissingConstraintValueValidatorRegThrowsException() {

		$instance = new CompoundConstraintValueValidator();

		$this->setExpectedException( '\RuntimeException' );
		$instance->validate( 'Foo' );
	}

}
