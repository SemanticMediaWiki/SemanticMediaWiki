<?php

namespace SMW\Tests\Deserializers\DVDescriptionDeserializer;

use SMW\Deserializers\DVDescriptionDeserializer\TimeValueDescriptionDeserializer;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializer\TimeValueDescriptionDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class TimeValueDescriptionDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\TimeValueDescriptionDeserializer',
			new TimeValueDescriptionDeserializer()
		);
	}

	public function testIsDeserializerForTimeValue() {

		$dataValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new TimeValueDescriptionDeserializer();

		$this->assertTrue(
			$instance->isDeserializerFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testDeserialize( $value, $decription ) {

		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$timeValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$timeValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( new \SMWDITime( 1, '1970' ) ) );

		$timeValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( new \SMW\DIProperty( 'Foo' ) ) );

		$instance = new TimeValueDescriptionDeserializer();
		$instance->setDataValue( $timeValue );

		$this->assertInstanceOf(
			$decription,
			$instance->deserialize( $value )
		);
	}

	public function testInvalidTimeValueReturnsThingDescription() {

		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$timeValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$instance = new TimeValueDescriptionDeserializer();
		$instance->setDataValue( $timeValue );

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->deserialize( 'Foo' )
		);
	}

	public function testNonStringThrowsException() {

		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TimeValueDescriptionDeserializer();
		$instance->setDataValue( $timeValue );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->deserialize( array() );
	}

	public function valueProvider() {

		$provider[] = array(
			'Jan 1970',
			'\SMW\Query\Language\ValueDescription'
		);

		$provider[] = array(
			'~Jan 1970',
			'\SMW\Query\Language\Conjunction'
		);

		$provider[] = array(
			'!~Jan 1970',
			'\SMW\Query\Language\Disjunction'
		);

		return $provider;
	}

}
