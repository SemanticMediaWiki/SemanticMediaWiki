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

	/**
	 * @since  2.5
	 *
	 * {@inheritDoc}
	 */
	public function __construct( $permission, $errors = [] ) {
		parent::__construct( $permission, [] );

		// Push SMW specific messages to appear first, PermissionsError will
		// generate a list of required permissions
		array_unshift( $this->errors, $errors );
	}

}
