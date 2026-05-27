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
		$wasReady = $GLOBALS['wgFullyInitialised'] ?? false;
		$GLOBALS['wgFullyInitialised'] = true;

		try {
			$this->assertTrue( ( new SiteReadiness() )->isReady() );

			$GLOBALS['wgFullyInitialised'] = false;
			$this->assertFalse( ( new SiteReadiness() )->isReady() );
		} finally {
			$GLOBALS['wgFullyInitialised'] = $wasReady;
		}
	}

}
