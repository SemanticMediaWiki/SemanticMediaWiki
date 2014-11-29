<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\SemanticData;
use SMW\DIProperty;

use SMWDIBlob as DIBlob;

/**
 * Simplified book record
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class BookRecordProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Book record' );
		$this->property->setPropertyTypeId( '_rec' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {

		$semanticData = parent::getDependencies();

		$titleProperty = new TitleProperty();
		$yearProperty = new YearProperty();

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_LIST' ),
			new DIBlob(
				$titleProperty->getProperty()->getKey() . ';' .
				$yearProperty->getProperty()->getKey()
			)
		);

		return $semanticData;
	}

}
