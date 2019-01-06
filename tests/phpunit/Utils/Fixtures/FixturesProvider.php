<?php

namespace SMW\Tests\Utils\Fixtures;

use RuntimeException;
use SMW\Store;
use SMW\Tests\Utils\Fixtures\Facts\BerlinFactsheet;
use SMW\Tests\Utils\Fixtures\Facts\FranceFactsheet;
use SMW\Tests\Utils\Fixtures\Facts\ParisFactsheet;
use SMW\Tests\Utils\Fixtures\Properties\AreaProperty;
use SMW\Tests\Utils\Fixtures\Properties\BookRecordProperty;
use SMW\Tests\Utils\Fixtures\Properties\CityCategory;
use SMW\Tests\Utils\Fixtures\Properties\FoundedProperty;
use SMW\Tests\Utils\Fixtures\Properties\LocatedInProperty;
use SMW\Tests\Utils\Fixtures\Properties\PopulationDensityProperty;
use SMW\Tests\Utils\Fixtures\Properties\PopulationProperty;
use SMW\Tests\Utils\Fixtures\Properties\StatusProperty;
use SMW\Tests\Utils\Fixtures\Properties\TitleProperty;
use SMW\Tests\Utils\Fixtures\Properties\UrlProperty;
use SMW\Tests\Utils\Fixtures\Properties\YearProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixturesProvider {

	private $factsheets = null;
	private $properties = null;
	private $categories = null;

	/**
	 * @since 2.1
	 */
	public function setupDependencies( Store $store ) {

		// This needs to happen before access to a property object is granted

		// $pageCreator = UtilityFactory::getInstance()->newPageCreator();

		foreach ( $this->getListOfPropertyInstances() as $propertyInstance ) {
			$store->updateData( $propertyInstance->getDependencies() );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getListOfFactsheetInstances() {
		return [
			'berlin' => new BerlinFactsheet(),
			'paris'  => new ParisFactsheet(),
			'france'  => new FranceFactsheet()
		];
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getListOfPropertyInstances() {
		return [
			'area' => new AreaProperty(),
			'populationdensity' => new PopulationDensityProperty(),
		//	'capitalof' => new CapitalOfProperty(),
			'status' => new StatusProperty(),
			'population' => new PopulationProperty(),
			'founded' => new FoundedProperty(),
			'locatedin' => new LocatedInProperty(),
			'bookrecord' => new BookRecordProperty(),
			'year' => new YearProperty(),
			'title' => new TitleProperty(),
			'url' => new UrlProperty()
		];
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getListOfCategoryInstances() {
		return [
			'city' => new CityCategory()
		];
	}

	/**
	 * @since 2.1
	 *
	 * @return DIProperty
	 * @throws RuntimeException
	 */
	public function getProperty( $id ) {

		$id = strtolower( $id );

		if ( $this->properties === null ) {
			$this->properties = $this->getListOfPropertyInstances();
		};

		if ( isset( $this->properties[$id] ) ) {
			return $this->properties[$id]->getProperty();
		}

		throw new RuntimeException( "$id is an unknown requested property" );
	}

	/**
	 * @since 2.1
	 *
	 * @return DIProperty
	 * @throws RuntimeException
	 */
	public function getCategory( $id ) {

		$id = strtolower( $id );

		if ( $this->categories === null ) {
			$this->categories = $this->getListOfCategoryInstances();
		};

		if ( isset( $this->categories[$id] ) ) {
			return $this->categories[$id];
		}

		throw new RuntimeException( "$id is an unknown requested property" );
	}

	/**
	 * @since 2.1
	 *
	 * @return Factsheet
	 * @throws RuntimeException
	 */
	public function getFactsheet( $id ) {

		$id = strtolower( $id );

		if ( $this->factsheets === null ) {
			$this->factsheets = $this->getListOfFactsheetInstances();
		};

		if ( isset( $this->factsheets[$id] ) ) {
			return $this->factsheets[$id];
		}

		throw new RuntimeException( "$id is an unknown requested fact" );
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
