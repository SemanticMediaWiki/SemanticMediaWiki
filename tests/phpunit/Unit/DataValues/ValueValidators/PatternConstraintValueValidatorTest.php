<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\Tests\TestEnvironment;
use SMW\DataValues\ValueValidators\PatternConstraintValueValidator;
use SMW\Options;
use SMW\DataItemFactory;

/**
 * @covers \SMW\DataValues\ValueValidators\PatternConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PatternConstraintValueValidatorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $mediaWikiNsContentReader;

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
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueValidators\PatternConstraintValueValidator',
			new PatternConstraintValueValidator()
		);
	}

	public function testValidateUsingAMockedQueryEngine() {

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

		$instance = new PatternConstraintValueValidator();

		$dataValue->setOptions( new Options(
			array( 'smwgDVFeatures' => SMW_DV_PVAP )
		) );

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

}
