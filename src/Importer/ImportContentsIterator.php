<?php

namespace SMW\Importer;

use IteratorAggregate;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
interface ImportContentsIterator extends IteratorAggregate {

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getDescription();

}
