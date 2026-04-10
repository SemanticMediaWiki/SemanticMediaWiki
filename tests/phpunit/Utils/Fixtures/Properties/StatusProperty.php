<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class StatusProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = Property::newFromUserLabel( 'Status' );
		$this->property->setPropertyTypeId( '_txt' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {
		$semanticData = parent::getDependencies();

		$semanticData->addPropertyObjectValue(
			new Property( '_PVAL' ),
			new Blob( 'open' )
		);

		$semanticData->addPropertyObjectValue(
			new Property( '_PVAL' ),
			new Blob( 'closed' )
		);

		return $semanticData;
	}

}
