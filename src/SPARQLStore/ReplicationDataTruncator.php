<?php

namespace SMW\SPARQLStore;

use SMW\SemanticData;
use SMW\DIProperty;
use SMW\DataTypeRegistry;

/**
 * Truncate a SemanticData instance for the replication process
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ReplicationDataTruncator {

	/**
	 * @var array
	 */
	private $propertyExemptionList = array();

	/**
	 * @since 2.5
	 *
	 * @param array $propertyExemptionList
	 */
	public function setPropertyExemptionList( array $propertyExemptionList ) {
		$this->propertyExemptionList = str_replace( ' ', '_', $propertyExemptionList );
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticDat
	 *
	 * @return SemanticData
	 */
	public function doTruncate( SemanticData $semanticData ) {

		if ( $this->propertyExemptionList === array() ) {
			return $semanticData;
		}

		foreach ( $this->propertyExemptionList as $property ) {
			$semanticData->removeProperty( DIProperty::newFromUserLabel( $property ) );
		}

		return $semanticData;
	}

}
