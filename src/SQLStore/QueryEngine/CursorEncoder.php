<?php

namespace SMW\SQLStore\QueryEngine;

use InvalidArgumentException;

/**
 * Opaque base64url-encoded JSON cursors for keyset pagination across the
 * QueryEngine result surface.
 *
 * The encoded payload is a JSON object with a mandatory `"v"` version field
 * plus the cursor anchor data:
 *
 * ```
 * { "v": 1, "sort": "<last_row_sort_value>", "id": <last_row_smw_id> }
 * ```
 *
 * Clients treat tokens as opaque. The format may grow new fields (e.g. a
 * compound sort tuple for multi-property `sort=` support, a subquery
 * disambiguator, etc.); the version field gates schema evolution. Old
 * servers that don't recognise a version return `null` and the consumer
 * decides whether to surface an error or fall back to the legacy offset
 * path.
 *
 * Encoding uses base64url (RFC 4648 §5): `-` / `_` substitute for `+` / `/`
 * and `=` padding is stripped, so tokens are safe in URL query strings
 * without percent-encoding.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
final class CursorEncoder {

	public const CURRENT_VERSION = 1;

	/**
	 * Encode a cursor payload into an opaque base64url string. The `"v"`
	 * field is auto-injected at the current version if the caller omits
	 * it, so consumers can pass `[ 'id' => 42 ]` without thinking about
	 * the version.
	 *
	 * @since 7.0.0
	 *
	 * @param array $payload The cursor anchor data. Will be JSON-encoded.
	 *
	 * @return string The opaque cursor token, safe to embed in URLs and
	 *   JSON response fields without further escaping.
	 */
	public static function encode( array $payload ): string {
		if ( !isset( $payload['v'] ) ) {
			$payload['v'] = self::CURRENT_VERSION;
		}

		$json = json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( $json === false ) {
			throw new InvalidArgumentException(
				'CursorEncoder: failed to JSON-encode cursor payload'
			);
		}

		return rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
	}

	/**
	 * Decode an opaque base64url cursor token back to its payload array.
	 * Returns `null` for any input that isn't a well-formed token at the
	 * current version: malformed base64, malformed JSON, missing version
	 * field, or unknown future version. Callers MUST treat `null` as
	 * "cursor not understood" and either surface an error or fall back
	 * to the legacy offset path; they MUST NOT silently use a partial
	 * decode.
	 *
	 * @since 7.0.0
	 *
	 * @param string $token The opaque cursor token, typically from a URL
	 *   parameter or a JSON request field.
	 */
	public static function decode( string $token ): ?array {
		if ( $token === '' ) {
			return null;
		}

		// Restore base64 padding stripped by `rtrim` in `encode()`.
		$padded = $token . str_repeat( '=', ( 4 - strlen( $token ) % 4 ) % 4 );
		$json = base64_decode( strtr( $padded, '-_', '+/' ), true );

		if ( $json === false ) {
			return null;
		}

		$payload = json_decode( $json, true );
		if ( !is_array( $payload ) ) {
			return null;
		}

		if ( !isset( $payload['v'] ) || $payload['v'] !== self::CURRENT_VERSION ) {
			return null;
		}

		return $payload;
	}

}
