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
	 * A single resource (individual) for export, as defined by a URI.
	 */
	const TYPE_RESOURCE = 0;

	/**
	 * A single resource (individual) for export, defined by a URI for which there
	 * also is a namespace abbreviation.
	 */
	const TYPE_NSRESOURCE = 1;

	/**
	 * A single datatype literal for export. Defined by a literal value and a
	 * datatype URI.
	 */
	const TYPE_LITERAL = 2;

	/**
	 * A dataItem an export element is associated with
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
