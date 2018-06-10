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
class PropertyDescriptionValueResourceBuilder extends PropertyValueResourceBuilder {

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_PDESC';
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

		// Ussing `skos:scopeNote` instead of `skos:definition` since we can not
		// ensure that the description given by a user is complete.
		//
		// "skos:scopeNote supplies some, possibly partial, information about the
		// intended meaning of a concept ..."
		//
		// "skos:definition supplies a complete explanation of the intended
		// meaning of a concept."
		//
		// According to https://www.w3.org/TR/2009/NOTE-skos-primer-20090818/#secdocumentation

		$expData->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'skos', 'scopeNote' ),
			new ExpLiteral(
				(string)$list['_TEXT'],
				'http://www.w3.org/2001/XMLSchema#string',
				(string)$list['_LCODE'],
				$dataItem
			)
		);
	}

}
