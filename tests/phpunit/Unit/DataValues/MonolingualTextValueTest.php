<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;

/**
 * @covers \SMW\DataValues\MonolingualTextValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueTest extends \PHPUnit_Framework_TestCase {

	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->will( $this->returnValue( new MonolingualTextValueParser() ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\MonolingualTextValue',
			new MonolingualTextValue()
		);
	}

	public function testErrorForMissingLanguageCode() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setOption( 'smwgDVFeatures', SMW_DV_MLTV_LCODE );
		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testNoErrorForMissingLanguageCodeWhenFeatureIsDisabled() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setOption( 'smwgDVFeatures', false );
		$instance->setUserValue( 'Foo' );

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testErrorForInvalidLanguageCode() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@foobar' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testValidParsableUserValue() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@en' );

		$this->assertEmpty(
			$instance->getErrors()
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getDataItem()
		);

		foreach ( $instance->getDataItems() as $dataItem ) {
			$this->assertInstanceOf(
				'\SMWDIBlob',
				$dataItem
			);
		}

		$this->assertEquals(
			'Foo',
			$instance->getTextValueByLanguageCode( 'en' )->getDataItem()->getString()
		);
	}

	public function testTryToGetTextValueByLanguageForUnrecognizedLanguagCode() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@en' );

		$this->assertNull(
			$instance->getTextValueByLanguageCode( 'bar' )
		);
	}

	public function testGetWikiValueForValidMonolingualTextValue() {

		$instance = new MonolingualTextValue();

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $monolingualTextValueFormatter ) );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@en' );

		$this->assertEquals(
			'Foo@en',
			$instance->getWikiValue()
		);
	}

	public function testGetWikiValueForInvalidMonolingualTextValue() {

		$instance = new MonolingualTextValue();

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $monolingualTextValueFormatter ) );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@foobar' );

		$this->assertContains(
			'class="smw-highlighter" data-type="4"',
			$instance->getWikiValue()
		);
	}

	public function testGetProperties() {

		$instance = new MonolingualTextValue();
		$properties = $instance->getPropertyDataItems();

		$this->assertEquals(
			'_TEXT',
			$properties[0]->getKey()
		);

		$this->assertEquals(
			'_LCODE',
			$properties[1]->getKey()
		);
	}

	public function testToArray() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@en' );

		$this->assertEquals(
			[
				'_TEXT'  => 'Foo',
				'_LCODE' => 'en'
			],
			$instance->toArray()
		);
	}

	public function testToString() {

		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@en' );

		$this->assertSame(
			'Foo@en',
			$instance->toString()
		);
	}

	public function testGetTextWithLanguageTag() {

		$instance = new MonolingualTextValue();

		$this->assertSame(
			'foo@zh-Hans',
			$instance->getTextWithLanguageTag( 'foo', 'zh-hans' )
		);
	}

}
