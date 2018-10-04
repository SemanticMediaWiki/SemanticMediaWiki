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
class UniquenessConstraintPropertyValueResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_PVUC';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		parent::addResourceValue( $expData, $property, $dataItem );

		// https://www.w3.org/TR/2004/REC-owl-ref-20040210/#FunctionalProperty-def
		//
		// "A functional property is a property that can have only one (unique)
		// value y for each instance x ..."

		$expData->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'rdf', 'type' ),
			$this->exporter->getSpecialNsResource( 'owl', 'FunctionalProperty' )
		);
	}

}
