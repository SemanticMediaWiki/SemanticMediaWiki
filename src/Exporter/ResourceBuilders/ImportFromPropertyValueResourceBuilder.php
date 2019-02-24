<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWExpData as ExpData;
use SMWImportValue as ImportValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ImportFromPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_IMPO';
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


		if ( $expNsResource === null ) {
			return;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		if ( !$dataValue instanceof ImportValue ) {
			return;
		}

		$expData->addPropertyObjectValue(
			$expNsResource,
			$this->exporter->newExpElement( new DIBlob( $dataValue->getImportReference() ) )
		);

		$this->addResourceHelperValue(
			$expData,
			$property,
			$dataItem
		);
	}

}
