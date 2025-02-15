<?php

namespace SMW\MediaWiki;

use MediaWiki\Permissions\PermissionManager as MwPermissionManager;
use RequestContext;
use Title;
use User;

/**
 * @license GPL-2.0-or-later
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
	 * @param string $rigor One of the PermissionManager::RIGOR_* constants
	 *
	 * @return bool
	 */
	public function userCan(
		string $action,
		?User $user,
		Title $title,
		string $rigor = MwPermissionManager::RIGOR_SECURE
	): bool {
		if ( !$user instanceof User ) {
			$user = RequestContext::getMain()->getUser();
		}

		return $this->permissionManager->userCan( $action, $user, $title, $rigor );
	}

	/**
	 * @since 3.2
	 *
	 * @param User $user
	 * @param string $action
	 *
	 * @return bool
	 */
	public function userHasRight( User $user, string $action = '' ): bool {
		return $this->permissionManager->userHasRight( $user, $action );
	}

}
