<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDIUri as DIUri;
use SMWExpData as ExpData;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExternalIdentifierPropertyValueResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->findPropertyTypeID() === '_eid';
	}

	/**
	 * Instead of representing an external identifier as "owl:sameAs", the weaker
	 * declarative axiom "skos:exactMatch" has been chosen to avoid potential
	 * issues with undesirable entailments.
	 *
	 * "skos:exactMatch" has been defined as "... indicating a high degree of
	 * confidence that the concepts can be used interchangeably across a wide
	 * range of information retrieval applications ..."
	 *
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		parent::addResourceValue( $expData, $property, $dataItem );

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		$uri = $dataValue->getUri();

		if ( $uri instanceof DIUri ) {
			$expData->addPropertyObjectValue(
				$this->exporter->getSpecialNsResource( 'skos', 'exactMatch' ),
				$this->exporter->newExpElement( $uri )
			);
		}
	}

}
