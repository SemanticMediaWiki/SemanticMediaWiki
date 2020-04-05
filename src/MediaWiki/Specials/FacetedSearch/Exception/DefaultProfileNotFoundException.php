<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DefaultProfileNotFoundException extends RuntimeException {

	/**
	 * @since  3.2
	 */
	public function __construct() {
		parent::__construct(
			"The Faceted Search is missing a default profile! The default profile is expected " .
			"to be imported but somehow it went missing. Please read the documentation and avoid " .
			"using `--skip-import` during the setup!"
		);
	}

}
