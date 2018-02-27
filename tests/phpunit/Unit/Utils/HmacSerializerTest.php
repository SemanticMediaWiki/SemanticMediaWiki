<?php

namespace SMW\Tests\Utils;

use SMW\Utils\HmacSerializer;

/**
 * @covers \SMW\Utils\HmacSerializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HmacSerializerTest extends \PHPUnit_Framework_TestCase {

	public function testEncodeWithDifferentKey() {

		$instance = new HmacSerializer();

		$data = [ 'Foo' ];

		$this->assertNotSame(
			$instance->encode( $data, 'abc' ),
			$instance->encode( $data, 'def' )
		);
	}

	public function testUDecodeUsingAnObject() {

		$this->assertFalse(
			HmacSerializer::decode( $this )
		);
	}

	public function testRoundtripEncodeDecode() {

		$instance = new HmacSerializer();

		$data = [ 'Foo' ];

		$this->assertEquals(
			$data,
			$instance->decode( $instance->encode( $data, 'def' ), 'def' )
		);
	}

	public function testRoundtripEncodeDecodeWithDifferentKey() {

		$instance = new HmacSerializer();

		$data = [ 'Foo' ];

		$result = $instance->decode(
			$instance->encode( $data, 'def' ),
			'abc'
		);

		$this->assertNotEquals(
			$data,
			$result
		);

		$this->assertFalse(
			$result
		);
	}

	public function testSeralizeWithDifferentKey() {

		$data = [ 'Foo' ];

		$this->assertNotSame(
			HmacSerializer::serialize( $data, 'abc' ),
			HmacSerializer::serialize( $data, 'def' )
		);
	}

	public function testUnseralizeUsingAnObject() {

		$this->assertFalse(
			HmacSerializer::unserialize( $this )
		);
	}

	public function testRoundtripSerializeDeserialize() {

		$instance = new HmacSerializer();

		$data = [ 'Foo' ];

		$this->assertEquals(
			$data,
			$instance->unserialize( $instance->serialize( $data, 'def' ), 'def' )
		);
	}

	public function testRoundtripCompressUncompress() {

		$instance = new HmacSerializer();

		$data = [ 'Foo' ];

		$this->assertEquals(
			$data,
			$instance->uncompress( $instance->compress( $data, 'def' ), 'def' )
		);
	}

	public function testRoundtripSerializeDeserializeWithDifferentKey() {

		$instance = new HmacSerializer();

		$data = [ 'Foo' ];

		$result = $instance->unserialize(
			$instance->serialize( $data, 'def' ),
			'abc'
		);

		$this->assertNotEquals(
			$data,
			$result
		);

		$this->assertFalse(
			$result
		);
	}

}
