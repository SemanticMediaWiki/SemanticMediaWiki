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
class PreferredPropertyLabelResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_PPLB';
	}

	/**
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

		$list = $dataValue->toArray();

		if ( !isset( $list['_TEXT'] ) || !isset( $list['_LCODE'] ) ) {
			return;
		}

		// https://www.w3.org/TR/2009/NOTE-skos-primer-20090818/#secpref
		//
		// "skos:prefLabel ... implies that a resource can only have one such
		// label per language tag ... it is recommended that no two concepts in
		// the same KOS be given the same preferred lexical label for any given
		// language tag ..."

		$expData->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'skos', 'prefLabel' ),
			new ExpLiteral(
				(string)$list['_TEXT'],
				'http://www.w3.org/2001/XMLSchema#string',
				(string)$list['_LCODE'],
				$dataItem
			)
		);
	}

}
