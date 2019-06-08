<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\MustExistsConstraint;
use SMW\Tests\PHPUnitCompat;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Constraint\Constraints\MustExistsConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MustExistsConstraintTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp() {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			MustExistsConstraint::class,
			new MustExistsConstraint()
		);
	}

	public function testGetType() {

		$instance = new MustExistsConstraint();

		$this->assertEquals(
			MustExistsConstraint::TYPE_INSTANT,
			$instance->getType()
		);
	}

	public function testHasViolation() {

		$instance = new MustExistsConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_must_exists() {

		$constraint = [
			'must_exists' => true
		];

		$expectedErrMsg = 'smw-datavalue-constraint-violation-must-exists';

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( \SMWDataItem::TYPE_WIKIPAGE ) );

		$dataItem->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'addError' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$instance = new MustExistsConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_must_exists_ThrowsException() {

		$constraint = [
			'must_exists' => true
		];

		$instance = new MustExistsConstraint();

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
