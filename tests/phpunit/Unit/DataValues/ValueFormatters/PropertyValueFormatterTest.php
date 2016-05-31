<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMWPropertyValue as PropertyValue;
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

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertyLabelFinder = $this->getMockBuilder( '\SMW\PropertyLabelFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertyLabelFinder', $this->propertyLabelFinder );
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

	/**
	 * @dataProvider propertyValueProvider
	 */
	public function testFormat( $property, $type, $linker, $expected ) {

		$propertyValue = new PropertyValue();
		$propertyValue->setDataItem( $property );

		$propertyValue->setOption( PropertyValue::OPT_CONTENT_LANGUAGE, 'en' );
		$propertyValue->setOption( PropertyValue::OPT_USER_LANGUAGE, 'en' );

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
			->method( 'findPropertyListByLabelAndLanguageCode' )
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

		$propertyValue->setUserValue( $property );

		$instance = new PropertyValueFormatter( $propertyValue );
		$expected = $this->testEnvironment->getLocalizedTextByNamespace( SMW_NS_PROPERTY, $expected );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);

		\SMW\PropertyRegistry::clear();
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

		$dataItemFactory = new DataItemFactory();
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

		return $provider;
	}

}
