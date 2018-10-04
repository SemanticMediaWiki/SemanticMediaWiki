<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DIProperty;
use SMW\MediaWiki\Collator;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWExpData as ExpData;
use SMWExpLiteral as ExpLiteral;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SortPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	/**
	 * @var boolean
	 */
	private $enabledCollationField = false;

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return $property->getKey() === '_SKEY';
	}


	/**
	 * @since 3.0
	 *
	 * @param boolean $enabledCollationField
	 */
	public function enabledCollationField( $enabledCollationField ) {
		$this->enabledCollationField = (bool)$enabledCollationField;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		if ( !$dataItem instanceof DIBlob ) {
			$dataItem = new DIBlob( $dataItem->getSortKey() );
		}

		parent::addResourceValue( $expData, $property, $dataItem );

		if ( $this->enabledCollationField === false ) {
			return;
		}

		$sort = Collator::singleton()->armor(
			Collator::singleton()->getSortKey( $dataItem->getSortKey() )
		);

		$expData->addPropertyObjectValue(
			$this->exporter->getSpecialNsResource( 'swivt', 'sort' ),
			new ExpLiteral(
				$sort,
				'http://www.w3.org/2001/XMLSchema#string'
			)
		);
	}

}
