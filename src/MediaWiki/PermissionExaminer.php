<?php

namespace SMW\MediaWiki;

use MediaWiki\Permissions\PermissionManager;
use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PermissionExaminer {

	/**
	 * @var PermissionManager|null
	 */
	private $permissionManager;

	/**
	 * @since 3.2
	 *
	 * @param PermissionManager|null $permissionManager
	 */
	public function __construct( PermissionManager $permissionManager = null ) {
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $action
	 * @param User|null $user
	 * @param Title $title
	 *
	 * @return bool
	 */
	public function userCan( string $action, User $user = null, Title $title ) : bool {

		// @see Title::userCan
		if ( !$user instanceof User ) {
			$user = $GLOBALS['wgUser'];
		}

		if ( $this->permissionManager !== null ) {
			return $this->permissionManager->userCan( $action, $user, $title );
		}

		return $title->userCan( $action, $user );
	}

	/**
	 * @since 3.2
	 *
	 * @param User $user
	 * @param string $action
	 *
	 * @return bool
	 */
	public function userHasRight( User $user, string $action = '' ) : bool {

		if ( $this->permissionManager !== null && method_exists( $this->permissionManager, 'userHasRight' ) ) {
			return $this->permissionManager->userHasRight( $user, $action );
		}

		return $user->isAllowed( $action );
	}

}
