<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Options;

/**
 * @license GNU GPL v2+
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
			'smwgIgnoreQueryErrors'   => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSortFeatures'     => $GLOBALS['smwgQSortFeatures'],
			'smwgQFilterDuplicates'   => $GLOBALS['smwgQFilterDuplicates']
		] );
	}

}
