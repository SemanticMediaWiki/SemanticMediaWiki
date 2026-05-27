<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\SiteReadiness;

/**
 * @covers \SMW\SiteReadiness
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SiteReadinessTest extends TestCase {

	public function testCanConstruct(): void {
		$this->assertInstanceOf(
			SiteReadiness::class,
			new SiteReadiness()
		);
	}

	public function testIsReadyDelegatesToSite(): void {
		// The `$GLOBALS` mutation is intentional in this single test: it
		// verifies the wrapper's delegation contract to `Site::isReady()`.
		// All other call sites should inject this wrapper as a mock instead.
		$wasSet = array_key_exists( 'wgFullyInitialised', $GLOBALS );
		$wasReady = $GLOBALS['wgFullyInitialised'] ?? null;
		$GLOBALS['wgFullyInitialised'] = true;

		try {
			$this->assertTrue( ( new SiteReadiness() )->isReady() );

			$GLOBALS['wgFullyInitialised'] = false;
			$this->assertFalse( ( new SiteReadiness() )->isReady() );
		} finally {
			if ( $wasSet ) {
				$GLOBALS['wgFullyInitialised'] = $wasReady;
			} else {
				unset( $GLOBALS['wgFullyInitialised'] );
			}
		}
	}

}
