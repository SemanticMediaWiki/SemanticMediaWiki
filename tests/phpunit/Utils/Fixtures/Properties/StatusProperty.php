<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DIProperty;
use SMW\SemanticData;
use SMWDIBlob as DIBlob;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class StatusProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Status' );
		$this->property->setPropertyTypeId( '_txt' );
	}

	/**
	 * @since 2.1
	 *
	 * @return SemanticData
	 */
	public function getDependencies() {
		$semanticData = parent::getDependencies();

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_PVAL' ),
			new DIBlob( 'open' )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_PVAL' ),
			new DIBlob( 'closed' )
		);

		return $semanticData;
	}

}
