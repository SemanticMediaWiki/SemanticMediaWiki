<?php

namespace SMW\Tests\Utils\Fixtures\Facts;

use RuntimeException;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataModel\Subobject;
use SMW\DataValueFactory;
use SMW\Tests\Utils\Fixtures\Properties\AreaProperty;
use SMW\Tests\Utils\Fixtures\Properties\CityCategory;
use SMW\Tests\Utils\Fixtures\Properties\LocatedInProperty;
use SMW\Tests\Utils\Fixtures\Properties\PopulationDensityProperty;
use SMW\Tests\Utils\Fixtures\Properties\PopulationProperty;
use SMW\Tests\Utils\Fixtures\Properties\TemperatureProperty;
use SMW\Tests\Utils\Fixtures\Properties\YearProperty;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ParisFactsheet {

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @since 2.1
	 */
	public function __construct( private ?WikiPage $targetSubject = null ) {
		if ( $this->targetSubject === null ) {
			$this->targetSubject = $this->asSubject();
		}

		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @param WikiPage $targetSubject
	 */
	public function setTargetSubject( WikiPage $targetSubject ) {
		$this->targetSubject = $targetSubject;
	}

	/**
	 * @since 2.1
	 *
	 * @return WikiPage
	 */
	public function asSubject() {
		return new WikiPage( 'Paris', NS_MAIN );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function asEntity() {
		$semanticData = new SemanticData( $this->asSubject() );
		$semanticData->addDataValue( $this->getAreaValue() );
		$semanticData->addDataValue( $this->getAverageHighTemperatureValue() );
		$semanticData->addDataValue( $this->getPopulationValue() );
		$semanticData->addDataValue( $this->getPopulationDensityValue() );
		$semanticData->addDataValue( $this->getLocatedInValue() );
		$semanticData->addSubobject( $this->getDemographics() );

		$cityCategory = new CityCategory();

		$semanticData->addDataValue( $cityCategory->getCategoryValue() );

		return $semanticData;
	}

	/**
	 * @since 2.1
	 *
	 * @return DataValue
	 */
	public function getLocatedInValue() {
		$locatedInProperty = new LocatedInProperty();

		return $this->dataValueFactory->newDataValueByItem(
			new WikiPage( 'France', NS_MAIN ),
			$locatedInProperty->getProperty()
		);
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

		return $this->dataValueFactory->newDataValueByProperty(
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

		return $this->dataValueFactory->newDataValueByProperty(
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

		return $this->dataValueFactory->newDataValueByProperty(
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
		if ( $this->targetSubject === null ) {
			throw new RuntimeException( 'Expected a target subject' );
		}

		$populationDensityProperty = new PopulationDensityProperty();

		return $this->dataValueFactory->newDataValueByProperty(
			$populationDensityProperty->getProperty(),
			'20169;1 km²',
			'Population density',
			$this->targetSubject
		);
	}

	/**
	 * @since 2.1
	 *
	 * @see https://en.wikipedia.org/wiki/Demographics_of_Paris
	 *
	 * @return Subobject
	 */
	public function getDemographics() {
		if ( $this->targetSubject === null ) {
			throw new RuntimeException( 'Expected a target subject' );
		}

		$subobject = new Subobject( $this->targetSubject->getTitle() );
		$subobject->setEmptyContainerForId( 'Paris#Demographics' );

		$yearProperty = new YearProperty();

		$yearDataValue = $this->dataValueFactory->newDataValueByProperty(
			$yearProperty->getProperty(),
			'2009'
		);

		$subobject->addDataValue( $yearDataValue );
		$subobject->addDataValue( $this->getAreaValue() );
		$subobject->addDataValue( $this->getPopulationValue() );
		$subobject->addDataValue( $this->getPopulationDensityValue() );

		return $subobject;
	}

	/**
	 * @since 2.1
	 */
	public function purge() {
		$subjects = [];

		$subjects[] = $this->asSubject();
		$subjects[] = $this->targetSubject;
		$subjects[] = $this->getAreaValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getAverageHighTemperatureValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getPopulationValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getPopulationDensityValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getDemographics()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getLocatedInValue()->getProperty()->getDiWikiPage();
		$subjects[] = $this->getLocatedInValue()->getDataItem();

		// Record type needs extra attention
		$dataItems = $this->getPopulationDensityValue()->getDataItems();

		foreach ( $dataItems as $dataItem ) {
			$subjects[] = $dataItem;
		}

		// Clean-up subobject
		$properties = $this->getDemographics()->getSemanticData()->getProperties();

		foreach ( $properties as $property ) {
			$subjects[] = $property->getDiWikiPage();
		}

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		foreach ( $subjects as $subject ) {
			if ( $subject instanceof WikiPage ) {
				$pageDeleter->deletePage( $subject->getTitle() );
			}
		}
	}

}
