<?php

namespace SMW\Test;

use SMW\SerializerFactory;
use SMw\SemanticData;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SerializerFactory
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SerializerFactoryTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SerializerFactory';
	}

	/**
	 * @since 1.9
	 */
	public function testUnregisteredSerializeObjectOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		SerializerFactory::serialize( 'Foo' );

	}

	/**
	 * @dataProvider exceptionDataProvider
	 * @since 1.9
	 */
	public function testUnregisteredDeserializerObjectOutOfBoundsException( $setup ) {

		$this->setExpectedException( 'OutOfBoundsException' );

		SerializerFactory::deserialize( $setup );

	}

	/**
	 * @dataProvider serializerDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisteredSerializerFactory( $object ) {

		$serialized = SerializerFactory::serialize( $object );

		$this->assertInternalType(
			'array',
			$serialized,
			'Asserts that serialize() returns an array'
		);

	}

	/**
	 * @dataProvider deserializerDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisteredDeserializerFactory( $object, $instance ) {

		$unserialized = SerializerFactory::deserialize( $object );

		$this->assertInstanceOf(
			$instance,
			$unserialized,
			"Asserts that deserialize() returns a {$instance} instance"
		);

	}

	/**
	 * @return array
	 */
	public function exceptionDataProvider() {

		$provider = array();

		// #0
		$provider[] = array( array() );

		// #1
		$provider[] = array( array( 'serializer' => 'Foo' ) );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function serializerDataProvider() {

		$provider = array();

		// #0 SemanticData
		$provider[] = array(
			 new SemanticData( DIWikiPage::newFromTitle( $this->newTitle() ) ),
		);

		// #1 QueryResult
		$provider[] = array(
			$this->newMockBuilder()->newObject( 'QueryResult', array(
				'getResults'       => array(),
				'getPrintRequests' => array()
			) )
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function deserializerDataProvider() {

		$provider = array();

		// #0 SemanticData
		$provider[] = array( array( 'serializer' => 'SMW\Serializers\SemanticDataSerializer', 'subject' => 'Foo#0#' ), '\SMW\SemanticData' );

		return $provider;
	}

}
