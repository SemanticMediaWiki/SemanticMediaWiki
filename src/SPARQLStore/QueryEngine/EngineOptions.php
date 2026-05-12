<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\Options;
use SMW\Services\ServicesFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptions extends Options {

	/**
	 * @since 2.2
	 */
	public function __construct() {
		// Read $smwgQSortFeatures and $smwgSparqlQFeatures via Settings (not
		// $GLOBALS directly) so they go through LegacyConstantNormalizer's
		// array-of-strings normalization (#6586). The other three keys are
		// scalars unaffected by the migration.
		$settings = ServicesFactory::getInstance()->getSettings();
		parent::__construct( [
			'smwgIgnoreQueryErrors'   => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSortFeatures'       => $settings->get( 'smwgQSortFeatures' ),
			'smwgQSubpropertyDepth'   => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgQSubcategoryDepth'   => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgSparqlQFeatures'     => $settings->get( 'smwgSparqlQFeatures' )
		] );
	}

}
