<?php

namespace SMW;

use SMW\SemanticData;
use SMW\Store;
use SMW\DIProperty;
use SMW\DataItemFactory;

/**
 * Examines codified requirements for listed types of property specifications which
 * in case of a violation returns a message with the details of that violation.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationReqExaminer {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var DataItemFactory
	 */
	private $dataItemFactory;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param DIProperty $property
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return array|null
	 */
	public function checkOn( DIProperty $property ) {

		if ( $this->semanticData === null ) {
			$this->semanticData = $this->store->getSemanticData( $property->getCanonicalDiWikiPage() );
		}

		$type = $property->findPropertyTypeID();
		$this->dataItemFactory = new DataItemFactory();

		if ( !$property->isUserDefined() ) {
			return $this->checkOnTypeForPredefinedProperty( $property );
		}

		if ( $type === '_ref_rec' || $type === '_rec' ) {
			return $this->checkOnFieldList( $property );
		}

		if ( $type === '_eid' ) {
			return $this->checkOnExternalFormatterUri( $property );
		}
	}

	/**
	 * A violation occurs when a predefined property contains a `Has type` annotation
	 * that is incompatible with the default type.
	 */
	private function checkOnTypeForPredefinedProperty( $property ) {

		if ( !$this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_TYPE' ) ) ) {
			return;
		}

		$typeValues = $this->semanticData->getPropertyValues(
			$this->dataItemFactory->newDIProperty( '_TYPE' )
		);

		if ( $typeValues !== array() ) {
			list( $url, $type ) = explode( "#", end( $typeValues )->getSerialization() );
		}

		if ( $type === $property->findPropertyTypeID() ) {
			return;
		}

		$prop = $this->dataItemFactory->newDIProperty( $type );

		return array(
			'smw-property-req-violation-predefined-type',
			$property->getCanonicalLabel(),
			$prop->getCanonicalLabel()
		);
	}

	/**
	 * A violation occurs when a Reference or Record typed property does not denote
	 * a `Has fields` declaration.
	 */
	private function checkOnFieldList( $property ) {

		if ( $this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_LIST' ) ) ) {
			return;
		}

		$prop = $this->dataItemFactory->newDIProperty( $property->findPropertyTypeID() );

		return array(
			'smw-property-req-violation-missing-fields',
			$property->getLabel(),
			$prop->getCanonicalLabel()
		);
	}

	/**
	 * A violation occurs when the External Identifier typed property does not declare
	 * a `External formatter URI` declaration.
	 */
	private function checkOnExternalFormatterUri( $property ) {

		if ( $this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_PEFU' ) ) ) {
			return;
		}

		return array(
			'smw-property-req-violation-missing-formatter-uri',
			$property->getLabel()
		);
	}

}
