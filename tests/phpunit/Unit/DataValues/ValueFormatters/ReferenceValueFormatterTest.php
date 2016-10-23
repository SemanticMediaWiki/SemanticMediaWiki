<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ReferenceValue;
use SMW\DataValues\ValueFormatters\ReferenceValueFormatter;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueFormatters\ReferenceValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ReferenceValueFormatterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\ReferenceValueFormatter',
			new ReferenceValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$referenceValue = $this->getMockBuilder( '\SMW\DataValues\ReferenceValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ReferenceValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $referenceValue )
		);
	}

	public function testToUseCaptionOutput() {

		$referenceValue = new ReferenceValue();
		$referenceValue->setCaption( 'ABC' );

		$instance = new ReferenceValueFormatter( $referenceValue );

		$this->assertEquals(
			'ABC',
			$instance->format( ReferenceValueFormatter::WIKI_SHORT )
		);

		$this->assertEquals(
			'ABC',
			$instance->format( ReferenceValueFormatter::HTML_SHORT )
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $suserValue, $type, $linker, $expected ) {

		$referenceValue = new ReferenceValue();

		$referenceValue->setFieldProperties( array(
			$this->dataItemFactory->newDIProperty( 'Foo' ),
			$this->dataItemFactory->newDIProperty( 'Date' ),
			$this->dataItemFactory->newDIProperty( 'URL' )
		) );

		$referenceValue->setOption( ReferenceValue::OPT_CONTENT_LANGUAGE, 'en' );
		$referenceValue->setOption( ReferenceValue::OPT_USER_LANGUAGE, 'en' );

		$referenceValue->setUserValue( $suserValue );

		$instance = new ReferenceValueFormatter( $referenceValue );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new ReferenceValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( ReferenceValueFormatter::VALUE );
	}

	public function stringValueProvider() {

		$provider[] = array(
			'abc;12;3',
			ReferenceValueFormatter::VALUE,
			null,
			'Abc'
		);

		$provider[] = array(
			'abc;12;3',
			ReferenceValueFormatter::VALUE,
			false,
			'Abc;12;3'
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::WIKI_SHORT,
			null,
			'Abc'
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::WIKI_SHORT,
			false,
			array(
				'Abc',
				'class="smw-reference smw-reference-indicator smw-highlighter smwttinline"',
				'data-title="Reference"',
				'title="Date: ?, URL: ?"'
			)
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::HTML_SHORT,
			null,
			'Abc'
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::HTML_SHORT,
			false,
			array(
				'Abc',
				'class="smw-reference smw-reference-indicator smw-highlighter smwttinline"',
				'data-title="Reference"',
				'title="Date: ?, URL: ?"'
			)
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::WIKI_LONG,
			null,
			'Abc'
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::WIKI_LONG,
			false,
			array(
				'Abc',
				'class="smw-reference smw-reference-indicator smw-highlighter smwttinline"',
				'data-title="Reference"',
				'title="Date: ?, URL: ?"'
			)
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::HTML_LONG,
			null,
			'Abc'
		);

		$provider[] = array(
			'abc',
			ReferenceValueFormatter::HTML_LONG,
			false,
			array(
				'Abc',
				'class="smw-reference smw-reference-indicator smw-highlighter smwttinline"',
				'data-title="Reference"',
				'title="Date: ?, URL: ?"'
			)
		);

		// Notice: Undefined variable: dataValue in
		$provider[] = array(
			'?;12;3',
			ReferenceValueFormatter::VALUE,
			null,
			''
		);

		return $provider;
	}

}
