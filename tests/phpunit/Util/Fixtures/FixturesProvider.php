<?php

namespace SMW\Tests\Util\Fixtures;

use SMW\Tests\Util\Fixtures\Properties\AreaProperty;
use SMW\Tests\Util\Fixtures\Properties\PopulationDensityProperty;
use SMW\Tests\Util\Fixtures\Properties\CapitalOfProperty;
use SMW\Tests\Util\Fixtures\Properties\StatusProperty;
use SMW\Tests\Util\Fixtures\Facts\BerlinFactsheet;
use SMW\Tests\Util\Fixtures\Facts\ParisFactsheet;

use SMW\Store;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixturesProvider {

	/**
	 * @since 2.1
	 */
	public function setupDependencies( Store $store ) {

		// This needs to happen before access to a property object is granted
		$areaProperty = new AreaProperty();
		$store->updateData( $areaProperty->getDependencies() );

		$populationDensityProperty = new PopulationDensityProperty();
		$store->updateData( $populationDensityProperty->getDependencies() );

		$capitalOfProperty = new CapitalOfProperty();
		$store->updateData( $capitalOfProperty->getDependencies() );

		$statusProperty = new StatusProperty();
		$store->updateData( $statusProperty->getDependencies() );
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getListOfFactsheetInstances() {
		return array(
			'berlin' => new BerlinFactsheet(),
			'paris'  => new ParisFactsheet()
		);
	}

	/**
	 * @since 2.1
	 *
	 * @return Factsheet
	 */
	public function getFactsheet( $item ) {

		$factsheets = $this->getListOfFactsheetInstances();

		if ( isset( $factsheets[ $item ] ) ) {
			return $factsheets[ $item ];
		}

		throw new RuntimeException( "$item is an unknown request item" );
	}

	/**
	 * @since 2.1
	 *
	 * @return FixturesCleaner
	 */
	public function getCleaner() {
		return new FixturesCleaner();
	}

}
