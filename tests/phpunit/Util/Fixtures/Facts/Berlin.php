<?php

namespace SMW\Tests\Util\Fixtures\Facts;

use SMW\Tests\Util\Fixtures\Properties\AreaProperty;
use SMW\Tests\Util\Fixtures\Properties\TemperatureProperty;
use SMW\Tests\Util\Fixtures\Properties\PopulationProperty;
use SMW\Tests\Util\Fixtures\Properties\YearProperty;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;
use SMW\Subobject;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class Berlin {

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @return DIWikiPage
	 */
	public function asSubject() {
		return new DIWikiPage( 'Berlin', NS_MAIN );
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Berlin
	 *
	 * @return DataValue
	 */
	public function getAreaDataValue() {

		$areaProperty = new AreaProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$areaProperty->getProperty(),
			'891.85 km²'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Berlin
	 *
	 * @return DataValue
	 */
	public function getAverageHighTemperatureDataValue() {

		$temperatureProperty = new TemperatureProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$temperatureProperty->getProperty(),
			'13.4 °C',
			'Average high temperature'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Berlin
	 *
	 * @return DataValue
	 */
	public function getPopulationDataValue() {

		$populationProperty = new PopulationProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$populationProperty->getProperty(),
			'3517424'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Demographics_of_Berlin
	 *
	 * @return Subobject
	 */
	public function getDemographicsForId( DIWikiPage $subject ) {

		$subobject = new Subobject( $subject->getTitle() );
		$subobject->setEmptySemanticDataforId( 'Demographics' );

		$yearProperty = new YearProperty();

		$yearDataValue = $this->dataValueFactory->newPropertyObjectValue(
			$yearProperty->getProperty(),
			'2013'
		);

		$subobject->addDataValue( $yearDataValue );
		$subobject->addDataValue( $this->getAreaDataValue() );
		$subobject->addDataValue( $this->getPopulationDataValue() );

		return $subobject;
	}

}
