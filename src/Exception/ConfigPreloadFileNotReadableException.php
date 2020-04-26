<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloadFileNotReadableException extends RuntimeException {

	/**
	 * @since  3.2
	 *
	 * @param string $file
	 */
	public function __construct( string $file ) {

		$profile = pathinfo( $file, PATHINFO_BASENAME );

		parent::__construct(
			"The \"$profile\" profile is unknown, missing, or might contain a misspelling.\n\n" .
			"Semantic MediaWiki is currently unable to locate and load the profile from $file."
		);
	}

}
