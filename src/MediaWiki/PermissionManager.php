<?php

namespace SMW\MediaWiki;

use SMW\DataValues\AllowsPatternValue;
use SMW\Protection\ProtectionValidator;
use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PermissionManager {

	/**
	 * @var ProtectionValidator
	 */
	private $protectionValidator;

	/**
	 * @since 2.5
	 *
	 * @param ProtectionValidator $protectionValidator
	 */
	public function __construct( ProtectionValidator $protectionValidator ) {
		$this->protectionValidator = $protectionValidator;
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
	public function checkQuickPermission( Title &$title, User $user, $action, &$errors ) {
		return $this->hasUserPermission( $title, $user, $action, $errors );
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
	public function hasUserPermission( Title &$title, User $user, $action, &$errors ) {

		if ( $title->getNamespace() === SMW_NS_SCHEMA ) {
			return $this->checkSchemaNamespacePermission( $title, $user, $action, $errors );
		}

		if ( $action !== 'edit' && $action !== 'delete' && $action !== 'move' && $action !== 'upload' ) {
			return true;
		}

		if ( $title->getNamespace() === NS_MEDIAWIKI ) {
			return $this->checkMwNamespacePatternEditPermission( $title, $user, $action, $errors );
		}

		if ( $this->protectionValidator->getCreateProtectionRight() && $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->checkPropertyNamespaceCreatePermission( $title, $user, $action, $errors );
		}

		if ( $title->getNamespace() === NS_CATEGORY ) {
			return $this->checkChangePropagationProtection( $title, $user, $action, $errors );
		}

		if ( !$title->exists() ) {
			return true;
		}

		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->checkPropertyNamespaceEditPermission( $title, $user, $action, $errors );
		}

		if ( $this->protectionValidator->hasEditProtectionOnNamespace( $title ) ) {
			return $this->checkEditPermissionOn( $title, $user, $action, $errors );
		}

		return true;
	}

	private function checkMwNamespacePatternEditPermission( Title &$title, User $user, $action, &$errors ) {

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern
		if ( $title->getDBKey() !== AllowsPatternValue::REFERENCE_PAGE_ID || $user->isAllowed( 'smw-patternedit' ) ) {
			return true;
		}

		$errors[] = [ 'smw-patternedit-protection', 'smw-patternedit' ];

		return false;
	}

	private function checkSchemaNamespacePermission( Title &$title, User $user, $action, &$errors ) {

		if ( !$user->isAllowed( 'smw-schemaedit' ) ) {
			$errors[] = [ 'smw-schema-namespace-edit-protection', 'smw-schemaedit' ];
			return false;
		}

		// Disallow to change the content model
		if ( $action === 'editcontentmodel' ) {
			$errors[] = [ 'smw-schema-namespace-editcontentmodel-disallowed' ];
			return false;
		}

		return true;
	}

	private function checkPropertyNamespaceCreatePermission( Title &$title, User $user, $action, &$errors ) {

		$createProtectionRight = $this->protectionValidator->getCreateProtectionRight();

		if ( $user->isAllowed( $createProtectionRight ) ) {
			return $this->checkPropertyNamespaceEditPermission( $title, $user, $action, $errors );;
		}

		$msg = 'smw-create-protection';

		if ( $title->exists() ) {
			$msg = 'smw-create-protection-exists';
		}

		$errors[] = [ $msg, $title->getText(), $createProtectionRight ];

		return false;
	}

	private function checkPropertyNamespaceEditPermission( Title &$title, User $user, $action, &$errors ) {

		// This renders full protection until the ChangePropagationDispatchJob was run
		if ( !$this->protectionValidator->hasChangePropagationProtection( $title ) ) {
			return $this->checkEditPermissionOn( $title, $user, $action, $errors );
		}

		$errors[] = [ 'smw-change-propagation-protection' ];

		return false;
	}

	private function checkChangePropagationProtection( Title &$title, User $user, $action, &$errors ) {

		// This renders full protection until the ChangePropagationDispatchJob was run
		if ( !$this->protectionValidator->hasChangePropagationProtection( $title ) ) {
			return true;
		}

		$errors[] = [ 'smw-change-propagation-protection' ];

		return false;
	}

	private function checkEditPermissionOn( Title &$title, User $user, $action, &$errors ) {

		$editProtectionRight = $this->protectionValidator->getEditProtectionRight();

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Is_edit_protected
		if ( !$this->protectionValidator->hasProtection( $title ) || $user->isAllowed( $editProtectionRight ) ) {
			return true;
		}

		$errors[] = [ 'smw-edit-protection', $editProtectionRight ];

		return false;
	}

}
