<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\SemanticData;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
abstract class FixtureProperty {

	/**
	 * @var DIProperty
	 */
	protected $property = null;

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

		$semanticData->addDataValue(
			DataValueFactory::getInstance()->newDataValueByProperty(
				new DIProperty( '_TYPE' ),
				$this->property->findPropertyTypeID()
			)
		);

		return $semanticData;
	}

}
