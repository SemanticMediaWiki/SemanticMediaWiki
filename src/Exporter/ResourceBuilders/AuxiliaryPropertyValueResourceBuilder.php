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
class AuxiliaryPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( Property $property ): bool {
		return !$property->isUserDefined() && $this->requiresAuxiliary( $property->getKey() );
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

		if ( $expElement === null ) {
			return;
		}

		if ( $property->getKey() === $property->findPropertyTypeID() ) {
			// Ensures that Boolean remains Boolean and not localized canonical
			// representation such as "Booléen" when the content languageis not
			// English
			$expNsResource = $this->getResourceElementForProperty(
				new Property( $property->getCanonicalDiWikiPage()->getDBKey() )
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

	protected function requiresAuxiliary( $key ): bool {
		return !in_array( $key, [ '_SKEY', '_INST', '_MDAT', '_CDAT', '_SUBC', '_SUBP', '_TYPE', '_IMPO', '_URI' ] );
	}

}
