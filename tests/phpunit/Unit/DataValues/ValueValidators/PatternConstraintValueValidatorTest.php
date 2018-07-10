<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\DataItemFactory;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\ValueValidators\PatternConstraintValueValidator;
use SMW\Tests\TestEnvironment;

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
	private $allowsPatternValueParser;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$this->allowsPatternValueParser = new AllowsPatternValueParser(
			$this->mediaWikiNsContentReader
		);

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
			new PatternConstraintValueValidator( $this->allowsPatternValueParser )
		);
	}

	/**
	 * @dataProvider allowedPatternProvider
	 */
	public function testPatternUsingMockedDataValue( $allowedPattern, $testString, $expectedConstraintViolation ) {

		$property = $this->dataItemFactory->newDIProperty( 'Has allowed pattern' );

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $allowedPattern ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedPatternBy' )
			->will( $this->returnValue( 'Foo' ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( $testString ) ) );

		$instance = new PatternConstraintValueValidator(
			$this->allowsPatternValueParser
		);

		$dataValue->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->validate( $dataValue );

		$this->assertEquals(
			$expectedConstraintViolation,
			$instance->hasConstraintViolation()
		);
	}

	public function allowedPatternProvider() {

		$provider[] = [
			" \nFoo|^(Bar|Foo bar)$/e\n",
			'Foo bar',
			false
		];

		#1 valid
		$provider[] = [
			" \nFoo|(ev\d{7}\d{4})|((tt|nm|ch|co|ev)\d{7})\n",
			'tt0042876',
			false
		];

		#2 uses '/\'
		$provider[] = [
			" \nFoo|(ev\d{7}/\d{4})|((tt|nm|ch|co|ev)\d{7})\n",
			'tt0042876',
			false
		];

		#3 "Compilation failed: missing )", suppress error
		$provider[] = [
			" \nFoo|(ev\d{7}\d{4})|((tt|nm|ch|co|ev)\d{7}\n",
			'Foo',
			true
		];

		#4
		$provider[] = [
			" \nFoo|\d{8}\n",
			'00564222',
			false
		];

		#5
		$provider[] = [
			" \nFoo|/\d{8}\n",
			'00564222',
			false
		];

		return $provider;
	}

}
