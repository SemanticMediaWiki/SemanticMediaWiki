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
class AreaProperty extends FixtureProperty {

	/**
	 * @var array
	 */
	private $conversionValues = [
		'1 km²',
		'0.38610 sq mi',
		'1000 m²',
		'247.1054 acre',
		'988.4215 rood'
	];

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new DIProperty( 'Area' );
		$this->property->setPropertyTypeId( '_qty' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {

		$semanticData = parent::getDependencies();

		$dataValueFactory = DataValueFactory::getInstance();

		foreach( $this->conversionValues as $conversionValue ) {
			$semanticData->addDataValue(
				$dataValueFactory->newDataValueByProperty(
					new DIProperty( '_CONV' ),
					$conversionValue
				)
			);
		}

		return $semanticData;
	}

}
