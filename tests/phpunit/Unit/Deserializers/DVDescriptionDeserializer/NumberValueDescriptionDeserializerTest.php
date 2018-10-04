<?php

namespace SMW\Tests\Deserializers\DVDescriptionDeserializer;

use SMW\Deserializers\DVDescriptionDeserializer\NumberValueDescriptionDeserializer;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializer\NumberValueDescriptionDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NumberValueDescriptionDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			NumberValueDescriptionDeserializer::class,
			new NumberValueDescriptionDeserializer()
		);
	}

	public function testIsDeserializerForNumberValue() {

		$dataValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new NumberValueDescriptionDeserializer();

		$this->assertTrue(
			$instance->isDeserializerFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testDeserialize( $value, $decription ) {

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$numberValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( new \SMWDINumber( 42 ) ) );

		$numberValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( new \SMW\DIProperty( 'Foo' ) ) );

		$instance = new NumberValueDescriptionDeserializer();
		$instance->setDataValue( $numberValue );

		$this->assertInstanceOf(
			$decription,
			$instance->deserialize( $value )
		);
	}

	public function testInvalidNumberValueReturnsThingDescription() {

		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$instance = new NumberValueDescriptionDeserializer();
		$instance->setDataValue( $numberValue );

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->deserialize( 'Foo' )
		);
	}

	public function valueProvider() {

		$provider[] = [
			'42',
			'\SMW\Query\Language\ValueDescription'
		];

		$provider[] = [
			'~42',
			'\SMW\Query\Language\Conjunction'
		];

		$provider[] = [
			'~*42*',
			'\SMW\Query\Language\Conjunction'
		];

		$provider[] = [
			'~-42',
			'\SMW\Query\Language\Conjunction'
		];

		return $provider;
	}

}
