<?php

namespace SMW\Exporter\ResourceBuilders;

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
class RedirectPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_REDI';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		parent::addResourceValue( $expData, $property, $dataItem );

		$expElement = $this->exporter->newExpElement(
			$dataItem
		);

		if ( $expElement === null ) {
			return;
		}

		$expData->addPropertyObjectValue(
			$this->exporter->getSpecialPropertyResource( '_URI' ),
			$expElement
		);
	}

}
