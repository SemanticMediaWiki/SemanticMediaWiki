<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\AllowsPatternValue;
use SMW\Tests\TestEnvironment;
use SMW\DataItemFactory;
use SMW\Options;

/**
 * @covers \SMW\DataValues\AllowsPatternValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	//	$this->testEnvironment->resetPoolCacheFor( 'pvap.no.pattern.cache' );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\AllowsPatternValue',
			new AllowsPatternValue()
		);
	}

	public function testHasErrorForMissingValue() {

		$instance = new AllowsPatternValue();

		$instance->setOptions( new Options(
			array( 'smwgDVFeatures' => SMW_DV_PVAP )
		) );

		$instance->setUserValue( '' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasErrorForNonMatchingContent() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|Bar\n" ) );

		$instance = new AllowsPatternValue();

		$instance->setOptions( new Options(
			array( 'smwgDVFeatures' => SMW_DV_PVAP )
		) );

		$instance->setUserValue( 'NotMatchable' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasNoErrorOnMatchableContent() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|Bar\n" ) );

		$instance = new AllowsPatternValue();

		$instance->setOptions( new Options(
			array( 'smwgDVFeatures' => SMW_DV_PVAP )
		) );

		$instance->setUserValue( 'Foo' );

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testErrorOnNotEnabledFeatureWhenUserValueIsNotEmpty() {

		$instance = new AllowsPatternValue();

		$instance->setUserValue( 'abc/e' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasAllowedPatternForValidDataValueWithMatchablePattern() {

		$property = $this->dataItemFactory->newDIProperty( 'Has allowed pattern' );

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|^(Bar|Foo bar)$/e\n" ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedPatternFor' )
			->will( $this->returnValue( 'Foo' ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getProperty', 'getDataItem', 'getTypeID' ) )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Foo bar' ) ) );

		$instance = new AllowsPatternValue();

		$dataValue->setOptions( new Options(
			array( 'smwgDVFeatures' => SMW_DV_PVAP )
		) );

		$this->assertTrue(
			$instance->doCheckAllowedPatternFor( $dataValue )
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

}
