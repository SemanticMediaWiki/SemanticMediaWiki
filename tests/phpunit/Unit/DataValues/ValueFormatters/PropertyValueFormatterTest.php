<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMWPropertyValue as PropertyValue;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ValueFormatters\PropertyValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueFormatterTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $propertyLabelFinder;
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertyLabelFinder', $this->propertyLabelFinder );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->will( $this->returnValue( new PropertyValueParser() ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\PropertyValueFormatter',
			new PropertyValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$propertyValue = $this->getMockBuilder( '\SMWPropertyValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $propertyValue )
		);
	}

	public function testFormatWithInvalidFormat() {

		$propertyValue = new PropertyValue();
		$propertyValue->setDataItem( $this->dataItemFactory->newDIProperty( 'Foo' ) );
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance = new PropertyValueFormatter( $propertyValue );

		$this->assertEquals(
			'',
			$instance->format( 'Foo' )
		);
	}

	public function testFormatWithCaptionOutput() {

		$propertyValue = new PropertyValue();
		$propertyValue->setDataItem( $this->dataItemFactory->newDIProperty( 'Foo' ) );
		$propertyValue->setCaption( 'ABC[<>]' );
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance = new PropertyValueFormatter( $propertyValue );

		$this->assertEquals(
			'ABC[<>]',
			$instance->format( PropertyValueFormatter::WIKI_SHORT )
		);

		$this->assertEquals(
			'ABC[&lt;&gt;]',
			$instance->format( PropertyValueFormatter::HTML_SHORT )
		);
	}

	public function testFormatWithCaptionOutputAndHighlighter() {

		$propertyValue = new PropertyValue();
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, false );

		$propertyValue->setDataItem( $this->dataItemFactory->newDIProperty( 'Foo' ) );
		$propertyValue->setCaption( 'ABC[<>]' );

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance = new PropertyValueFormatter( $propertyValue );

		$this->assertContains(
			'<span class="smwtext">ABC[<>]</span><div class="smwttcontent"></div>',
			$instance->format( PropertyValueFormatter::WIKI_SHORT )
		);

		$this->assertContains(
			'<span class="smwtext">ABC[&lt;&gt;]</span><div class="smwttcontent"></div>',
			$instance->format( PropertyValueFormatter::HTML_SHORT )
		);
	}

	/**
	 * @dataProvider propertyValueProvider
	 */
	public function testFormat( $property, $type, $linker, $expected ) {

		$propertyValue = new PropertyValue();
		$propertyValue->setDataItem( $property );

		$propertyValue->setOption( PropertyValue::OPT_CONTENT_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_USER_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance = new PropertyValueFormatter( $propertyValue );
		$expected = $this->testEnvironment->getLocalizedTextByNamespace( SMW_NS_PROPERTY, $expected );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	/**
	 * @dataProvider preferredLabelValueProvider
	 */
	public function testFormatWithPreferredLabel( $property, $preferredLabel, $type, $linker, $expected ) {

		// Ensures the mocked instance is injected and registered with the
		// PropertyRegistry instance
		\SMW\PropertyRegistry::clear();

		$this->propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyLabelFinder->expects( $this->any() )
			->method( 'findPropertyListFromLabelByLanguageCode' )
			->will( $this->returnValue( array() ) );

		$this->propertyLabelFinder->expects( $this->any() )
			->method( 'findPreferredPropertyLabelByLanguageCode' )
			->will( $this->returnValue( $preferredLabel ) );

		$this->propertyLabelFinder->expects( $this->any() )
			->method( 'searchPropertyIdByLabel' )
			->will( $this->returnValue( false ) );

		$this->testEnvironment->registerObject( 'PropertyLabelFinder', $this->propertyLabelFinder );

		$propertyValue = new PropertyValue();

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$propertyValue->setOption( 'smwgDVFeatures', SMW_DV_PROV_LHNT );
		$propertyValue->setOption( PropertyValue::OPT_CONTENT_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_USER_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

		$propertyValue->setUserValue( $property );

		$instance = new PropertyValueFormatter( $propertyValue );
		$expected = $this->testEnvironment->getLocalizedTextByNamespace( SMW_NS_PROPERTY, $expected );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);

		\SMW\PropertyRegistry::clear();
	}

	/**
	 * @dataProvider preferredLabelAndCaptionValueProvider
	 */
	public function testFormatWithPreferredLabelAndCaption( $property, $caption, $preferredLabel, $type, $linker, $expected ) {

		// Ensures the mocked instance is injected and registered with the
		// PropertyRegistry instance
		\SMW\PropertyRegistry::clear();

		$this->propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyLabelFinder->expects( $this->any() )
			->method( 'findPropertyListFromLabelByLanguageCode' )
			->will( $this->returnValue( array() ) );

		$this->propertyLabelFinder->expects( $this->any() )
			->method( 'findPreferredPropertyLabelByLanguageCode' )
			->will( $this->returnValue( $preferredLabel ) );

		$this->propertyLabelFinder->expects( $this->any() )
			->method( 'searchPropertyIdByLabel' )
			->will( $this->returnValue( false ) );

		$this->testEnvironment->registerObject( 'PropertyLabelFinder', $this->propertyLabelFinder );

		$propertyValue = new PropertyValue();

		$propertyValue->setOption( 'smwgDVFeatures', SMW_DV_PROV_LHNT );
		$propertyValue->setOption( PropertyValue::OPT_CONTENT_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_USER_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$propertyValue->setUserValue( $property );
		$propertyValue->setCaption( $caption );

		$instance = new PropertyValueFormatter( $propertyValue );
		$expected = $this->testEnvironment->getLocalizedTextByNamespace( SMW_NS_PROPERTY, $expected );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);

		\SMW\PropertyRegistry::clear();
	}

	/**
	 * @dataProvider formattedLabelProvider
	 */
	public function testFormattedLabelLabel( $property, $linker, $expected ) {

		$propertyValue = new PropertyValue();

		$propertyValue->setOption( PropertyValue::OPT_CONTENT_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_USER_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_NO_HIGHLIGHT, true );

		$propertyValue->setDataItem( $property );

		$propertyValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance = new PropertyValueFormatter( $propertyValue );
		$expected = $this->testEnvironment->getLocalizedTextByNamespace( SMW_NS_PROPERTY, $expected );

		$this->assertEquals(
			$expected,
			$instance->format( PropertyValue::FORMAT_LABEL, $linker )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new PropertyValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( PropertyValueFormatter::VALUE );
	}

	public function propertyValueProvider() {

		$dataItemFactory = new DataItemFactory();

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			PropertyValueFormatter::VALUE,
			null,
			'Foo'
		);

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			PropertyValueFormatter::WIKI_SHORT,
			null,
			'Foo'
		);

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			PropertyValueFormatter::HTML_SHORT,
			null,
			'Foo'
		);

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			PropertyValueFormatter::WIKI_LONG,
			null,
			'Property:Foo'
		);

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			PropertyValueFormatter::HTML_LONG,
			null,
			'Property:Foo'
		);

		return $provider;
	}

	public function preferredLabelValueProvider() {

		$linker = 'some';

		$provider[] = array(
			'Foo',
			'Bar',
			PropertyValueFormatter::VALUE,
			null,
			'Bar'
		);

		$provider[] = array(
			'Foo',
			'Bar',
			PropertyValueFormatter::WIKI_SHORT,
			null,
			'Bar&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		$provider[] = array(
			'Foo',
			'Bar',
			PropertyValueFormatter::HTML_SHORT,
			null,
			'Bar&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		$provider[] = array(
			'Foo',
			'Bar',
			PropertyValueFormatter::WIKI_LONG,
			$linker,
			'[[:Property:Foo|Bar]]&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		$provider[] = array(
			'Foo',
			'Bar',
			PropertyValueFormatter::HTML_LONG,
			null,
			'Property:Foo&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		$provider[] = array(
			'Foo',
			'Bar with',
			PropertyValueFormatter::HTML_SHORT,
			null,
			'Bar with&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		return $provider;
	}

	public function preferredLabelAndCaptionValueProvider() {

		$linker = 'some';

		$provider[] = array(
			'Foo',
			false,
			'Bar',
			PropertyValueFormatter::VALUE,
			null,
			'Bar'
		);

		$provider[] = array(
			'Foo',
			false,
			'Bar',
			PropertyValueFormatter::HTML_SHORT,
			null,
			'Bar'
		);

		$provider[] = array(
			'Foo',
			'Bar with',
			'Bar with',
			PropertyValueFormatter::HTML_SHORT,
			null,
			'Bar with&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		$provider[] = array(
			'Foo',
			'Bar&nbsp;with',
			'Bar with',
			PropertyValueFormatter::HTML_SHORT,
			null,
			'Bar&nbsp;with&nbsp;<span title="Foo"><sup>ᵖ</sup></span>'
		);

		return $provider;
	}

	public function formattedLabelProvider() {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( \SMWDataItem::TYPE_PROPERTY ) );

		$property->expects( $this->any() )
			->method( 'getPreferredLabel' )
			->will( $this->returnValue( 'Bar' ) );

		$property->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( 'Foo' ) );

		$property->expects( $this->any() )
			->method( 'getCanonicalLabel' )
			->will( $this->returnValue( 'Foo' ) );

		$provider[] = array(
			$property,
			null,
			'&nbsp;<span style="font-size:small;">(Foo)</span>'
		);

		return $provider;
	}

}
