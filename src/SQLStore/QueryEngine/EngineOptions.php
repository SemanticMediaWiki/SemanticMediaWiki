<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Options;
use SMW\Services\ServicesFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class EngineOptions extends Options {

	/**
	 * @since 2.2
	 */
	public function __construct() {
		// Read $smwgQSortFeatures via Settings (not $GLOBALS directly) so it
		// goes through LegacyConstantNormalizer's array-of-strings normalization
		// (#6586). The other three keys are scalars unaffected by the migration.
		parent::__construct( [
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSortFeatures' => ServicesFactory::getInstance()->getSettings()->get( 'smwgQSortFeatures' ),
			'smwgQFilterDuplicates' => $GLOBALS['smwgQFilterDuplicates'],
			'smwgQUseLegacyQuery' => $GLOBALS['smwgQUseLegacyQuery']
		] );
	}

}
