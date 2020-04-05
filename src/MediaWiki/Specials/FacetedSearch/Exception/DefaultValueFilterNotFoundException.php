<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
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
