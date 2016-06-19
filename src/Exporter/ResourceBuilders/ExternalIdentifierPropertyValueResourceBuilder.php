<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\Exporter\ResourceBuilder;
use SMW\DIProperty;
use SMWExporter as Exporter;
use SMW\DataValueFactory;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;
use SMWDIUri as DIUri;

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
	 * declarative axiom "skos:exactMatch" has been choosen to avoid potential
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

		$formattedUriDataItem = $dataValue->getWithFormattedUri();

		if ( $formattedUriDataItem instanceof DIUri ) {
			$expData->addPropertyObjectValue(
				$this->exporter->getSpecialNsResource( 'skos', 'exactMatch' ),
				$this->exporter->getDataItemExpElement( $formattedUriDataItem )
			);
		}
	}

}
