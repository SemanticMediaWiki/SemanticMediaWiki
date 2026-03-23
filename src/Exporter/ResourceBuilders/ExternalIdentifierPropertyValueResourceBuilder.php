<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Uri;
use SMW\DataValueFactory;
use SMW\Export\ExpData;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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
	public function isResourceBuilderFor( Property $property ): bool {
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
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ): void {
		parent::addResourceValue( $expData, $property, $dataItem );

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		$uri = $dataValue->getUri();

		if ( $uri instanceof Uri ) {
			$expData->addPropertyObjectValue(
				$this->exporter->newExpNsResourceById( 'skos', 'exactMatch' ),
				$this->exporter->newExpElement( $uri )
			);
		}
	}

}
