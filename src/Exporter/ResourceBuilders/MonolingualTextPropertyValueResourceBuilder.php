<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;
use SMWExpLiteral as ExpLiteral;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MonolingualTextPropertyValueResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->findPropertyTypeID() === '_mlt_rec';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		$expResourceElement = $this->exporter->getResourceElementForWikiPage(
			$property->getCanonicalDiWikiPage(),
			true
		);

		// Avoid that an imported vocabulary is pointing to an internal resource.
		//
		// For example: <Has_alternative_label> imported from <skos:altLabel>
		// with "Monolingual text" type is expected to produce:
		//
		// - <property:Has_alternative_label rdf:resource="http://example.org/id/Foo_MLa9c103f4379a94bfab97819dacd3c182"/>
		// - <skos:altLabel xml:lang="en">Foo</skos:altLabel>
		if ( $expResourceElement->isImported() ) {
			$seekImportVocabulary = false;

			$expData->addPropertyObjectValue(
				$this->exporter->getResourceElementForProperty( $property, false, $seekImportVocabulary ),
				$this->exporter->newExpElement( $dataItem )
			);
		} else {
			parent::addResourceValue( $expData, $property, $dataItem );
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		$list = $dataValue->toArray();

		if ( !isset( $list['_TEXT'] ) || !isset( $list['_LCODE'] ) ) {
			return;
		}

		$expData->addPropertyObjectValue(
			$expResourceElement,
			new ExpLiteral(
				(string)$list['_TEXT'],
				'http://www.w3.org/2001/XMLSchema#string',
				(string)$list['_LCODE'],
				$dataItem
			)
		);
	}

}
