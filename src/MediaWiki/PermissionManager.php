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
	 * @var []
	 */
	private $errors = [];

	/**
	 * @since 2.5
	 *
	 * @param ProtectionValidator $protectionValidator
	 */
	public function __construct( ProtectionValidator $protectionValidator ) {
		$this->protectionValidator = $protectionValidator;
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 * @param array &$errors
	 *
	 * @return boolean
	 */
	public function checkQuickPermission( Title $title, User $user, $action, &$errors ) {

		$hasUserPermission = $this->hasUserPermission( $title, $user, $action );

		foreach ( $this->errors as $error ) {
			$errors[] = $error;
		}

		return $hasUserPermission;
	}

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param User $user
	 * @param string $action
	 *
	 * @return boolean
	 */
	public function hasUserPermission( Title $title, User $user, $action ) {

		$this->errors = [];

		if ( $title->getNamespace() === SMW_NS_SCHEMA ) {
			return $this->checkSchemaNamespacePermission( $title, $user, $action );
		}

		$actions = [ 'edit', 'delete', 'move', 'upload' ];

		if ( !in_array( $action, $actions ) ) {
			return true;
		}

		if ( $title->getNamespace() === NS_MEDIAWIKI ) {
			return $this->checkMwNamespacePatternEditPermission( $title, $user, $action );
		}

		if ( $this->protectionValidator->getCreateProtectionRight() && $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->checkPropertyNamespaceCreatePermission( $title, $user, $action );
		}

		if ( $title->getNamespace() === NS_CATEGORY ) {
			return $this->checkChangePropagationProtection( $title, $user, $action );
		}

		if ( !$title->exists() ) {
			return true;
		}

		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->checkPropertyNamespaceEditPermission( $title, $user, $action );
		}

		if ( $this->protectionValidator->hasEditProtectionOnNamespace( $title ) ) {
			return $this->checkEditPermission( $title, $user, $action );
		}

		return true;
	}

	private function checkMwNamespacePatternEditPermission( Title $title, User $user, $action ) {

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern
		if ( $title->getDBKey() !== AllowsPatternValue::REFERENCE_PAGE_ID || $user->isAllowed( 'smw-patternedit' ) ) {
			return true;
		}

		$this->errors[] = [ 'smw-patternedit-protection', 'smw-patternedit' ];

		return false;
	}

	private function checkSchemaNamespacePermission( Title $title, User $user, $action ) {

		if ( !$user->isAllowed( 'smw-schemaedit' ) ) {
			$this->errors[] = [ 'smw-schema-namespace-edit-protection', 'smw-schemaedit' ];
			return false;
		}

		// Disallow to change the content model
		if ( $action === 'editcontentmodel' ) {
			$this->errors[] = [ 'smw-schema-namespace-editcontentmodel-disallowed' ];
			return false;
		}

		return true;
	}

	private function checkPropertyNamespaceCreatePermission( Title $title, User $user, $action ) {

		$protectionRight = $this->protectionValidator->getCreateProtectionRight();

		if ( $protectionRight === false ) {
			$protectionRight = $this->protectionValidator->getEditProtectionRight();
		}

		if ( $user->isAllowed( $protectionRight ) ) {
			return $this->checkPropertyNamespaceEditPermission( $title, $user, $action );
		}

		$msg = 'smw-create-protection';

		if ( $title->exists() ) {
			$msg = 'smw-create-protection-exists';
		}

		$this->errors[] = [ $msg, $title->getText(), $protectionRight ];

		return false;
	}

	private function checkPropertyNamespaceEditPermission( Title $title, User $user, $action ) {

		// This renders full protection until the ChangePropagationDispatchJob was run
		if ( !$this->protectionValidator->hasChangePropagationProtection( $title ) ) {
			return $this->checkEditPermission( $title, $user, $action );
		}

		$this->errors[] = [ 'smw-change-propagation-protection' ];

		return false;
	}

	private function checkChangePropagationProtection( Title $title, User $user, $action ) {

		// This renders full protection until the ChangePropagationDispatchJob was run
		if ( !$this->protectionValidator->hasChangePropagationProtection( $title ) ) {
			return true;
		}

		$this->errors[] = [ 'smw-change-propagation-protection' ];

		return false;
	}

	private function checkEditPermission( Title $title, User $user, $action ) {

		$editProtectionRight = $this->protectionValidator->getEditProtectionRight();

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Is_edit_protected
		if ( !$this->protectionValidator->hasProtection( $title ) || $user->isAllowed( $editProtectionRight ) ) {
			return true;
		}

		$this->errors[] = [ 'smw-edit-protection', $editProtectionRight ];

		return false;
	}

}
