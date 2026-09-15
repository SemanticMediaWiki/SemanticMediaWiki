<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 7.3.0
 */
class ConfigPreloadFileAlreadyLoadedException extends RuntimeException {

	/**
	 * @since 7.3.0
	 */
	public function __construct( string $file ) {
		$profile = pathinfo( $file, PATHINFO_BASENAME );

		parent::__construct(
			"The \"$profile\" profile was loaded before Semantic MediaWiki could apply it, " .
			"for example by a `require` in LocalSettings.php.\n\n" .
			'Remove that `require` and list the profile in `$smwgConfigProfiles` instead.'
		);
	}

}
