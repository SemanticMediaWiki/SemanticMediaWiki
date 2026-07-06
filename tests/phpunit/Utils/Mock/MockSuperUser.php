<?php

namespace SMW\Tests\Utils\Mock;

use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\User\User;

/**
 * Instantiate a SuperUser in order to be able to do everything
 *
 * @since 1.9
 *
 * @file
 *
 * @license GPL-2.0-or-later
 */

/**
 * Instantiate a SuperUser in order to be able to do everything.
 * Borrowed from Translate/EducationProgram extension :-)
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 */
class MockSuperUser extends User {

	public function getId( $wikiId = self::LOCAL ): int {
		return 666;
	}

	public function getName(): string {
		return 'SuperUser';
	}

	public function isAllowed( string $permission, ?PermissionStatus $status = null ): bool {
		return true;
	}
}
