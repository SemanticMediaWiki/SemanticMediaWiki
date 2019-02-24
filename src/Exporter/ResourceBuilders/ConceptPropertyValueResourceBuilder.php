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
class ConceptPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_CONC';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		$expElement = $this->exporter->newExpElement(
			$dataItem
		);

		if ( $expData->getSubject()->getUri() === '' || $expElement === null ) {
			return;
		}

		foreach ( $expElement->getProperties() as $subp ) {
			if ( $subp->getUri() != $this->exporter->getSpecialNsResource( 'rdf', 'type' )->getUri() ) {
				foreach ( $expElement->getValues( $subp ) as $subval ) {
					$expData->addPropertyObjectValue( $subp, $subval );
				}
			}
		}

		$this->addResourceHelperValue(
			$expData,
			$property,
			$dataItem
		);
	}

}
