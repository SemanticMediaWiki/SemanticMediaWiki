<?php

namespace SMW\MediaWiki\Permission;

use SMW\MediaWiki\PermissionManager;
use User;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PermissionExaminer {

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @since 3.2
	 *
	 * @param PermissionManager $permissionManager
	 * @param User|null $user
	 */
	public function __construct( PermissionManager $permissionManager, User $user = null ) {
		$this->permissionManager = $permissionManager;
		$this->user = $user;
	}

	/**
	 * @since 3.2
	 *
	 * @return User $user
	 */
	public function setUser( User $user ) {
		$this->user = $user;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $right
	 *
	 * @return bool
	 */
	public function hasPermissionOf( string $right ) : bool {

		if ( $this->user === null ) {
			return false;
		}

		return $this->permissionManager->userHasRight( $this->user, $right );
	}

}
