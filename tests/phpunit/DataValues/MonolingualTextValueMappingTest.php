<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Services\DataValueServiceFactory;

// phpcs:disable MediaWiki.Commenting.ClassAnnotations.UnrecognizedAnnotation

/**
 * @covers \SMW\DataValues\MonolingualTextValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @reviewer thomas-topway-it
 */
class MonolingualTextValueMappingTest extends TestCase {

	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $constraintValueValidator );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( new MonolingualTextValueParser() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MonolingualTextValue::class,
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

		$instance->setUserValue( 'Foo@de-formal' );

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
			$instance->getTextValueByLanguageCode( 'de-formal' )->getDataItem()->getString()
		);
	}

	public function testTryToGetTextValueByLanguageForUnrecognizedLanguagCode() {
		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@de-formal' );

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
			->willReturn( $monolingualTextValueFormatter );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@de-formal' );

		$this->assertEquals(
			'Foo@de-formal',
			$instance->getWikiValue()
		);
	}

	public function testGetWikiValueForInvalidMonolingualTextValue() {
		$instance = new MonolingualTextValue();

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->willReturn( $monolingualTextValueFormatter );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@foobar' );

		$this->assertStringContainsString(
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

		$instance->setUserValue( 'Foo@de-formal' );

		$this->assertEquals(
			[
				'_TEXT'  => 'Foo',
				'_LCODE' => 'de-formal'
			],
			$instance->toArray()
		);
	}

	public function testToString() {
		$instance = new MonolingualTextValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'Foo@de-formal' );

		$this->assertSame(
			'Foo@de-formal',
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
