<?php

namespace SMW\Tests\Util\Mock;

use User;

/**
 * Instantiate a SuperUser in order to be able to do everything
 *
 * @since 1.9
 *
 * @file
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 */

/**
 * Instantiate a SuperUser in order to be able to do everything.
 * Borrowed from Translate/EducationProgram extension :-)
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 */
class MockSuperUser extends User {
	public function getId() {
		return 666;
	}

	public function getName() {
		return 'SuperUser';
	}

	public function isAllowed( $right = '' ) {
		return true;
	}
}
