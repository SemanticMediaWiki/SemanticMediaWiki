<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataValueFactory;
use SMW\DataValues\ImportValue;
use SMW\Export\ExpData;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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
	public function isResourceBuilderFor( Property $property ): bool {
		return $property->getKey() === '_IMPO';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ): void {
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
			$this->exporter->newExpElement( new Blob( $dataValue->getImportReference() ) )
		);

		$this->addResourceHelperValue(
			$expData,
			$property,
			$dataItem
		);
	}

}
