<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ProfileSourceDefinitionConflictException extends RuntimeException {

	/**
	 * @since 3.2
	 *
	 * @param string $name
	 * @param string $sourceOne
	 * @param string $sourceTwo
	 */
	public function __construct( string $name, string $sourceOne, string $sourceTwo ) {
		parent::__construct(
			"Found competing profiles with the same name! (`{$name}_profile`: $sourceOne, $sourceTwo )"
		);
	}

}
