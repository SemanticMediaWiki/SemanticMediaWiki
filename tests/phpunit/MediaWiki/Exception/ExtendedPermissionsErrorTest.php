<?php

namespace SMW\Tests\MediaWiki\Exception;

use SMW\MediaWiki\Exception\ExtendedPermissionsError;

/**
 * @covers \SMW\MediaWiki\Exception\ExtendedPermissionsError
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ExtendedPermissionsErrorTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\Exception\ExtendedPermissionsError',
			new ExtendedPermissionsError( 'Foo' )
		);

		$this->assertInstanceOf(
			'\PermissionsError',
			new ExtendedPermissionsError( 'Foo' )
		);
	}

}
