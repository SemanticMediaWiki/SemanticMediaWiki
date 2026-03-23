<?php

namespace SMW\SPARQLStore;

use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;

/**
 * Truncate a SemanticData instance for the replication process
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ReplicationDataTruncator {

	/**
	 * @var array
	 */
	private $propertyExemptionList = [];

	/**
	 * @since 2.5
	 *
	 * @param array $propertyExemptionList
	 */
	public function setPropertyExemptionList( array $propertyExemptionList ): void {
		$this->propertyExemptionList = str_replace( ' ', '_', $propertyExemptionList );
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return SemanticData
	 */
	public function doTruncate( SemanticData $semanticData ): SemanticData {
		if ( $this->propertyExemptionList === [] ) {
			return $semanticData;
		}

		foreach ( $this->propertyExemptionList as $property ) {
			$semanticData->removeProperty( Property::newFromUserLabel( $property ) );
		}

		return $semanticData;
	}

}
