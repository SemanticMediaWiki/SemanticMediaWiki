<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\QueryEngine\CursorEncoder;

/**
 * @covers \SMW\SQLStore\QueryEngine\CursorEncoder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class CursorEncoderTest extends TestCase {

	public function testEncodeAutoInjectsCurrentVersion(): void {
		$token = CursorEncoder::encode( [ 'id' => 42 ] );
		$payload = CursorEncoder::decode( $token );

		$this->assertIsArray( $payload );
		$this->assertSame( CursorEncoder::CURRENT_VERSION, $payload['v'] );
		$this->assertSame( 42, $payload['id'] );
	}

	public function testRoundtripPreservesScalarFields(): void {
		$original = [
			'v' => 1,
			'sort' => 'Some sort value with spaces',
			'id' => 12345,
		];

		$decoded = CursorEncoder::decode( CursorEncoder::encode( $original ) );

		$this->assertSame( $original, $decoded );
	}

	public function testRoundtripPreservesNestedArrays(): void {
		// Multi-sort cursor schema (Phase 3b extension) must round-trip
		// through the v1 encoder unchanged. This locks the format's
		// forward compatibility: adding new fields to the payload does
		// not require an encoder change.
		$original = [
			'v' => 1,
			'sort' => [ 'primary', 'secondary' ],
			'id' => 99,
		];

		$this->assertSame(
			$original,
			CursorEncoder::decode( CursorEncoder::encode( $original ) )
		);
	}

	public function testRoundtripPreservesUnicode(): void {
		$original = [
			'v' => 1,
			'sort' => 'Bär · café · 日本語',
			'id' => 1,
		];

		$this->assertSame(
			$original,
			CursorEncoder::decode( CursorEncoder::encode( $original ) )
		);
	}

	public function testEncodedTokenIsUrlSafe(): void {
		// Construct a payload likely to produce `+`, `/`, or `=` in
		// standard base64 (long string, non-aligned length). The
		// base64url variant must replace all of them.
		$token = CursorEncoder::encode( [
			'sort' => str_repeat( '?>', 50 ),
			'id' => 42,
		] );

		$this->assertStringNotContainsString( '+', $token );
		$this->assertStringNotContainsString( '/', $token );
		$this->assertStringNotContainsString( '=', $token );
	}

	public function testDecodeRejectsEmptyToken(): void {
		$this->assertNull( CursorEncoder::decode( '' ) );
	}

	public function testDecodeRejectsMalformedBase64(): void {
		$this->assertNull( CursorEncoder::decode( '!!!not base64!!!' ) );
	}

	public function testDecodeRejectsMalformedJson(): void {
		// Valid base64url but the decoded bytes are not JSON.
		$token = rtrim( strtr( base64_encode( 'not json' ), '+/', '-_' ), '=' );
		$this->assertNull( CursorEncoder::decode( $token ) );
	}

	public function testDecodeRejectsMissingVersionField(): void {
		// Valid base64url, valid JSON, but no `v` field.
		$token = rtrim( strtr( base64_encode( '{"id":42}' ), '+/', '-_' ), '=' );
		$this->assertNull( CursorEncoder::decode( $token ) );
	}

	public function testDecodeRejectsUnknownVersion(): void {
		// Forward-compat: a v=99 cursor (some future format) must be
		// rejected by this server, not partially decoded. The consumer
		// is responsible for surfacing the error or falling back.
		$token = rtrim( strtr( base64_encode( '{"v":99,"id":42}' ), '+/', '-_' ), '=' );
		$this->assertNull( CursorEncoder::decode( $token ) );
	}

	public function testDecodeRejectsScalarJsonPayload(): void {
		// `json_decode("42", true)` returns the int 42, not an array.
		$token = rtrim( strtr( base64_encode( '42' ), '+/', '-_' ), '=' );
		$this->assertNull( CursorEncoder::decode( $token ) );
	}

}
