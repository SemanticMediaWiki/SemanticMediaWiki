<?php

namespace SMW\Tests\MediaWiki\Exception;

use SMW\MediaWiki\Exception\ExtendedPermissionsError;

/**
 * @covers \SMW\MediaWiki\Exception\ExtendedPermissionsError
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExtendedPermissionsErrorTest extends \PHPUnit_Framework_TestCase {

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
