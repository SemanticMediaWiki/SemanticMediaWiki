<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;
use SMWExpData as ExpData;

/**
 * @private
 *
 * @license GNU GPL v2+
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
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->findPropertyTypeID() === '_keyw';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		$dataItem = new DIBlob(
			DIBlob::normalize( $dataItem->getString() )
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
		if ( $uri instanceof DIUri ) {
			$expData->addPropertyObjectValue(
				$this->exporter->getSpecialNsResource( 'skos', 'relatedMatch' ),
				$this->exporter->newExpElement( $uri )
			);
		}
	}

}
