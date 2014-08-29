<?php

namespace SMW\Tests\Util\Fixtures\Facts;

use SMW\Tests\Util\Fixtures\Properties\AreaProperty;
use SMW\Tests\Util\Fixtures\Properties\TemperatureProperty;
use SMW\Tests\Util\Fixtures\Properties\PopulationProperty;
use SMW\Tests\Util\Fixtures\Properties\YearProperty;
use SMW\Tests\Util\Fixtures\Properties\PopulationDensityProperty;

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
class ParisFact {

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
		return new DIWikiPage( 'Paris', NS_MAIN );
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Berlin
	 *
	 * @return DataValue
	 */
	public function getAreaValue() {

		$areaProperty = new AreaProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$areaProperty->getProperty(),
			'105.40 km²',
			'City of Paris'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Berlin
	 *
	 * @return DataValue
	 */
	public function getAverageHighTemperatureValue() {

		$temperatureProperty = new TemperatureProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$temperatureProperty->getProperty(),
			'16.0 °C',
			'Average high temperature'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Demographics_of_Paris
	 *
	 * @return DataValue
	 */
	public function getPopulationValue() {

		$populationProperty = new PopulationProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$populationProperty->getProperty(),
			'2234105'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Demographics_of_Paris
	 *
	 * @return DataValue
	 */
	public function getPopulationDensityValue() {

		$populationDensityProperty = new PopulationDensityProperty();

		return $this->dataValueFactory->newPropertyObjectValue(
			$populationDensityProperty->getProperty(),
			'20169;1 km²'
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Demographics_of_Paris
	 *
	 * @return Subobject
	 */
	public function getDemographicsForTargetSubject( DIWikiPage $subject ) {

		$subobject = new Subobject( $subject->getTitle() );
		$subobject->setEmptySemanticDataforId( 'Paris#Demographics' );

		$yearProperty = new YearProperty();

		$yearDataValue = $this->dataValueFactory->newPropertyObjectValue(
			$yearProperty->getProperty(),
			'2009'
		);

		$subobject->addDataValue( $yearDataValue );
		$subobject->addDataValue( $this->getAreaValue() );
		$subobject->addDataValue( $this->getPopulationValue() );
		$subobject->addDataValue( $this->getPopulationDensityValue() );

		return $subobject;
	}

}
