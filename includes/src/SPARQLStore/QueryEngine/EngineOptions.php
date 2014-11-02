<?php

namespace SMW\SPARQLStore\QueryEngine;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EngineOptions {

	/**
	 * @var boolean
	 */
	public $ignoreQueryErrors = false;

	/**
	 * @var boolean
	 */
	public $sortingSupport = true;

	/**
	 * @var boolean
	 */
	public $randomSortingSupport = true;

	/**
	 * @since  2.1
	 */
	public function __construct() {
		$this->ignoreQueryErrors = $GLOBALS['smwgIgnoreQueryErrors'];
		$this->sortingSupport = $GLOBALS['smwgQSortingSupport'];
		$this->randomSortingSupport = $GLOBALS['smwgQRandSortingSupport'];
	}

}
