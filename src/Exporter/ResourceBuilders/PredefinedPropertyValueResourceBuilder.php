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
class PredefinedPropertyValueResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return !$property->isUserDefined();
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		$diSubject = $expData->getSubject()->getDataItem();

		if ( $diSubject === null ) {
			return;
		}

		$expNsResource = $this->exporter->getSpecialPropertyResource(
			$property->getKey(),
			$diSubject->getNamespace()
		);

		$expElement = $this->exporter->newExpElement(
			$dataItem
		);

		if ( $expElement === null || $expNsResource === null ) {
			return;
		}

		$expData->addPropertyObjectValue(
			$expNsResource,
			$expElement
		);

		$this->addResourceHelperValue(
			$expData,
			$property,
			$dataItem
		);
	}

}
