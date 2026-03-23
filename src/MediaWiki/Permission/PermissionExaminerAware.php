<?php

namespace SMW\MediaWiki\Permission;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
interface PermissionExaminerAware {

	/**
	 * @since 3.2
	 *
	 * @param PermissionExaminer $permissionExaminer
	 */
	public function setPermissionExaminer( PermissionExaminer $permissionExaminer );

}
