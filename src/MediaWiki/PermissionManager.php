<?php

namespace SMW\MediaWiki;

use MediaWiki\Permissions\PermissionManager as MwPermissionManager;
use RequestContext;
use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PermissionManager {

	/**
	 * @var MwPermissionManager
	 */
	private $permissionManager;

	/**
	 * @since 3.2
	 *
	 * @param MwPermissionManager $permissionManager
	 */
	public function __construct( MwPermissionManager $permissionManager ) {
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
		if ( !$user instanceof User ) {
			$user = RequestContext::getMain()->getUser();			
		}

		return $this->permissionManager->userCan( $action, $user, $title );
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
		return $this->permissionManager->userHasRight( $user, $action );
	}

}
