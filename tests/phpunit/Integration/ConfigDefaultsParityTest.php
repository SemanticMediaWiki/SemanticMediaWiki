<?php

namespace SMW\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Asserts each extension.json `config` entry seeds an equal value into
 * $GLOBALS. Catches drift between the manifest and the live config state
 * (e.g. a migration that copied a literal but typo'd the value, or a
 * registration callback that overwrote rather than provided-default).
 *
 * Empty manifest ⇒ this test trivially passes; cases populate as
 * subsequent PRs migrate settings.
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigDefaultsParityTest extends TestCase {

	private const MANIFEST = __DIR__ . '/../../../extension.json';

	/**
	 * Settings whose value in $GLOBALS is derived at runtime by SMW
	 * initialization code (e.g. Exporter::initBaseURIs() computes the
	 * namespace URI from the wiki URL and stores it back via `global`). These
	 * start as the manifest default but are overwritten before the test can
	 * compare them, so exclude them from the parity check rather than
	 * hardcoding the environment-specific computed value.
	 */
	private const RUNTIME_DERIVED_KEYS = [
		'smwgNamespace',
	];

	public function testEveryConfigEntrySeedsMatchingGlobalsValue(): void {
		$manifest = json_decode( file_get_contents( self::MANIFEST ), true );
		$prefix   = $manifest['config_prefix'] ?? 'wg';
		$config   = $manifest['config'] ?? [];

		if ( $config === [] ) {
			$this->assertTrue( true, '0 keys checked, empty manifest' );
			return;
		}

		// phpunit.xml.dist may intentionally override some globals for the test
		// environment (e.g. smwgEnabledDeferredUpdate => false to disable async
		// updates during tests). Read those overrides and skip any key whose
		// phpunit value differs from the manifest default so the parity test
		// only fails on genuine drift, not intentional test-env overrides.
		$phpunitOverrides = $this->readPhpunitVarOverrides();

		foreach ( $config as $key => $entry ) {
			$globalKey = $prefix . $key;

			// Skip settings with runtime-derived $GLOBALS values.
			if ( in_array( $globalKey, self::RUNTIME_DERIVED_KEYS, true ) ) {
				continue;
			}

			if ( isset( $phpunitOverrides[$globalKey] ) ) {
				// Cast to the same type as the manifest value for comparison.
				$overrideValue = $this->castToType(
					$phpunitOverrides[$globalKey],
					$entry['value']
				);
				if ( $overrideValue !== $entry['value'] ) {
					// Intentionally overridden in the test environment; skip.
					continue;
				}
				// Override equals the manifest default. Either the override is
				// redundant (remove it from phpunit.xml.dist) or the manifest
				// drifted to match it (a real bug we'd otherwise miss). Fail
				// loudly rather than silently passing.
				$this->fail(
					"phpunit.xml.dist `<var name=\"$globalKey\">` equals the "
					. "manifest default. Either remove the redundant override, or "
					. "investigate whether the manifest default has drifted."
				);
			}

			$this->assertArrayHasKey(
				$globalKey,
				$GLOBALS,
				"\$GLOBALS missing entry for config key '$key'"
			);

			$expected = $entry['value'];

			// Apply path: true semantics. Mirrors MW core's
			// ExtensionProcessor::applyPath — shallow: a scalar gets the dir
			// prefixed; a flat array gets each value prefixed. Settings whose
			// value would need recursion don't use path: true (they're
			// handled by ConfigBootstrap instead).
			if ( !empty( $entry['path'] ) ) {
				$dir = realpath( dirname( self::MANIFEST ) );
				if ( is_array( $expected ) ) {
					foreach ( $expected as $k => $v ) {
						$expected[$k] = $dir . '/' . $v;
					}
				} else {
					$expected = "$dir/$expected";
				}
			}

			$this->assertSame(
				$expected,
				$GLOBALS[$globalKey],
				"Config value drift for '$key'"
			);
		}
	}

	/**
	 * Parse `<var name="..." value="..."/>` entries from the nearest phpunit
	 * XML config file and return them as a `[ globalName => stringValue ]` map.
	 *
	 * @return array<string, string>
	 */
	private function readPhpunitVarOverrides(): array {
		$xmlFile = __DIR__ . '/../../../phpunit.xml.dist';
		if ( !is_readable( $xmlFile ) ) {
			return [];
		}

		$overrides = [];
		$xml = simplexml_load_file( $xmlFile );
		if ( $xml === false ) {
			return [];
		}

		foreach ( $xml->xpath( '//php/var' ) as $var ) {
			$name  = (string)$var['name'];
			$value = (string)$var['value'];
			if ( $name !== '' ) {
				$overrides[$name] = $value;
			}
		}

		return $overrides;
	}

	/**
	 * Cast a string value (from XML) to the same PHP type as $reference.
	 */
	private function castToType( string $value, mixed $reference ): bool|int|float|string|null {
		if ( is_bool( $reference ) ) {
			return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}
		if ( is_int( $reference ) ) {
			return (int)$value;
		}
		if ( is_float( $reference ) ) {
			return (float)$value;
		}
		if ( $reference === null ) {
			return $value === 'null' ? null : $value;
		}
		return $value;
	}

}
