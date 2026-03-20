<?php

namespace SMW\MediaWiki\Permission;

/**
 * Describes an instance that is aware of a role or group permission on a user or
 * actor.
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
interface PermissionAware {

	/**
	 * @since 3.2
	 *
	 * @param PermissionExaminer $permissionExaminer
	 *
	 * @return bool
	 */
	public function hasPermission( PermissionExaminer $permissionExaminer ): bool;

}
