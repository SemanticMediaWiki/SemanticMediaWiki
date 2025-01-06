<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\Constraint\Constraints\ShapeConstraint;
use SMW\DataItemFactory;
use SMW\Tests\PHPUnitCompat;
use SMWDataValue;

/**
 * @covers \SMW\Constraint\Constraints\ShapeConstraint
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ShapeConstraintTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ShapeConstraint::class,
			new ShapeConstraint()
		);
	}

	public function testGetType() {
		$instance = new ShapeConstraint();

		$this->assertEquals(
			ShapeConstraint::TYPE_INSTANT,
			$instance->getType()
		);
	}

	public function testHasViolation() {
		$instance = new ShapeConstraint();

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_shape_constraint_missing_property() {
		$constraint = [
			'shape_constraint' => [ [ 'property' => 'Foo' ] ]
		];

		$expectedErrMsg = 'smw-constraint-violation-class-shape-constraint-missing-property';

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasProperty' )
			->with( 'Foo' )
			->willReturn( false );

		$dataValue = $this->createMock( SMWDataValue::class );
		$dataValue->expects( $this->any() )
			->method( 'getWikiValue' )
			->willReturn( 'foo' );

		$dataValue->expects( $this->once() )
			->method( 'getCallable' )
			->willReturn( static function () use( $semanticData ) { return $semanticData;
			} );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function ( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$instance = new ShapeConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_shape_constraint_wrong_type() {
		$constraint = [
			'shape_constraint' => [ [ 'property' => 'Foo', 'property_type' => 'Text' ] ]
		];

		$expectedErrMsg = 'smw-constraint-violation-class-shape-constraint-wrong-type';

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasProperty' )
			->with( 'Foo' )
			->willReturn( true );

		$dataValue = $this->createMock( SMWDataValue::class );
		$dataValue->expects( $this->any() )
			->method( 'getWikiValue' )
			->willReturn( 'foo' );

		$dataValue->expects( $this->once() )
			->method( 'getCallable' )
			->willReturn( static function () use( $semanticData ) { return $semanticData;
			} );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function ( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$instance = new ShapeConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_shape_constraint_invalid_max_cardinality() {
		$constraint = [
			'shape_constraint' => [ [ 'property' => 'Foo', 'max_cardinality' => 1 ] ]
		];

		$expectedErrMsg = 'smw-constraint-violation-class-shape-constraint-invalid-max-cardinality';

		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasProperty' )
			->with( 'Foo' )
			->willReturn( true );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ $dataItem, $dataItem ] );

		$dataValue = $this->createMock( SMWDataValue::class );
		$dataValue->expects( $this->any() )
			->method( 'getWikiValue' )
			->willReturn( 'foo' );

		$dataValue->expects( $this->once() )
			->method( 'getCallable' )
			->willReturn( static function () use( $semanticData ) { return $semanticData;
			} );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function ( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$instance = new ShapeConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_shape_constraint_invalid_min_textlength() {
		$constraint = [
			'shape_constraint' => [ [ 'property' => 'Foo', 'min_textlength' => 100 ] ]
		];

		$expectedErrMsg = 'smw-constraint-violation-class-shape-constraint-invalid-min-length';

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem->expects( $this->atLeastOnce() )
			->method( 'getString' )
			->willReturn( 'Bar' );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasProperty' )
			->with( 'Foo' )
			->willReturn( true );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ $dataItem ] );

		$dataValue = $this->createMock( SMWDataValue::class );
		$dataValue->expects( $this->any() )
			->method( 'getWikiValue' )
			->willReturn( 'foo' );

		$dataValue->expects( $this->once() )
			->method( 'getCallable' )
			->willReturn( static function () use( $semanticData ) { return $semanticData;
			} );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $this->callback( function ( $error ) use ( $expectedErrMsg ) {
				return $this->checkConstraintError( $error, $expectedErrMsg );
			} ) );

		$instance = new ShapeConstraint();

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

	public function testCheckConstraint_mandatory_properties_ThrowsException() {
		$constraint = [
			'mandatory_properties' => true
		];

		$instance = new ShapeConstraint();

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
