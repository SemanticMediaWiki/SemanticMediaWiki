<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Uri;
use SMW\DataValueFactory;
use SMW\Export\ExpData;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class KeywordPropertyValueResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( Property $property ): bool {
		return $property->findPropertyTypeID() === '_keyw';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ): void {
		$dataItem = new Blob(
			Blob::normalize( $dataItem->getString() )
		);

		parent::addResourceValue( $expData, $property, $dataItem );

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		$uri = $dataValue->getUri();

		/**
		 * @see https://www.w3.org/2009/08/skos-reference/skos.rdf
		 *
		 * "skos:relatedMatch" has been defined as "... used to state an associative
		 * mapping link between two conceptual resources in different concept
		 * schemes ..."
		 */
		if ( $uri instanceof Uri ) {
			$expData->addPropertyObjectValue(
				$this->exporter->newExpNsResourceById( 'skos', 'relatedMatch' ),
				$this->exporter->newExpElement( $uri )
			);
		}
	}

}
