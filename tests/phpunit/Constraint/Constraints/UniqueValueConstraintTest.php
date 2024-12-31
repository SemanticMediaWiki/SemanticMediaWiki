<?php

namespace SMW\Tests\Constraint\Constraints;

use SMW\DataItemFactory;
use SMW\Constraint\Constraints\UniqueValueConstraint;
use SMW\Constraint\ConstraintError;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Constraint\Constraints\UniqueValueConstraint
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UniqueValueConstraintTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $store;
	private $entityUniquenessLookup;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->entityUniquenessLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\EntityUniquenessLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'EntityUniquenessLookup' )
			->willReturn( $this->entityUniquenessLookup );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UniqueValueConstraint::class,
			new UniqueValueConstraint( $this->store, $this->propertySpecificationLookup )
		);
	}

	public function testInvalidValueThrowsException() {
		$instance = new UniqueValueConstraint(
			$this->store,
			$this->propertySpecificationLookup
		);

		$this->expectException( '\RuntimeException' );
		$instance->checkConstraint( [], 'Foo' );
	}

	public function testCanNotValidateOnNull() {
		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$instance = new UniqueValueConstraint(
			$this->store,
			$this->propertySpecificationLookup
		);

		$instance->checkConstraint( [], $dataValue );

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testValidate_HasNoConstraintViolation() {
		$property = $this->dataItemFactory->newDIProperty( __METHOD__ );

		$this->entityUniquenessLookup->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' )
			->willReturn( [] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Foo' ) );

		$instance = new UniqueValueConstraint(
			$this->store,
			$this->propertySpecificationLookup
		);

		$constraint = [
			'unique_value_constraint' => true
		];

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertFalse(
			$instance->hasViolation()
		);
	}

	public function testValidate_HasConstraintViolation() {
		$property = $this->dataItemFactory->newDIProperty( __METHOD__ );

		$error = new ConstraintError(
			[ 'smw-constraint-violation-uniqueness', $property->getLabel(), '...', 'Foo' ]
		);

		$this->entityUniquenessLookup->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' )
			->willReturn( [ $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ] );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getProperty', 'getDataItem', 'getContextPage', 'addError' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addError' )
			->with( $error );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Foo' ) );

		$instance = new UniqueValueConstraint(
			$this->store,
			$this->propertySpecificationLookup
		);

		$constraint = [
			'unique_value_constraint' => true
		];

		$instance->checkConstraint( $constraint, $dataValue );

		$this->assertTrue(
			$instance->hasViolation()
		);
	}

}
