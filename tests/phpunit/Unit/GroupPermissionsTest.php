<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\GroupPermissions;

/**
 * @covers \SMW\GroupPermissions
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class GroupPermissionsTest extends TestCase {

	public function testConstants() {
		$this->assertSame( 'smw-viewjobqueuewatchlist', GroupPermissions::VIEW_JOBQUEUE_WATCHLIST );
		$this->assertSame( 'smw-viewentityassociatedrevisionmismatch', GroupPermissions::VIEW_ENTITY_ASSOCIATEDREVISIONMISMATCH );
		$this->assertSame( 'smw-vieweditpageinfo', GroupPermissions::VIEW_EDITPAGE_INFO );
	}

}
