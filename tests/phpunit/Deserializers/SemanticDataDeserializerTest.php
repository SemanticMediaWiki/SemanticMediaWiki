<?php

namespace SMW\Tests\Deserializers;

use SMW\Deserializers\SemanticDataDeserializer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Deserializers\SemanticDataDeserializer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataDeserializerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstructor() {
		$this->assertInstanceOf(
			'\SMW\Deserializers\SemanticDataDeserializer',
			new SemanticDataDeserializer()
		);
	}

	public function testDeserializerInvalidVersionThrowsException() {
		$instance = new SemanticDataDeserializer();

		$this->expectException( 'OutOfBoundsException' );

		$instance->deserialize(
			[ 'version' => 'Foo' ]
		);
	}

	public function testDeserializerForInvalidSubjectThrowsException() {
		$instance = new SemanticDataDeserializer();

		$this->expectException( '\SMW\Exception\DataItemDeserializationException' );

		$instance->deserialize(
			[ 'subject' => '--#Foo' ]
		);
	}

	public function testDeserializerForMissingSubjectThrowsException() {
		$instance = new SemanticDataDeserializer();

		$this->expectException( 'RuntimeException' );
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
