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
	 * The dataItem that is associated with an export element
	 *
	 * @since 2.2
	 *
	 * @return DataItem|null
	 */
	public function getDataItem();

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash();

}
