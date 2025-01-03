<?php

namespace SMW\MediaWiki\Exception;

use PermissionsError;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExtendedPermissionsError extends PermissionsError {

	// Used on MW 1.43+
	private array $errorArray = [];

	/**
	 * @since  2.5
	 *
	 * {@inheritDoc}
	 */
	public function __construct( $permission, $errors = [] ) {
		parent::__construct( $permission, [] );

		if ( version_compare( MW_VERSION, '1.43', '>=' ) ) {
			foreach ( $this->status->getMessages() as $msg ) {
				$this->errorArray[] = $msg->getKey();
			}
			
			// Push SMW specific messages to appear first, PermissionsError will
			// generate a list of required permissions
			array_unshift( $this->errorArray, $errors );

			$this->status->fatal( ...$this->errorArray );
		} else {
			// Push SMW specific messages to appear first, PermissionsError will
			// generate a list of required permissions
			array_unshift( $this->errors, $errors );
		}
	}
}
