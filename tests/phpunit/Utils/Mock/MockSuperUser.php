<?php

namespace SMW\Tests\Utils\Mock;

use User;

/**
 * Instantiate a SuperUser in order to be able to do everything
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
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
	# The signature is "getId()" in MW 1.35-
	# and "getId( $wikiId = self::LOCAL ) : int" in MW 1.36
	# TODO: when SMW will only support MW 1.36+, the new signature can be fixed
	public function getId( $wikiId = false ) : int {
		return 666;
	}

	public function getName() : string {
		return 'SuperUser';
	}

	# The signature is "isAllowed( $action = '' )" in MW 1.35-
	# and "isAllowed( string $permission ) : bool" in MW 1.36
	# The following signature does not emit warnings in any cases
	# TODO: when SMW will only support MW 1.36+, the new signature can be fixed
	public function isAllowed( $permission = '' ) : bool {
		return true;
	}
}
