<?php

namespace SMW\Tests\Deserializers;

use SMW\Deserializers\SemanticDataDeserializer;

/**
 * @covers \SMW\Deserializers\SemanticDataDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructor() {

		$this->assertInstanceOf(
			'\SMW\Deserializers\SemanticDataDeserializer',
			new SemanticDataDeserializer()
		);
	}

	public function testDeserializerInvalidVersionThrowsException() {

		$instance = new SemanticDataDeserializer();

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance->deserialize(
			array( 'version' => 'Foo' )
		);
	}

	public function testDeserializerForInvalidSubjectThrowsException() {

		$instance = new SemanticDataDeserializer();

		$this->setExpectedException( '\SMW\DataItemException' );

		$instance->deserialize(
			array( 'subject' => '--#Foo' )
		);
	}

	public function testDeserializerForMissingSubjectThrowsException() {

		$instance = new SemanticDataDeserializer();

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->deserialize( array() );
	}

	public function testDeserializerForEmptyData() {

		$instance = new SemanticDataDeserializer();

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$instance->deserialize( array( 'subject' => 'Foo#0#' ) )
		);
	}

}
