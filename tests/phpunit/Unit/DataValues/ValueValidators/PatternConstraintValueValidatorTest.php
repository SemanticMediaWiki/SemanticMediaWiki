<?php

namespace SMW\Tests\Unit\DataValues\ValueValidators;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\DataValue;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\ValueValidators\PatternConstraintValueValidator;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\Property\SpecificationLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueValidators\PatternConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PatternConstraintValueValidatorTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $mediaWikiNsContentReader;
	private $allowsPatternValueParser;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$this->allowsPatternValueParser = new AllowsPatternValueParser(
			$this->mediaWikiNsContentReader
		);

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PatternConstraintValueValidator::class,
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
			->willReturn( $allowedPattern );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedPatternBy' )
			->willReturn( 'Foo' );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getProperty', 'getDataItem', 'getTypeID' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $property );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( $testString ) );

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

		# 1 valid
		$provider[] = [
			" \nFoo|(ev\d{7}\d{4})|((tt|nm|ch|co|ev)\d{7})\n",
			'tt0042876',
			false
		];

		# 2 uses '/\'
		$provider[] = [
			" \nFoo|(ev\d{7}/\d{4})|((tt|nm|ch|co|ev)\d{7})\n",
			'tt0042876',
			false
		];

		# 3 "Compilation failed: missing )", suppress error
		$provider[] = [
			" \nFoo|(ev\d{7}\d{4})|((tt|nm|ch|co|ev)\d{7}\n",
			'Foo',
			true
		];

		# 4
		$provider[] = [
			" \nFoo|\d{8}\n",
			'00564222',
			false
		];

		# 5
		$provider[] = [
			" \nFoo|/\d{8}\n",
			'00564222',
			false
		];

		return $provider;
	}

}
