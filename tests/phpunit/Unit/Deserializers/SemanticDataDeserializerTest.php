<?php

namespace SMW\Tests\Deserializers;

use SMW\Deserializers\SemanticDataDeserializer;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

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
			[ 'version' => 'Foo' ]
		);
	}

	public function testDeserializerForInvalidSubjectThrowsException() {

		$instance = new SemanticDataDeserializer();

		$this->setExpectedException( '\SMW\Exception\DataItemDeserializationException' );

		$instance->deserialize(
			[ 'subject' => '--#Foo' ]
		);
	}

	public function testDeserializerForMissingSubjectThrowsException() {

		$instance = new SemanticDataDeserializer();

		$this->setExpectedException( 'RuntimeException' );
		$instance->deserialize( [] );
	}

	public function testDeserializerForEmptyData() {

		$instance = new SemanticDataDeserializer();

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$instance->deserialize( [ 'subject' => 'Foo#0##' ] )
		);
	}

}
