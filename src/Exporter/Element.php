<?php

namespace SMW\Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
interface Element {

	/**
	 * A dataItem an export element is associated with
	 *
	 * @since 2.2
	 *
	 * @return DataItem|null
	 */
	public function getDataItem();

}
