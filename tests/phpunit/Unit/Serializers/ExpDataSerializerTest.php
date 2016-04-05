<?php

namespace SMW\Tests\Serializers;

use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Serializers\ExpDataSerializer;
use SMWDIBlob as DIBlob;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Serializers\ExpDataSerializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpDataSerializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructor() {

		$this->assertInstanceOf(
			'\SMW\Serializers\ExpDataSerializer',
			new ExpDataSerializer()
		);
	}

	public function testInvalidSerializerObjectThrowsException() {

		$instance = new ExpDataSerializer();

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->serialize( 'Foo' );
	}

	/**
	 * @dataProvider expDataProvider
	 */
	public function testSerialize( $data, $expected ) {

		$instance = new ExpDataSerializer();

		$this->assertEquals(
			$expected,
			$instance->serialize( $data )
		);
	}

	public function expDataProvider() {

		#0
		$expData = new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) );

		$provider[] = array(
			$expData,
			array(
				'subject' => array(
					'type' => 1,
					'uri' => 'Foo|Bar|Mo',
					'dataitem' => null
				),
				'data' => array(),
				'serializer' => 'SMW\Serializers\ExpDataSerializer',
				'version' => 0.1
			)
		);

		#1
		$expData = new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) );

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$provider[] = array(
			$expData,
			array(
				'subject' => array(
					'type' => 1,
					'uri' => 'Foo|Bar|Mo',
					'dataitem' => null
				),
				'data' => array(
					'LaLi' => array(
						 'property' => array(
						 	'type' => 1,
						 	'uri' => 'Li|La|Lu',
						 	'dataitem' => null
						 ),
						 'children' => array(
						 	array(
						 		'type' => 2,
						 		'lexical' => 'Foo',
						 		'datatype' => 'Bar',
						 		'lang' => '',
						 		'dataitem' => null
						 	)
						 )
					)
				),
				'serializer' => 'SMW\Serializers\ExpDataSerializer',
				'version' => 0.1
			)
		);

		#2 Nested
		$expData = new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) );

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', new DIBlob( 'SomeText' ) ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', new DIBlob( 'SomeOtherText' ) ) )
		);

		$provider[] = array(
			$expData,
			array(
				'subject' => array(
					'type' => 1,
					'uri' => 'Foo|Bar|Mo',
					'dataitem' => null
				),
				'data' => array(
					'LaLi' => array(
						 'property' => array(
						 	'type' => 1,
						 	'uri' => 'Li|La|Lu',
						 	'dataitem' => array( // DIBlob
						 		'type' => 2,
						 		'item' => 'SomeText'
						 	)
						 ),
						 'children' => array(
						 	array( // ExpLiteral
						 		'type' => 2,
						 		'lexical' => 'Foo',
						 		'datatype' => 'Bar',
						 		'lang' => '',
						 		'dataitem' => null
						 	),
						 	array( // ExpData
								'subject' => array(
								'type' => 1,
								'uri' => 'Foo|Bar|Mo',
							 	'dataitem' => array(
							 		'type' => 2,
							 		'item' => 'SomeOtherText'
							 		)
								),
								'data' => array()
						 	)
						 )
					)
				),
				'serializer' => 'SMW\Serializers\ExpDataSerializer',
				'version' => 0.1
			)
		);

		return $provider;
	}


}
