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
	private $blobStore;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->any() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

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
			'\SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator',
			new UniquenessConstraintValueValidator()
		);
	}

	public function testCanNotValidateOnNullProperty() {

		$cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getProperty', 'getDataItem', 'getContextPage' ) )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( null ) );

		$dataValue->expects( $this->never() )
			->method( 'getContextPage' );

		$dataValue->expects( $this->never() )
			->method( 'getDataItem' );

		$dataValue->setOption(
			'smwgDVFeatures',
			SMW_DV_PVUC
		);

		$instance = new UniquenessConstraintValueValidator(
			$cachedPropertyValuesPrefetcher
		);

		$instance->validate( $dataValue );
	}

	public function testValidateUsingAMockedQueryEngine() {

		$property = $this->dataItemFactory->newDIProperty( 'ValidAllowedValue' );

		$cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $this->blobStore ) );

		$cachedPropertyValuesPrefetcher->expects( $this->atLeastOnce() )
			->method( 'queryPropertyValuesFor' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIWikiPage( 'UV', NS_MAIN ) ) ) );

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'hasUniquenessConstraintFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getProperty', 'getDataItem', 'getContextPage' ) )
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
			$cachedPropertyValuesPrefetcher
		);

		$instance->validate( $dataValue );
	}

}
