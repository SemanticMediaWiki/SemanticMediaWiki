<?php

namespace SMW\Tests;

use Deserializers\Deserializer;
use PHPUnit\Framework\TestCase;
use Serializers\Serializer;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Deserializers\ExpDataDeserializer;
use SMW\Deserializers\SemanticDataDeserializer;
use SMW\Query\QueryResult;
use SMW\SerializerFactory;
use SMW\Serializers\ExpDataSerializer;
use SMW\Serializers\QueryResultSerializer;
use SMW\Serializers\SemanticDataSerializer;

/**
 * @covers \SMW\SerializerFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SerializerFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SerializerFactory::class,
			new SerializerFactory()
		);
	}

	public function testCanConstructSemanticDataSerializer() {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			SemanticDataSerializer::class,
			$instance->newSemanticDataSerializer()
		);
	}

	public function testCanConstructSemanticDataDeserializer() {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			SemanticDataDeserializer::class,
			$instance->newSemanticDataDeserializer()
		);
	}

	public function testCanConstructQueryResultSerializer() {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			QueryResultSerializer::class,
			$instance->newQueryResultSerializer()
		);
	}

	public function testCanConstructExpDataSerializer() {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			ExpDataSerializer::class,
			$instance->newExpDataSerializer()
		);
	}

	public function testCanConstructExpDataDeserializer() {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			ExpDataDeserializer::class,
			$instance->newExpDataDeserializer()
		);
	}

	/**
	 * @dataProvider objectToSerializerProvider
	 */
	public function testGetSerializerFor( $object ) {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			Serializer::class,
			$instance->getSerializerFor( $object )
		);
	}

	/**
	 * @dataProvider serializationToDeserializerProvider
	 */
	public function testGetDeserializerFor( $serialization ) {
		$instance = new SerializerFactory();

		$this->assertInstanceOf(
			Deserializer::class,
			$instance->getDeserializerFor( $serialization )
		);
	}

	public function testGetSerializerForUnregisteredSerializerThrowsException() {
		$instance = new SerializerFactory();

		$this->expectException( 'OutOfBoundsException' );
		$instance->getSerializerFor( 'Foo' );
	}

	public function testGetDeserializerForUnregisteredSerializerThrowsException() {
		$instance = new SerializerFactory();

		$this->expectException( 'OutOfBoundsException' );
		$instance->getDeserializerFor( [ 'Foo' ] );
	}

	public function objectToSerializerProvider() {
		# 0
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$provider[] = [
			$semanticData
		];

		# 1
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$queryResult
		];

		# 2
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

		# 0
		$provider[] = [
			[ 'serializer' => SemanticDataSerializer::class, 'subject' => 'Foo#0##' ]
		];

		# 1
		$provider[] = [
			[ 'serializer' => ExpDataSerializer::class ]
		];

		return $provider;
	}

}
