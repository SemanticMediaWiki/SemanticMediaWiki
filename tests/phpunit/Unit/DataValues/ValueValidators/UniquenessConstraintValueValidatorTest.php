<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\DataItemFactory;
use SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValueValidatorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $uniqueValueConstraint;
	private $propertySpecificationLookup;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->uniqueValueConstraint = $this->getMockBuilder( '\SMW\Constraint\Constraints\UniqueValueConstraint' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
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

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$this->uniqueValueConstraint->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' );

		$this->uniqueValueConstraint->expects( $this->once() )
			->method( 'hasViolation' )
			->will( $this->returnValue( false ) );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraint' )
			->will( $this->returnValue( true ) );

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

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$this->uniqueValueConstraint->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' );

		$this->uniqueValueConstraint->expects( $this->once() )
			->method( 'hasViolation' )
			->will( $this->returnValue( true ) );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraint' )
			->will( $this->returnValue( true ) );

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
