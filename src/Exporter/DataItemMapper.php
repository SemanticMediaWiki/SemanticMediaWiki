<?php

namespace SMW\Exporter;

use SMWDataItem as DataItem;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
interface DataItemMapper {

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	public function isMapperFor( DataItem $dataItem );

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return Element
	 */
	public function newElement( DataItem $dataItem );

}
