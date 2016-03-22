<?php

namespace SMW\Tests\DataValues;

use SMW\Tests\TestEnvironment;
use SMW\DataValues\UniquenessConstraintValue;
use SMW\Options;
use SMW\DataItemFactory;

/**
 * @covers \SMW\DataValues\UniquenessConstraintValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValueTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\DataValues\UniquenessConstraintValue',
			new UniquenessConstraintValue()
		);
	}

	public function testErrorForMissingFeatureSetting() {

		$instance = new UniquenessConstraintValue();

		$instance->setOptions(
			new Options( array( 'smwgDVFeatures' => '' ) )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testErrorForInvalidBoolean() {

		$instance = new UniquenessConstraintValue();

		$instance->setOptions(
			new Options( array( 'smwgDVFeatures' => SMW_DV_PVUC ) )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testCheckUniquenessConstraintByUsingAMockedQueryEngine() {

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

		$dataValue->setOptions(
			new Options( array( 'smwgDVFeatures' => SMW_DV_PVUC ) )
		);

		$instance = new UniquenessConstraintValue(
			'',
			$cachedPropertyValuesPrefetcher
		);

		$instance->doCheckUniquenessConstraintFor( $dataValue );
	}

}
