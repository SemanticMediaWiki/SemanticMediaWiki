<?php

namespace SMW\Exporter;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\Export\ExpData;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
interface ResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public function isResourceBuilderFor( Property $property );

	/**
	 * @since 2.5
	 *
	 * @param ExpData $expData
	 * @param Property $property
	 * @param DataItem $dataItem
	 */
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem );

}
