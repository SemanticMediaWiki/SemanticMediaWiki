<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class CapitalOfProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = Property::newFromUserLabel( 'Capital of' );
		$this->property->setPropertyTypeId( '_wpg' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {
		$semanticData = parent::getDependencies();

		$locatedInProperty = new LocatedInProperty();

		$semanticData->addPropertyObjectValue(
			new Property( '_SUBP' ),
			$locatedInProperty->getProperty()->getDiWikiPage()
		);

		return $semanticData;
	}

}
