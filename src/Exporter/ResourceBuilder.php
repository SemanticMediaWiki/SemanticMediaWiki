<?php

namespace SMW\Exporter;

use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
interface ResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function isResourceBuilderFor( DIProperty $property );

	/**
	 * @since 2.5
	 *
	 * @param ExpData $expData
	 * @param DIProperty $property
	 * @param DataItem $dataItem
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem );

}
