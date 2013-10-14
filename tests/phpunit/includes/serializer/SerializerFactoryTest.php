<?php

namespace SMW\Test;

use SMW\SerializerFactory;
use SMw\SemanticData;
use SMW\DIWikiPage;

/**
 * Tests for the SerializerFactory class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SerializerFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
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
	 * @since 1.9
	 */
	public function testUnregisteredUnserializeObjectOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		SerializerFactory::unserialize( array() );

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
	 * @dataProvider unserializerDataProvider
	 *
	 * @since 1.9
	 */
	public function testRegisteredUnserializerFactory( $object, $instance ) {

		$unserialized = SerializerFactory::unserialize( $object );

		$this->assertInstanceOf(
			$instance,
			$unserialized,
			"Asserts that unserialize() returns a {$instance} instance"
		);

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

		return $provider;
	}

	/**
	 * @return array
	 */
	public function unserializerDataProvider() {

		$provider = array();

		// #0 SemanticData
		$provider[] = array( array( 'serializer' => '\SMW\SemanticDataSerializer', 'subject' => 'Foo#0#' ), '\SMW\SemanticData' );

		return $provider;
	}

}
