<?php

namespace SMW\MediaWiki\Permission;

use MediaWiki\Title\Title;
use MediaWiki\User\User;
use SMW\DataValues\AllowsPatternValue;
use SMW\MediaWiki\PermissionManager;
use SMW\Protection\ProtectionValidator;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class TitlePermissions {

	private array $errors = [];

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly ProtectionValidator $protectionValidator,
		private readonly PermissionManager $permissionManager,
	) {
	}

	/**
	 * @since 3.1
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 */
	public function checkPermissionFor( Title $title, User $user, string $action ): bool {
		return $this->hasUserPermission( $title, $user, $action );
	}

	/**
	 * @since 2.4
	 */
	public function hasUserPermission( Title $title, User $user, string $action ): bool {
		$this->errors = [];

		if ( $title->getNamespace() === SMW_NS_SCHEMA ) {
			return $this->checkSchemaNamespacePermission( $title, $user, $action );
		}

		$actions = [ 'edit', 'delete', 'move', 'upload' ];

		if ( !in_array( $action, $actions ) ) {
			return true;
		}

		if ( $title->getNamespace() === NS_MEDIAWIKI ) {
			return $this->checkMwNamespacePatternEditPermission( $title, $user );
		}

		if ( $this->protectionValidator->getCreateProtectionRight() && $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->checkPropertyNamespaceCreatePermission( $title, $user );
		}

		if ( $title->getNamespace() === NS_CATEGORY ) {
			return $this->checkChangePropagationProtection( $title );
		}

		if ( !$title->exists() ) {
			return true;
		}

		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			return $this->checkPropertyNamespaceEditPermission( $title, $user );
		}

		if ( $this->protectionValidator->hasEditProtectionOnNamespace( $title ) ) {
			return $this->checkEditPermission( $title, $user );
		}

		return true;
	}

	private function checkMwNamespacePatternEditPermission( Title $title, User $user ): bool {
		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern
		if (
			$title->getDBKey() !== AllowsPatternValue::REFERENCE_PAGE_ID ||
			$this->permissionManager->userHasRight( $user, 'smw-patternedit' ) ) {
			return true;
		}

		$this->errors[] = [ 'smw-patternedit-protection', 'smw-patternedit' ];

		return false;
	}

	private function checkSchemaNamespacePermission( Title $title, User $user, $action ): bool {
		if ( !$this->permissionManager->userHasRight( $user, 'smw-schemaedit' ) ) {
			$this->errors[] = [ 'smw-schema-namespace-edit-protection', 'smw-schemaedit' ];
			return false;
		}

		// Disallow to change the content model
		if ( $action === 'editcontentmodel' ) {
			$this->errors[] = [ 'smw-schema-namespace-editcontentmodel-disallowed' ];
			return false;
		}

		if ( $action === 'edit' && $this->protectionValidator->isClassifiedAsImportPerformerProtected( $title, $user ) ) {
			$this->errors[] = [ 'smw-schema-namespace-edit-protection-by-import-performer' ];
			return false;
		}

		return true;
	}

	private function checkPropertyNamespaceCreatePermission( Title $title, User $user ): bool {
		$protectionRight = $this->protectionValidator->getCreateProtectionRight();

		if ( $protectionRight === false ) {
			$protectionRight = $this->protectionValidator->getEditProtectionRight();
		}

		if ( $this->permissionManager->userHasRight( $user, $protectionRight ) ) {
			return $this->checkPropertyNamespaceEditPermission( $title, $user );
		}

		$msg = 'smw-create-protection';

		if ( $title->exists() ) {
			$msg = 'smw-create-protection-exists';
		}

		$this->errors[] = [ $msg, $title->getText(), $protectionRight ];

		return false;
	}

	private function checkPropertyNamespaceEditPermission( Title $title, User $user ): bool {
		// This renders full protection until the ChangePropagationDispatchJob was run
		if ( !$this->protectionValidator->hasChangePropagationProtection( $title ) ) {
			return $this->checkEditPermission( $title, $user );
		}

		$this->errors[] = [ 'smw-change-propagation-protection' ];

		return false;
	}

	private function checkChangePropagationProtection( Title $title ): bool {
		// This renders full protection until the ChangePropagationDispatchJob was run
		if ( !$this->protectionValidator->hasChangePropagationProtection( $title ) ) {
			return true;
		}

		$this->errors[] = [ 'smw-change-propagation-protection' ];

		return false;
	}

	private function checkEditPermission( Title $title, User $user ): bool {
		$editProtectionRight = $this->protectionValidator->getEditProtectionRight();

		// @see https://www.semantic-mediawiki.org/wiki/Help:Special_property_Is_edit_protected
		if (
			!$this->protectionValidator->hasProtection( $title ) ||
			$this->permissionManager->userHasRight( $user, $editProtectionRight ) ) {
			return true;
		}

		$this->errors[] = [ 'smw-edit-protection', $editProtectionRight ];

		return false;
	}

}
