<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;

/**
 * Simplified book record
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class BookRecordProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = Property::newFromUserLabel( 'Book record' );
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
			new Property( '_LIST' ),
			new Blob(
				$titleProperty->getProperty()->getKey() . ';' .
				$yearProperty->getProperty()->getKey()
			)
		);

		return $semanticData;
	}

}
