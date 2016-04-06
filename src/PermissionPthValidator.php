<?php

namespace SMW;

use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PermissionPthValidator {

	/**
	 * @since 2.4
	 *
	 * @param Title &$title
	 * @param User $user
	 * @param string $action
	 * @param boolean &$result
	 *
	 * @return boolean
	 */
	public function checkUserCanPermissionFor( Title &$title, User $user, $action, &$result ) {

		if ( $title->getNamespace() === NS_MEDIAWIKI &&
			 !$this->checkMwNamespaceEditPermission( $title, $user, $action, $result ) ) {
			return false;
		}

		return true;
	}

	private function checkMwNamespaceEditPermission( Title &$title, User $user, $action, &$result ) {

		if ( $action !== 'edit' ) {
			return true;
		}

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern
		// User should not be allowed to proceed and later functions (chain hook
		// execution) are not consulted
		if ( $title->getDBKey() === 'Smw_allows_pattern' ) {
			$result = false;
			return $user->isAllowed( 'smw-patternedit' );
		}

		return true;
	}

}
