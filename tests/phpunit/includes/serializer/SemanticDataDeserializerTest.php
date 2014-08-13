<?php

namespace SMW\Test;

use SMW\Deserializers\SemanticDataDeserializer;

/**
 * @covers \SMW\Deserializers\SemanticDataDeserializer
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
class SemanticDataDeserializerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Deserializers\SemanticDataDeserializer';
	}

	/**
	 * Helper method that returns a SemanticDataDeserializer object
	 *
	 * @since 1.9
	 */
	private function newDeserializerInstance() {
		return new SemanticDataDeserializer();
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newDeserializerInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testDeserializerInvalidVersionOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newDeserializerInstance();
		$instance->deserialize( array( 'version' => 'Foo' ) );

	}

	/**
	 * @since 1.9
	 */
	public function testDeserializerInvalidSubjectDataItemException() {

		$this->setExpectedException( '\SMW\DataItemException' );

		$instance = $this->newDeserializerInstance();
		$instance->deserialize( array( 'subject' => '--#Foo' ) );

	}

	/**
	 * @since 1.9
	 */
	public function testDeserializerMissingSubjectOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newDeserializerInstance();
		$instance->deserialize( array() );

	}

	/**
	 * @since 1.9
	 */
	public function testDeserializerSubjectWithoutData() {

		$instance = $this->newDeserializerInstance();

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$instance->deserialize( array( 'subject' => 'Foo#0#' ) )
		);

	}

}
