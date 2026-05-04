<?php

namespace SMW\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SMW\Setup\ConfigBootstrap;

/**
 * Regression coverage for the partial-write bug class behind #6649 and
 * #6726: LocalSettings.php writes a single nested key into a compound
 * `$smwg*` array, and the rest of the defaults must survive.
 *
 * Each test case simulates the documented partial-write pattern, triggers
 * the extension config-resolution path, and asserts that both (a) the
 * user-set value won and (b) untouched defaults are still present.
 *
 * Cases are added per compound global as subsequent PRs migrate them with
 * appropriate merge_strategy declarations in extension.json.
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigPartialWriteTest extends TestCase {

	// ---------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------

	/**
	 * Load a single entry from extension.json's config block.
	 *
	 * Returns an array with two keys:
	 *   - 'value'          — the default value declared in the manifest
	 *   - 'merge_strategy' — the merge_strategy string
	 *
	 * @param string $shortKey The key as it appears in extension.json's
	 *   "config" object (without the "smwg" prefix, matching the manifest's
	 *   camelCase key, e.g. "SparqlEndpoint" for smwgSparqlEndpoint).
	 */
	private function loadManifestEntry( string $shortKey ): array {
		static $config = null;
		if ( $config === null ) {
			$manifest = json_decode(
				file_get_contents( __DIR__ . '/../../../extension.json' ),
				true
			);
			$config = $manifest['config'];
		}
		$this->assertArrayHasKey(
			$shortKey,
			$config,
			"extension.json config key '$shortKey' not found"
		);
		return [
			'value'          => $config[$shortKey]['value'],
			'merge_strategy' => $config[$shortKey]['merge_strategy'],
		];
	}

	/**
	 * Apply the merge strategy that ExtensionRegistry::exportExtractedData()
	 * would use, merging $userValue on top of $manifestDefault.
	 *
	 * @param string $strategy One of: provide_default, array_plus,
	 *                          array_plus_2d, array_replace_recursive
	 * @param array $default The manifest's default value
	 * @param array $user The user's partial value from LocalSettings.php
	 */
	private function applyMergeStrategy( string $strategy, array $default, array $user ): array {
		switch ( $strategy ) {
			case 'provide_default':
				// User value replaces the whole default.
				return $user;
			case 'array_plus':
				// User keys win; missing default keys are filled in.
				return $user + $default;
			case 'array_plus_2d':
				// Two-level array_plus: user inner-keys win at both levels.
				return wfArrayPlus2d( $user, $default );
			case 'array_replace_recursive':
				// User values at any depth win; unset paths from default survive.
				return array_replace_recursive( $default, $user );
			default:
				$this->fail( "Unknown merge_strategy '$strategy'" );
		}
	}

	// ---------------------------------------------------------------------------
	// extension.json-bound settings (8)
	// ---------------------------------------------------------------------------

	/**
	 * smwgSparqlEndpoint — array_plus
	 * User overrides the 'query' URL; 'update' and 'data' must survive.
	 */
	public function testSparqlEndpointPartialWrite(): void {
		$entry  = $this->loadManifestEntry( 'SparqlEndpoint' );
		$user   = [ 'query' => 'http://my-sparql-host/sparql/' ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User value won.
		$this->assertSame( 'http://my-sparql-host/sparql/', $result['query'] );
		// Default keys survive.
		$this->assertArrayHasKey( 'update', $result );
		$this->assertArrayHasKey( 'data', $result );
	}

	/**
	 * smwgPagingLimit — array_replace_recursive
	 * User overrides one scalar leaf ('type'); peer scalar leaves and the
	 * nested 'browse' submap must survive with their defaults.
	 */
	public function testPagingLimitPartialWrite(): void {
		$entry = $this->loadManifestEntry( 'PagingLimit' );
		// Documented user pattern: $smwgPagingLimit['type'] = 100;
		$user = [ 'type' => 100 ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User scalar override won (and was NOT summed with the default 50).
		$this->assertSame( 100, $result['type'] );
		// Peer scalar keys survive.
		$this->assertArrayHasKey( 'concept', $result );
		$this->assertArrayHasKey( 'property', $result );
		$this->assertArrayHasKey( 'errorlist', $result );
		// Nested 'browse' submap survives entirely.
		$this->assertArrayHasKey( 'browse', $result );
		$this->assertArrayHasKey( 'valuelist.outgoing', $result['browse'] );
		$this->assertArrayHasKey( 'valuelist.incoming', $result['browse'] );
	}

	/**
	 * smwgPropertyListLimit — array_plus
	 * User sets 'subproperty'; 'redirect' and 'error' must survive.
	 */
	public function testPropertyListLimitPartialWrite(): void {
		$entry  = $this->loadManifestEntry( 'PropertyListLimit' );
		$user   = [ 'subproperty' => 50 ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User value won.
		$this->assertSame( 50, $result['subproperty'] );
		// Default keys survive.
		$this->assertArrayHasKey( 'redirect', $result );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * smwgResultFormats — array_plus
	 * User adds a custom format class; all built-in formats must survive.
	 */
	public function testResultFormatsPartialWrite(): void {
		$entry  = $this->loadManifestEntry( 'ResultFormats' );
		$user   = [ 'mychart' => 'Acme\\Query\\ChartResultPrinter' ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User value won.
		$this->assertSame( 'Acme\\Query\\ChartResultPrinter', $result['mychart'] );
		// Default formats survive.
		$this->assertArrayHasKey( 'table', $result );
		$this->assertArrayHasKey( 'list', $result );
		$this->assertArrayHasKey( 'json', $result );
	}

	/**
	 * smwgResultAliases — array_plus
	 * User adds a new alias entry; built-in aliases must survive.
	 */
	public function testResultAliasesPartialWrite(): void {
		$entry  = $this->loadManifestEntry( 'ResultAliases' );
		$user   = [ 'mychart' => [ 'chart', 'barchart' ] ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User value won.
		$this->assertSame( [ 'chart', 'barchart' ], $result['mychart'] );
		// Default aliases survive.
		$this->assertArrayHasKey( 'feed', $result );
		$this->assertArrayHasKey( 'templatefile', $result );
		$this->assertArrayHasKey( 'plainlist', $result );
	}

	/**
	 * smwgCacheUsage — array_plus
	 * User overrides one TTL; the other TTL entries must survive.
	 */
	public function testCacheUsagePartialWrite(): void {
		$entry  = $this->loadManifestEntry( 'CacheUsage' );
		$user   = [ 'special.wantedproperties' => 7200 ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User value won.
		$this->assertSame( 7200, $result['special.wantedproperties'] );
		// Peer default entries survive.
		$this->assertArrayHasKey( 'special.unusedproperties', $result );
		$this->assertArrayHasKey( 'api.browse', $result );
		$this->assertArrayHasKey( 'api.task', $result );
	}

	/**
	 * smwgPostEditUpdate — array_replace_recursive
	 * User flips the top-level 'check-query' bool; the 'run-jobs' and
	 * 'purge-page' submaps must survive untouched.
	 */
	public function testPostEditUpdatePartialWrite(): void {
		$entry = $this->loadManifestEntry( 'PostEditUpdate' );
		// Documented user pattern: $smwgPostEditUpdate['check-query'] = true;
		$user = [ 'check-query' => true ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User scalar override won (NOT summed with the default false).
		$this->assertTrue( $result['check-query'] );
		// Nested submaps survive entirely.
		$this->assertArrayHasKey( 'run-jobs', $result );
		$this->assertArrayHasKey( 'smw.fulltextSearchTableUpdate', $result['run-jobs'] );
		$this->assertArrayHasKey( 'purge-page', $result );
		$this->assertArrayHasKey( 'on-outdated-query-dependency', $result['purge-page'] );
	}

	/**
	 * smwgFulltextSearchTableOptions — array_plus
	 * User adds a postgres entry; mysql and sqlite entries must survive.
	 */
	public function testFulltextSearchTableOptionsPartialWrite(): void {
		$entry  = $this->loadManifestEntry( 'FulltextSearchTableOptions' );
		$user   = [ 'postgres' => [ 'tsvector' ] ];

		$result = $this->applyMergeStrategy( $entry['merge_strategy'], $entry['value'], $user );

		// User value won.
		$this->assertSame( [ 'tsvector' ], $result['postgres'] );
		// Default entries survive.
		$this->assertArrayHasKey( 'mysql', $result );
		$this->assertArrayHasKey( 'sqlite', $result );
	}

	// ---------------------------------------------------------------------------
	// ConfigBootstrap-bound settings (4)
	// These settings are merged inside ConfigBootstrap::seedComputedDefaults()
	// using unconditional logic (no !isset() guard), so re-calling
	// seedComputedDefaults() after a partial write merges defaults under the
	// user's value.
	// ---------------------------------------------------------------------------

	/**
	 * smwgLocalConnectionConf — ConfigBootstrap array_plus_2d (manual)
	 * User adds a custom connection; 'mw.db' and 'mw.db.queryengine' must
	 * survive with their read/write sub-keys.
	 */
	public function testLocalConnectionConfPartialWrite(): void {
		$key    = 'smwgLocalConnectionConf';
		$backup = $GLOBALS[$key] ?? null;

		// Simulate LocalSettings.php adding a custom connection.
		$GLOBALS[$key] = [
			'my.custom.db' => [
				'read'  => DB_REPLICA,
				'write' => DB_PRIMARY,
			],
		];

		try {
			ConfigBootstrap::seedComputedDefaults();

			// User connection survived.
			$this->assertArrayHasKey( 'my.custom.db', $GLOBALS[$key] );
			// Default connections were filled in.
			$this->assertArrayHasKey( 'mw.db', $GLOBALS[$key] );
			$this->assertArrayHasKey( 'mw.db.queryengine', $GLOBALS[$key] );
			$this->assertArrayHasKey( 'read', $GLOBALS[$key]['mw.db'] );
			$this->assertArrayHasKey( 'write', $GLOBALS[$key]['mw.db'] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	/**
	 * smwgNamespacesWithSemanticLinks — ConfigBootstrap array_plus (manual)
	 *
	 * Direct reproducer for #6726: user sets a custom namespace to true; the
	 * standard NS_MAIN / NS_USER / etc. entries must survive.
	 */
	public function testNamespacesWithSemanticLinksPartialWrite(): void {
		$key    = 'smwgNamespacesWithSemanticLinks';
		$backup = $GLOBALS[$key] ?? null;

		// Simulate LocalSettings.php: $smwgNamespacesWithSemanticLinks[NS_AUTHORITY] = true
		// where NS_AUTHORITY = 1234 (a custom namespace).
		$GLOBALS[$key] = [ 1234 => true ];

		try {
			ConfigBootstrap::seedComputedDefaults();

			// User value won.
			$this->assertTrue( $GLOBALS[$key][1234] );
			// Standard MW namespace defaults survive.
			$this->assertTrue( $GLOBALS[$key][NS_MAIN] );
			$this->assertFalse( $GLOBALS[$key][NS_TALK] );
			$this->assertTrue( $GLOBALS[$key][NS_USER] );
			$this->assertTrue( $GLOBALS[$key][NS_CATEGORY] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	/**
	 * smwgEntityCacheSizes — ConfigBootstrap array_plus (manual)
	 * User overrides 'entity.lookup' pool size; all other pools must survive
	 * with their default sizes.
	 */
	public function testEntityCacheSizesPartialWrite(): void {
		$key    = 'smwgEntityCacheSizes';
		$backup = $GLOBALS[$key] ?? null;

		// Simulate LocalSettings.php: $smwgEntityCacheSizes['entity.lookup'] = 5000
		$GLOBALS[$key] = [ 'entity.lookup' => 5000 ];

		try {
			ConfigBootstrap::seedComputedDefaults();

			// User value won.
			$this->assertSame( 5000, $GLOBALS[$key]['entity.lookup'] );
			// Peer pools survive.
			$this->assertArrayHasKey( 'entity.id', $GLOBALS[$key] );
			$this->assertArrayHasKey( 'entity.sort', $GLOBALS[$key] );
			$this->assertArrayHasKey( 'propertytable.hash', $GLOBALS[$key] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	/**
	 * smwgElasticsearchConfig — ConfigBootstrap array_replace_recursive (manual)
	 *
	 * Direct reproducer for #6649: user sets
	 * $smwgElasticsearchConfig['query']['highlight.fragment']['type'] = 'plain';
	 * the 'index_def.data' and 'index_def.lookup' paths must survive, and peer
	 * keys within 'query.highlight.fragment' must also survive.
	 */
	public function testElasticsearchConfigPartialWrite(): void {
		$key    = 'smwgElasticsearchConfig';
		$backup = $GLOBALS[$key] ?? null;

		// Simulate the exact LocalSettings.php pattern from the #6649 ticket.
		$GLOBALS[$key] = [
			'query' => [
				'highlight.fragment' => [
					'type' => 'plain',
				],
			],
		];

		try {
			ConfigBootstrap::seedComputedDefaults();

			// User value won.
			$this->assertSame( 'plain', $GLOBALS[$key]['query']['highlight.fragment']['type'] );
			// Sibling highlight.fragment keys survive.
			$this->assertArrayHasKey( 'number', $GLOBALS[$key]['query']['highlight.fragment'] );
			$this->assertArrayHasKey( 'size', $GLOBALS[$key]['query']['highlight.fragment'] );
			// Unrelated top-level sections survive.
			$this->assertArrayHasKey( 'index_def', $GLOBALS[$key] );
			$this->assertArrayHasKey( 'data', $GLOBALS[$key]['index_def'] );
			$this->assertArrayHasKey( 'lookup', $GLOBALS[$key]['index_def'] );
			$this->assertArrayHasKey( 'connection', $GLOBALS[$key] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

}
