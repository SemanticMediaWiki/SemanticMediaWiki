<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Options;

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
		parent::__construct( [
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSortFeatures' => $GLOBALS['smwgQSortFeatures'],
			'smwgQFilterDuplicates' => $GLOBALS['smwgQFilterDuplicates']
		] );
	}

}
