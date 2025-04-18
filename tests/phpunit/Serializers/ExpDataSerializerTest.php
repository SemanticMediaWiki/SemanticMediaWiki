<?php

namespace SMW\Tests\Serializers;

use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Serializers\ExpDataSerializer;
use SMW\Tests\PHPUnitCompat;
use SMWDIBlob as DIBlob;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Serializers\ExpDataSerializer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ExpDataSerializerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstructor() {
		$this->assertInstanceOf(
			'\SMW\Serializers\ExpDataSerializer',
			new ExpDataSerializer()
		);
	}

	public function testInvalidSerializerObjectThrowsException() {
		$instance = new ExpDataSerializer();

		$this->expectException( 'OutOfBoundsException' );
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
		# 0
		$expData = new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) );

		$provider[] = [
			$expData,
			[
				'subject' => [
					'type' => 1,
					'uri' => 'Foo|Bar|Mo',
					'dataitem' => null
				],
				'data' => [],
				'serializer' => 'SMW\Serializers\ExpDataSerializer',
				'version' => 0.1
			]
		];

		# 1
		$expData = new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) );

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$provider[] = [
			$expData,
			[
				'subject' => [
					'type' => 1,
					'uri' => 'Foo|Bar|Mo',
					'dataitem' => null
				],
				'data' => [
					'LaLi' => [
						 'property' => [
							'type' => 1,
							'uri' => 'Li|La|Lu',
							'dataitem' => null
						 ],
						 'children' => [
							[
								'type' => 2,
								'lexical' => 'Foo',
								'datatype' => 'Bar',
								'lang' => '',
								'dataitem' => null
							]
						 ]
					]
				],
				'serializer' => 'SMW\Serializers\ExpDataSerializer',
				'version' => 0.1
			]
		];

		# 2 Nested
		$expData = new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) );

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', new DIBlob( 'SomeText' ) ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', new DIBlob( 'SomeOtherText' ) ) )
		);

		$provider[] = [
			$expData,
			[
				'subject' => [
					'type' => 1,
					'uri' => 'Foo|Bar|Mo',
					'dataitem' => null
				],
				'data' => [
					'LaLi' => [
						 'property' => [
							'type' => 1,
							'uri' => 'Li|La|Lu',
							'dataitem' => [ // DIBlob
								'type' => 2,
								'item' => 'SomeText'
							]
						 ],
						 'children' => [
							[ // ExpLiteral
								'type' => 2,
								'lexical' => 'Foo',
								'datatype' => 'Bar',
								'lang' => '',
								'dataitem' => null
							],
							[ // ExpData
								'subject' => [
								'type' => 1,
								'uri' => 'Foo|Bar|Mo',
								'dataitem' => [
									'type' => 2,
									'item' => 'SomeOtherText'
									]
								],
								'data' => []
							]
						 ]
					]
				],
				'serializer' => 'SMW\Serializers\ExpDataSerializer',
				'version' => 0.1
			]
		];

		return $provider;
	}

}
