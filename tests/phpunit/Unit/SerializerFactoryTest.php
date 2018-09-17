<?php

namespace SMW\Tests;

use SMW\SerializerFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SerializerFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SerializerFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SerializerFactory',
			new SerializerFactory()
		);
	}

	public function testCanConstructSemanticDataSerializer() {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\SMW\Serializers\SemanticDataSerializer',
			$instance->newSemanticDataSerializer()
		);
	}

	public function testCanConstructSemanticDataDeserializer() {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\SMW\Deserializers\SemanticDataDeserializer',
			$instance->newSemanticDataDeserializer()
		);
	}

	public function testCanConstructQueryResultSerializer() {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\SMW\Serializers\QueryResultSerializer',
			$instance->newQueryResultSerializer()
		);
	}

	public function testCanConstructExpDataSerializer() {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\SMW\Serializers\ExpDataSerializer',
			$instance->newExpDataSerializer()
		);
	}

	public function testCanConstructExpDataDeserializer() {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\SMW\Deserializers\ExpDataDeserializer',
			$instance->newExpDataDeserializer()
		);
	}

	/**
	 * @dataProvider objectToSerializerProvider
	 */
	public function testGetSerializerFor( $object ) {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\Serializers\Serializer',
			$instance->getSerializerFor( $object )
		);
	}

	/**
	 * @dataProvider serializationToDeserializerProvider
	 */
	public function testGetDeserializerFor( $serialization ) {

		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			'\Deserializers\Deserializer',
			$instance->getDeserializerFor( $serialization )
		);
	}

	public function testGetSerializerForUnregisteredSerializerThrowsException() {

		$instance = new SerializerFactory();

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->getSerializerFor( 'Foo' );
	}

	public function testGetDeserializerForUnregisteredSerializerThrowsException() {

		$instance = new SerializerFactory();

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->getDeserializerFor( [ 'Foo' ] );
	}

	public function objectToSerializerProvider() {

		#0
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$semanticData
		];

		#1
		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$queryResult
		];

		#2
		$queryResult = $this->getMockBuilder( '\SMWExpData' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$queryResult
		];

		return $provider;
	}

	public function serializationToDeserializerProvider() {

		$provider = [];

		#0
		$provider[] = [
			[ 'serializer' => 'SMW\Serializers\SemanticDataSerializer', 'subject' => 'Foo#0##' ]
		];

		#1
		$provider[] = [
			[ 'serializer' => 'SMW\Serializers\ExpDataSerializer' ]
		];

		return $provider;
	}

}
