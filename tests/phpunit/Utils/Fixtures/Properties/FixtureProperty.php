<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
abstract class FixtureProperty {

	/**
	 * @var Property
	 */
	protected $property = null;

	/**
	 * @since 2.1
	 *
	 * @return Property
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

		$semanticData->addDataValue(
			DataValueFactory::getInstance()->newDataValueByProperty(
				new Property( '_TYPE' ),
				$this->property->findPropertyTypeID()
			)
		);

		return $semanticData;
	}

}
