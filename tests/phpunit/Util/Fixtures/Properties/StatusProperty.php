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
class StatusProperty {

	/**
	 * @var DIProperty
	 */
	private $property = null;

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
