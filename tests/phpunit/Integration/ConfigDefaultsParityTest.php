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

			$this->assertEquals(
				$expected,
				$GLOBALS[$globalKey],
				"Config value drift for '$key'"
			);
		}
	}

	/**
	 * Mirrors ExtensionProcessor::applyPath — recursive path prefixing for
	 * `path: true` config entries.
	 */
	private function applyPath( $value, string $dir ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[$k] = $this->applyPath( $v, $dir );
			}
			return $value;
		}

		return is_string( $value ) ? "$dir/$value" : $value;
	}

}
