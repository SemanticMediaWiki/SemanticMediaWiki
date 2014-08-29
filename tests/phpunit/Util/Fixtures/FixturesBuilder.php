<?php

namespace SMW\Tests\Util\Fixtures;

use SMW\Tests\Util\Fixtures\Properties\AreaProperty;
use SMW\Tests\Util\Fixtures\Properties\PopulationDensityProperty;
use SMW\Tests\Util\Fixtures\Facts\Berlin;

use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixturesBuilder {

	/**
	 * @since 2.1
	 */
	public function updateFixtureDependencies( Store $store ) {

		// This needs to happen before access to a property object is granted
		$areaProperty = new AreaProperty();
		$store->updateData( $areaProperty->getSemanticDataForConversionValues() );

		$populationDensityProperty = new PopulationDensityProperty();
		$store->updateData( $populationDensityProperty->getSemanticDataForRecordFields() );
	}

}
