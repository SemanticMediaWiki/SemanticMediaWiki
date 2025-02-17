<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\AdminLinks;

/**
 * @covers \SMW\MediaWiki\Hooks\AdminLinks
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AdminLinksTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AdminLinks::class,
			new AdminLinks()
		);
	}

}
