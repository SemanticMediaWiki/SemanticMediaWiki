<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\Export\ExpData;
use SMW\Exporter\Element\ExpLiteral;
use SMW\MediaWiki\Collator;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SortPropertyValueResourceBuilder extends PredefinedPropertyValueResourceBuilder {

	private bool $enabledCollationField = false;

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( Property $property ): bool {
		return $property->getKey() === '_SKEY';
	}

	/**
	 * @since 3.0
	 */
	public function enabledCollationField( mixed $enabledCollationField ): void {
		$this->enabledCollationField = (bool)$enabledCollationField;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ): void {
		if ( !$dataItem instanceof Blob ) {
			$dataItem = new Blob( $dataItem->getSortKey() );
		}

		parent::addResourceValue( $expData, $property, $dataItem );

		if ( !$this->enabledCollationField ) {
			return;
		}

		$sort = Collator::singleton()->armor(
			Collator::singleton()->getSortKey( $dataItem->getSortKey() )
		);

		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'swivt', 'sort' ),
			new ExpLiteral(
				$sort,
				'http://www.w3.org/2001/XMLSchema#string'
			)
		);
	}

}
