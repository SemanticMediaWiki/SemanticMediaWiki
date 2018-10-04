<?php

namespace SMW\Tests\Deserializers;

use SMW\Deserializers\ExpDataDeserializer;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Serializers\ExpDataSerializer;
use SMWDIBlob as DIBlob;
use SMWExpData as ExpData;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Deserializers\ExpDataDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpDataDeserializerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstructor() {

		$this->assertInstanceOf(
			'\SMW\Deserializers\ExpDataDeserializer',
			new ExpDataDeserializer()
		);
	}

	public function testInvalidSerializerObjectThrowsException() {

		$instance = new ExpDataDeserializer();

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->deserialize( 'Foo' );
	}

	public function testVersionMismatchThrowsException() {

		$instance = new ExpDataDeserializer();

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->deserialize( [ 'version' => 0.2 ] );
	}

	/**
	 * @dataProvider expDataProvider
	 */
	public function testDeserialize( $seralization, $expected ) {

		$instance = new ExpDataDeserializer();

		$this->assertEquals(
			$expected,
			$instance->deserialize( $seralization )
		);
	}

	/**
	 * @dataProvider expDataProvider
	 */
	public function testDeserializeToCompareHash( $seralization, $expected ) {

		$instance = new ExpDataDeserializer();

		$this->assertEquals(
			$expected->getHash(),
			$instance->deserialize( $seralization )->getHash()
		);
	}

	public function expDataProvider() {

		$serializier = new ExpDataSerializer();

		#0
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$provider[] = [
			$serializier->serialize( $expData ),
			$expData
		];

		#1
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$provider[] = [
			$serializier->serialize( $expData ),
			$expData
		];

		#2 Nested
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', new DIBlob( 'SomeText' ) ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', new DIBlob( 'SomeOtherText' ) ) )
		);

		$provider[] = [
			$serializier->serialize( $expData ),
			$expData
		];

		#2 Nested level 2+3

		$expDataLevel2 = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', new DIBlob( 'SomeOtherText' ) )
		);

		$expDataLevel2->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', new DIBlob( 'SomeText' ) ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expDataLevel2->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpData( new ExpNsResource( 'Foo', 'Bar', 'Mo', null ) ) // 3
		);

		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', new DIBlob( 'SomeText' ) ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			$expDataLevel2
		);

		$provider[] = [
			$serializier->serialize( $expData ),
			$expData
		];

		return $provider;
	}

}
