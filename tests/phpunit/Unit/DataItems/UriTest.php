<?php

namespace SMW\Tests\Unit\DataItems;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Uri;

/**
 * @covers \SMW\DataItems\Uri
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.3.0
 */
class UriTest extends TestCase {

	public function testSortKeyKeepsPlusSignAsPlus(): void {
		$uri = new Uri( 'http', 'example.org/s', 'q=1+2', '' );

		$this->assertSame(
			'http://example.org/s?q=1+2',
			$uri->getSortKey()
		);
	}

	public function testSortKeyDecodesPercentEncoding(): void {
		$uri = new Uri( 'http', 'example.org/a%2Fb', '', '' );

		$this->assertSame(
			'http://example.org/a/b',
			$uri->getSortKey()
		);
	}

}
