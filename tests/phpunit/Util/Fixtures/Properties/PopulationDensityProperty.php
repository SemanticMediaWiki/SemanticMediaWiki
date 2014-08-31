<?php

namespace SMW\Tests\Util\Fixtures\Properties;

use SMW\SemanticData;
use SMW\DIProperty;

use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PopulationDensityProperty {

	/**
	 * @var DIProperty
	 */
	private $property = null;

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
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {

		$semanticData = new SemanticData( $this->property->getDiWikiPage() );

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
