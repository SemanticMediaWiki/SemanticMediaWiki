<?php

namespace SMW\Tests\Deserializers\DVDescriptionDeserializer;

use SMW\DataValues\MonolingualTextValue;
use SMW\Deserializers\DVDescriptionDeserializer\MonolingualTextValueDescriptionDeserializer;
use SMW\Options;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializer\MonolingualTextValueDescriptionDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueDescriptionDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\MonolingualTextValueDescriptionDeserializer',
			new MonolingualTextValueDescriptionDeserializer()
		);
	}

	public function testIsDeserializerForTimeValue() {

		$dataValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new MonolingualTextValueDescriptionDeserializer();

		$this->assertTrue(
			$instance->isDeserializerFor( $dataValue )
		);
	}

	public function testNonStringThrowsException() {

		$recordValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MonolingualTextValueDescriptionDeserializer();
		$instance->setDataValue( $recordValue );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->deserialize( array() );
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testDeserialize( $value, $decription, $queryString, $dvFeatures ) {

		$monolingualTextValue = new MonolingualTextValue();

		$monolingualTextValue->setOptions(
			new Options( array( 'smwgDVFeatures' => $dvFeatures ) )
		);

		$instance = new MonolingualTextValueDescriptionDeserializer();
		$instance->setDataValue( $monolingualTextValue );

		$this->assertInstanceOf(
			$decription,
			$instance->deserialize( $value )
		);

		$this->assertEquals(
			$queryString,
			$instance->deserialize( $value )->getQueryString()
		);
	}

	public function valueProvider() {

		#0
		$provider[] = array(
			'Jan;1970',
			'\SMW\Query\Language\Conjunction',
			'[[Text::Jan;1970]] [[Language code::+]]',
			SMW_DV_MLTV_LCODE
		);

		#1
		$provider[] = array(
			'Jan;1970',
			'\SMW\Query\Language\SomeProperty',
			'[[Text::Jan;1970]]',
			SMW_DV_NONE
		);

		#2
		$provider[] = array(
			'Jan@en',
			'\SMW\Query\Language\Conjunction',
			'[[Text::Jan]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		);

		#3
		$provider[] = array(
			'@en',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		);

		#4
		$provider[] = array(
			'@EN',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::en]]',
			SMW_DV_MLTV_LCODE
		);

		#5
		$provider[] = array(
			'@~zh*',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::~zh*]]',
			SMW_DV_MLTV_LCODE
		);

		#6
		$provider[] = array(
			'?',
			'\SMW\Query\Language\Conjunction',
			'[[Text::+]] [[Language code::+]]',
			SMW_DV_MLTV_LCODE
		);

		#7
		$provider[] = array(
			'?',
			'\SMW\Query\Language\SomeProperty',
			'[[Text::+]]',
			SMW_DV_NONE
		);

		#8
		$provider[] = array(
			'',
			'\SMW\Query\Language\ThingDescription',
			'',
			SMW_DV_MLTV_LCODE
		);

		return $provider;
	}

}
