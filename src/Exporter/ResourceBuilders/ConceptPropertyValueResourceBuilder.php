<?php

namespace SMW\Exporter\ResourceBuilders;

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
class ConceptPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( Property $property ): bool {
		return $property->getKey() === '_CONC';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ): void {
		$expElement = $this->exporter->newExpElement(
			$dataItem
		);

		if ( $expData->getSubject()->getUri() === '' || $expElement === null ) {
			return;
		}

		foreach ( $expElement->getProperties() as $subp ) {
			if ( $subp->getUri() != $this->exporter->newExpNsResourceById( 'rdf', 'type' )->getUri() ) {
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
