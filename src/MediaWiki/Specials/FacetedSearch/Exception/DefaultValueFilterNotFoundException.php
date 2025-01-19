<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DefaultValueFilterNotFoundException extends RuntimeException {

	/**
	 * @since 3.2
	 *
	 * @param string $property
	 */
	public function __construct( string $property ) {
		parent::__construct( "No default value filter matched to $property!" );
	}

}
