<?php

namespace SMW\Tests\Deserializers\DVDescriptionDeserializer;

use SMW\Deserializers\DVDescriptionDeserializer\RecordValueDescriptionDeserializer;
use SMW\DIProperty;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializer\RecordValueDescriptionDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class RecordValueDescriptionDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\RecordValueDescriptionDeserializer',
			new RecordValueDescriptionDeserializer()
		);
	}

	public function testIsDeserializerForTimeValue() {

		$dataValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new RecordValueDescriptionDeserializer();

		$this->assertTrue(
			$instance->isDeserializerFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testDeserialize( $value, $propertyDataItems, $decription ) {

		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$recordValue->expects( $this->any() )
			->method( 'getValuesFromString' )
			->with( $this->stringContains( $value ) )
			->will( $this->returnCallback( function( $value ) {
				 return explode(';', $value );
			} ) );

		$recordValue->expects( $this->any() )
			->method( 'getPropertyDataItems' )
			->will( $this->returnValue( $propertyDataItems ) );

		$instance = new RecordValueDescriptionDeserializer();
		$instance->setDataValue( $recordValue );

		$this->assertInstanceOf(
			$decription,
			$instance->deserialize( $value )
		);
	}

	public function testInvalidRecordValueReturnsThingDescription() {

		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$recordValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$recordValue->expects( $this->any() )
			->method( 'getPropertyDataItems' )
			->will( $this->returnValue( array() ) );

		$instance = new RecordValueDescriptionDeserializer();
		$instance->setDataValue( $recordValue );

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->deserialize( 'Foo' )
		);
	}

	public function testNonStringThrowsException() {

		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RecordValueDescriptionDeserializer();
		$instance->setDataValue( $recordValue );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->deserialize( array() );
	}

	public function valueProvider() {

		$provider[] = array(
			'Jan;1970',
			array( new DIProperty( 'Foo' ) ),
			'\SMW\Query\Language\SomeProperty'
		);

		$provider[] = array(
			'Jan;1970',
			array( new DIProperty( 'Foo' ), new DIProperty( 'Bar' ) ),
			'\SMW\Query\Language\Conjunction'
		);

		$provider[] = array(
			'?',
			array( new DIProperty( 'Foo' ), new DIProperty( 'Bar' ) ),
			'\SMW\Query\Language\ThingDescription'
		);

		$provider[] = array(
			'',
			array(),
			'\SMW\Query\Language\ThingDescription'
		);

		return $provider;
	}

}
