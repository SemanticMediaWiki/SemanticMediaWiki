<?php

namespace SMW\Tests\Unit\DataValues\ValueValidators;

use PHPUnit\Framework\TestCase;
use SMW\Constraint\Constraints\UniqueValueConstraint;
use SMW\DataItemFactory;
use SMW\DataValues\DataValue;
use SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator;
use SMW\Property\SpecificationLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValueValidatorTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $uniqueValueConstraint;
	private $propertySpecificationLookup;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->uniqueValueConstraint = $this->getMockBuilder( UniqueValueConstraint::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UniquenessConstraintValueValidator::class,
			new UniquenessConstraintValueValidator( $this->uniqueValueConstraint, $this->propertySpecificationLookup )
		);
	}

	public function testValidate_HasNoConstraintViolation() {
		$property = $this->dataItemFactory->newDIProperty( __METHOD__ );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $property );

		$this->uniqueValueConstraint->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' );

		$this->uniqueValueConstraint->expects( $this->once() )
			->method( 'hasViolation' )
			->willReturn( false );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraint' )
			->willReturn( true );

		$dataValue->setOption(
			'smwgDVFeatures',
			SMW_DV_PVUC
		);

		$instance = new UniquenessConstraintValueValidator(
			$this->uniqueValueConstraint,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testValidate_HasConstraintViolation() {
		$property = $this->dataItemFactory->newDIProperty( __METHOD__ );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $property );

		$this->uniqueValueConstraint->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' );

		$this->uniqueValueConstraint->expects( $this->once() )
			->method( 'hasViolation' )
			->willReturn( true );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraint' )
			->willReturn( true );

		$dataValue->setOption(
			'smwgDVFeatures',
			SMW_DV_PVUC
		);

		$instance = new UniquenessConstraintValueValidator(
			$this->uniqueValueConstraint,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertTrue(
			$instance->hasConstraintViolation()
		);
	}

}
