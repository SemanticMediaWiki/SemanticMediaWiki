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
	private $propertySpecificationLookup;
	private $store;
	private $entityValueUniquenessConstraintChecker;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->entityValueUniquenessConstraintChecker = $this->getMockBuilder( '\SMW\SQLStore\EntityValueUniquenessConstraintChecker' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( $this->equalTo( 'EntityValueUniquenessConstraintChecker' ) )
			->will( $this->returnValue( $this->entityValueUniquenessConstraintChecker ) );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			UniquenessConstraintValueValidator::class,
			new UniquenessConstraintValueValidator( $this->store, $this->propertySpecificationLookup )
		);
	}

	public function testCanNotValidateOnNull() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getContextPage' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( null ) );

		$dataValue->expects( $this->never() )
			->method( 'getProperty' );

		$dataValue->expects( $this->never() )
			->method( 'getDataItem' );

		$dataValue->setOption(
			'smwgDVFeatures',
			SMW_DV_PVUC
		);

		$instance = new UniquenessConstraintValueValidator(
			$this->store,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );
	}

	public function testValidate_HasNoConstraintViolation() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

		$this->entityValueUniquenessConstraintChecker->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' )
			->will( $this->returnValue( [] ) );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraint' )
			->will( $this->returnValue( true ) );

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

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo' ) ) );

		$dataValue->setOption(
			'smwgDVFeatures',
			SMW_DV_PVUC
		);

		$instance = new UniquenessConstraintValueValidator(
			$this->store,
			$this->propertySpecificationLookup
		);

		$instance->clear();
		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testValidate_HasConstraintViolation() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue_2' );

		$this->entityValueUniquenessConstraintChecker->expects( $this->atLeastOnce() )
			->method( 'checkConstraint' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ] ) );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraint' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getContextPage', 'addErrorMsg' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'addErrorMsg' )
			->with( $this->equalTo( [ 'smw-datavalue-uniqueness-constraint-error', $property->getLabel(), '...', 'Foo' ] ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo' ) ) );

		$dataValue->setOption(
			'smwgDVFeatures',
			SMW_DV_PVUC
		);

		$instance = new UniquenessConstraintValueValidator(
			$this->store,
			$this->propertySpecificationLookup
		);

		$instance->validate( $dataValue );

		$this->assertTrue(
			$instance->hasConstraintViolation()
		);
	}

}
