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

	public function testEveryConfigEntrySeedsMatchingGlobalsValue(): void {
		$manifest = json_decode( file_get_contents( self::MANIFEST ), true );
		$prefix   = $manifest['config_prefix'] ?? 'wg';
		$config   = $manifest['config'] ?? [];

		if ( $config === [] ) {
			$this->assertTrue( true, '0 keys checked, empty manifest' );
			return;
		}

		foreach ( $config as $key => $entry ) {
			$globalKey = $prefix . $key;

			$this->assertArrayHasKey(
				$globalKey,
				$GLOBALS,
				"\$GLOBALS missing entry for config key '$key'"
			);

			$expected = $entry['value'];

			if ( !empty( $entry['path'] ) ) {
				$expected = $this->applyPath( $expected, dirname( self::MANIFEST ) );
			}

			$this->assertSame(
				$expected,
				$GLOBALS[$globalKey],
				"Config value drift for '$key'"
			);
		}
	}

	/**
	 * Recursive path prefixing. NOTE: MW's ExtensionProcessor::applyPath
	 * (includes/registration/ExtensionProcessor.php:856) is **shallow** —
	 * it only iterates one level and string-concatenates each value with
	 * the extension dir. Settings whose `path: true` value contains a
	 * nested array (e.g. `smwgElasticsearchConfig.index_def.{data,lookup}`)
	 * therefore cannot be expressed via the manifest's `path` flag and
	 * must be set in `ConfigBootstrap::seedComputedDefaults()` instead.
	 *
	 * The recursive form here exists so that the parity test would still
	 * compute a meaningful expected value if such a nested-path setting
	 * were ever added to the manifest by mistake — the assertion would
	 * then fail loudly rather than silently passing.
	 */
	private function applyPath( mixed $value, string $dir ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[$k] = $this->applyPath( $v, $dir );
			}
			return $value;
		}

		return is_string( $value ) ? "$dir/$value" : $value;
	}

}
