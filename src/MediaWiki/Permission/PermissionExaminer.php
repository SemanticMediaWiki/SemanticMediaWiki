<?php

namespace SMW\MediaWiki\Permission;

use MediaWiki\User\User;
use SMW\MediaWiki\PermissionManager;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PermissionExaminer {

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly PermissionManager $permissionManager,
		private ?User $user = null,
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @return User $user
	 */
	public function setUser( User $user ): void {
		$this->user = $user;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $right
	 *
	 * @return bool
	 */
	public function hasPermissionOf( string $right ): bool {
		if ( $this->user === null ) {
			return false;
		}

		return $this->permissionManager->userHasRight( $this->user, $right );
	}

}
