<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\User\Hook\UserGroupsChangedHook;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class UserGroupsChanged implements UserGroupsChangedHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly UserChange $userChange ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onUserGroupsChanged( $user, $added, $removed, $performer, $reason, $oldUGMs, $newUGMs ) {
		$this->userChange->notify( 'UserGroupsChanged', $user->getName() );

		return true;
	}

}
