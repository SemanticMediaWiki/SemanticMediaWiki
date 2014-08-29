<?php

namespace SMW\Tests\Util\Fixtures\Properties;

use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class AreaProperty {

	/**
	 * @var DIProperty
	 */
	private $property = null;

	/**
	 * @var array
	 */
	private $conversionValues = array(
		'1 km²',
		'0.38610 sq mi',
		'1000 m²',
		'247.1054 acre',
		'988.4215 rood'
	);

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
	public function getSemanticDataForConversionValues() {

		$dataValueFactory = DataValueFactory::getInstance();
		$semanticData = new SemanticData( $this->property->getDiWikiPage() );

		foreach( $this->conversionValues as $conversionValue ) {
			$semanticData->addDataValue(
				$dataValueFactory->newPropertyObjectValue( new DIProperty( '_CONV' ), $conversionValue )
			);
		}

		return $semanticData;
	}

}
