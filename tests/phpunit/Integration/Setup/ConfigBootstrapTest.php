<?php

namespace SMW\Tests\Integration\Setup;

use PHPUnit\Framework\TestCase;
use SMW\Setup\ConfigBootstrap;

/**
 * @covers \SMW\Setup\ConfigBootstrap
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigBootstrapTest extends TestCase {

	public function testUnsetGlobalGetsSeededDefault(): void {
		$key    = 'smwgQFeatures';
		$backup = $GLOBALS[$key] ?? null;
		unset( $GLOBALS[$key] );

		try {
			ConfigBootstrap::seedComputedDefaults();
			$this->assertSame(
				SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY
					| SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY,
				$GLOBALS[$key]
			);
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	public function testUserPresetValueIsPreserved(): void {
		$key    = 'smwgShowFactbox';
		$backup = $GLOBALS[$key] ?? null;
		$GLOBALS[$key] = SMW_FACTBOX_SHOWN;

		try {
			ConfigBootstrap::seedComputedDefaults();
			$this->assertSame( SMW_FACTBOX_SHOWN, $GLOBALS[$key] );
			$this->assertNotSame( SMW_FACTBOX_HIDDEN, $GLOBALS[$key] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	public function testSmwNamespacesWithSemanticLinksSeedsSmwNsKeys(): void {
		// Regression for #6772: the SMW NS defaults must be seeded at
		// registration time, not in the CanonicalNamespaces hook. The hook
		// fires on first Title resolution, which happens after
		// Settings::loadFromGlobals() is called inside wgExtensionFunctions,
		// so a hook-only seed leaves Settings holding a stale snapshot and
		// every Property page emits smw-property-namespace-disabled.
		$key    = 'smwgNamespacesWithSemanticLinks';
		$backup = $GLOBALS[$key] ?? null;
		unset( $GLOBALS[$key] );

		try {
			ConfigBootstrap::seedComputedDefaults();
			$this->assertSame( true,  $GLOBALS[$key][SMW_NS_PROPERTY] );
			$this->assertSame( false, $GLOBALS[$key][SMW_NS_PROPERTY_TALK] );
			$this->assertSame( true,  $GLOBALS[$key][SMW_NS_CONCEPT] );
			$this->assertSame( false, $GLOBALS[$key][SMW_NS_CONCEPT_TALK] );
			$this->assertSame( true,  $GLOBALS[$key][SMW_NS_SCHEMA] );
			$this->assertSame( false, $GLOBALS[$key][SMW_NS_SCHEMA_TALK] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	public function testSmwNamespacesWithSemanticLinksUserOverridePreserved(): void {
		// array_plus semantics: user keys win over defaults.
		$key    = 'smwgNamespacesWithSemanticLinks';
		$backup = $GLOBALS[$key] ?? null;
		$GLOBALS[$key] = [ SMW_NS_PROPERTY => false ];

		try {
			ConfigBootstrap::seedComputedDefaults();
			$this->assertFalse( $GLOBALS[$key][SMW_NS_PROPERTY] );
			$this->assertTrue( $GLOBALS[$key][SMW_NS_CONCEPT] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

	public function testFalseyButPresentValueIsPreserved(): void {
		// Sets the user value to literal 0 — falsey, but `isset()` returns
		// true. A buggy `if ( !$GLOBALS[$key] )` guard would overwrite the
		// 0 with the non-zero default; the correct `!isset()` guard
		// preserves it. Without this test, that regression would slip past
		// `testUserPresetValueIsPreserved` (which uses a truthy non-default).
		$key    = 'smwgFactboxFeatures';
		$backup = $GLOBALS[$key] ?? null;
		$GLOBALS[$key] = 0;

		try {
			ConfigBootstrap::seedComputedDefaults();
			$this->assertSame( 0, $GLOBALS[$key] );
		} finally {
			$GLOBALS[$key] = $backup;
		}
	}

}
