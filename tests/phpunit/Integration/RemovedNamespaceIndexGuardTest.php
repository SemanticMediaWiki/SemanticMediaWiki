<?php

namespace SMW\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SMW\Exception\RemovedNamespaceIndexException;
use SMW\SemanticMediaWiki;

/**
 * Integration test for the deprecation guard at the top of
 * `SemanticMediaWiki::initExtension`. The guard must throw
 * `RemovedNamespaceIndexException` when `$GLOBALS['smwgNamespaceIndex']` is
 * still set after upgrade, BEFORE any setup side effects run, so the user
 * sees the migration message instead of silent data orphaning.
 *
 * Lives in the Integration suite (not Unit) because the test exercises the
 * extension entry point. The guard fires on line one and prevents downstream
 * bootstrap from running, but if the guard ever moves later in `initExtension`
 * this test would start touching real bootstrap, so the Integration directory
 * is the honest home.
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class RemovedNamespaceIndexGuardTest extends TestCase {

	public function testInitExtensionThrowsWhenLegacyGlobalIsSet(): void {
		$saved = $GLOBALS['smwgNamespaceIndex'] ?? null;
		$GLOBALS['smwgNamespaceIndex'] = 250;

		try {
			$this->expectException( RemovedNamespaceIndexException::class );
			SemanticMediaWiki::initExtension( [ 'version' => '7.0.0' ] );
		} finally {
			if ( $saved === null ) {
				unset( $GLOBALS['smwgNamespaceIndex'] );
			} else {
				$GLOBALS['smwgNamespaceIndex'] = $saved;
			}
		}
	}
}
