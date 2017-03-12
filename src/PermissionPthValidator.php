<?php

namespace SMW;

use Title;
use User;
use SMW\Protection\EditProtectionValidator;
use SMW\DataValues\AllowsPatternValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PermissionPthValidator {

	/**
	 * @var EditProtectionValidator
	 */
	private $editProtectionValidator;

	/**
	 * @var string|false
	 */
	private $editProtectionRight = false;

	/**
	 * @since 2.5
	 *
	 * @param EditProtectionValidator $editProtectionValidator
	 */
	public function __construct( EditProtectionValidator $editProtectionValidator ) {
		$this->editProtectionValidator = $editProtectionValidator;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|false $editProtectionRight
	 */
	public function setEditProtectionRight( $editProtectionRight ) {
		$this->editProtectionRight = $editProtectionRight;
	}

	/**
	 * @since 2.5
	 *
	 * @param Title &$title
	 * @param User $user
	 * @param string $action
	 * @param array &$errors
	 *
	 * @return boolean
	 */
	public function checkQuickPermissionOn( Title &$title, User $user, $action, &$errors ) {
		return $this->checkUserPermissionOn( $title, $user, $action, $errors );
	}

	/**
	 * @since 2.4
	 *
	 * @param Title &$title
	 * @param User $user
	 * @param string $action
	 * @param array &$errors
	 *
	 * @return boolean
	 */
	public function checkUserPermissionOn( Title &$title, User $user, $action, &$errors ) {

		if ( $action !== 'edit' && $action !== 'delete' && $action !== 'move' && $action !== 'upload' ) {
			return true;
		}

		if ( $title->getNamespace() === NS_MEDIAWIKI ) {
			return $this->checkMwNamespaceEditPermission( $title, $user, $action, $errors );
		}

		if ( !$title->exists() ) {
			return true;
		}

		if ( $this->editProtectionRight && $this->editProtectionValidator->hasProtectionOnNamespace( $title ) ) {
			return $this->checkPermissionOn( $title, $user, $action, $errors );
		}

		return true;
	}

	private function checkMwNamespaceEditPermission( Title &$title, User $user, $action, &$errors ) {

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern
		if ( $title->getDBKey() !== AllowsPatternValue::REFERENCE_PAGE_ID || $user->isAllowed( 'smw-patternedit' ) ) {
			return true;
		}

		$errors[] = array( 'smw-patternedit-protection', 'smw-patternedit' );

		return false;
	}

	private function checkPermissionOn( Title &$title, User $user, $action, &$errors ) {

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Is_edit_protected
		if ( !$this->editProtectionValidator->hasProtection( $title ) || $user->isAllowed( $this->editProtectionRight ) ) {
			return true;
		}

		$errors[] = array( 'smw-edit-protection', $this->editProtectionRight );

		return false;
	}

}
