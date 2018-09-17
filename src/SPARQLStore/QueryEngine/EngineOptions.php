<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\Options;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptions extends Options {

	/**
	 * @since 2.2
	 */
	public function __construct() {
		parent::__construct( [
			'smwgIgnoreQueryErrors'   => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSortFeatures'       => $GLOBALS['smwgQSortFeatures'],
			'smwgQSubpropertyDepth'   => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgQSubcategoryDepth'   => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgSparqlQFeatures'     => $GLOBALS['smwgSparqlQFeatures']
		] );
	}

}
