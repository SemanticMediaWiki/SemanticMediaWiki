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
class AuxiliaryPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return !$property->isUserDefined() && $this->requiresAuxiliary( $property->getKey() );
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

		if ( $expElement === null ) {
			return;
		}

		if ( $property->getKey() === $property->findPropertyTypeID() ) {
			// Ensures that Boolean remains Boolean and not localized canonical
			// representation such as "Booléen" when the content languageis not
			// English
			$expNsResource = $this->getResourceElementForProperty(
				new DIProperty( $property->getCanonicalDiWikiPage()->getDBKey() )
			);
		} else {
			$expNsResource = $this->getResourceElementHelperForProperty( $property );
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

	protected function requiresAuxiliary( $key ) {
		return !in_array( $key, [ '_SKEY', '_INST', '_MDAT', '_CDAT', '_SUBC', '_SUBP', '_TYPE', '_IMPO', '_URI' ] );
	}

}
