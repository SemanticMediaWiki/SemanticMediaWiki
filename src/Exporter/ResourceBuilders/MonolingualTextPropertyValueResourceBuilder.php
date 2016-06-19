<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DIProperty;
use SMW\DataValueFactory;
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

		parent::addResourceValue( $expData, $property, $dataItem );

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		$list = $dataValue->toArray();

		if ( !isset( $list['_TEXT'] ) || !isset( $list['_LCODE'] ) ) {
			return;
		}

		$expData->addPropertyObjectValue(
			$this->exporter->getResourceElementForWikiPage( $property->getCanonicalDiWikiPage(), true ),
			new ExpLiteral(
				(string)$list['_TEXT'],
				'http://www.w3.org/2001/XMLSchema#string',
				(string)$list['_LCODE'],
				$dataItem
			)
		);
	}

}
