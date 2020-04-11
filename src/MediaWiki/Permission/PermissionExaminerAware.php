<?php

namespace SMW\MediaWiki\Permission;

/**
 * @license GNU GPL v2
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
