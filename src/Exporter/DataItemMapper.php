<?php

namespace SMW\Exporter;

use SMW\DataItems\DataItem;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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
	 * @return bool
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
