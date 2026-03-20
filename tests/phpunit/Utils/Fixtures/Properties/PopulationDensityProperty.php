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
class PopulationDensityProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = Property::newFromUserLabel( 'Population density' );
		$this->property->setPropertyTypeId( '_rec' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {
		$semanticData = parent::getDependencies();

		$populationProperty = new PopulationProperty();
		$areaProperty = new AreaProperty();

		$semanticData->addPropertyObjectValue(
			new Property( '_LIST' ),
			new Blob(
				$populationProperty->getProperty()->getKey() . ';' .
				$areaProperty->getProperty()->getKey()
			)
		);

		return $semanticData;
	}

}
