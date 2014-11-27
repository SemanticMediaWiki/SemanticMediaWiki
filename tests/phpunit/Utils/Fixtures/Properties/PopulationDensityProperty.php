<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\SemanticData;
use SMW\DIProperty;

use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PopulationDensityProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Population density' );
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
			new DIProperty( '_LIST' ),
			new DIBlob(
				$populationProperty->getProperty()->getKey() . ';' .
				$areaProperty->getProperty()->getKey()
			)
		);

		return $semanticData;
	}

}
