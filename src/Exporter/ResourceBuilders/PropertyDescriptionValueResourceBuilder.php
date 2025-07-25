<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Exporter\Element\ExpLiteral;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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

		/** @var \SMW\DataValues\MonolingualTextValue $dataValue */
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
			$this->exporter->newExpNsResourceById( 'skos', 'scopeNote' ),
			new ExpLiteral(
				(string)$list['_TEXT'],
				'http://www.w3.org/2001/XMLSchema#string',
				(string)$list['_LCODE'],
				$dataItem
			)
		);
	}

}
