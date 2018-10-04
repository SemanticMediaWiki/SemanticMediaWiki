<?php

namespace SMW;

use SMW\PropertyAnnotators\MandatoryTypePropertyAnnotator;
use SMW\Protection\ProtectionValidator;
use SMWDataItem as DataItem;

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
	 * @var ProtectionValidator
	 */
	private $protectionValidator;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var boolean
	 */
	private $changePropagationProtection = true;

	/**
	 * @var DataItemFactory
	 */
	private $dataItemFactory;

	/**
	 * @var boolean
	 */
	private $reqLock = false;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param ProtectionValidator $protectionValidator
	 */
	public function __construct( Store $store, ProtectionValidator $protectionValidator ) {
		$this->store = $store;
		$this->protectionValidator = $protectionValidator;
	}

	/**
	 * @since 3.0
	 *
	 * @param SemanticData|null $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData = null ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $changePropagationProtection
	 */
	public function setChangePropagationProtection( $changePropagationProtection ) {
		$this->changePropagationProtection = (bool)$changePropagationProtection;
	}

	/**
	 * Whether a specific property requires a lock nor not.
	 *
	 * @since 3.0
	 *
	 * @param boolean
	 */
	public function reqLock() {
		return $this->reqLock;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return array|null
	 */
	public function check( DIProperty $property ) {

		$subject = $property->getCanonicalDiWikiPage();
		$title = $subject->getTitle();

		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->semanticData === null ) {
			$this->semanticData = $semanticData;
		}

		$this->reqLock = false;
		$this->dataItemFactory = new DataItemFactory();

		if ( $semanticData->hasProperty( new DIProperty( DIProperty::TYPE_CHANGE_PROP ) ) ) {
			$severity = $this->changePropagationProtection ? 'error' : 'warning';
			$this->reqLock = true;
			return [
				$severity,
				'smw-property-req-violation-change-propagation-locked-' . $severity,
				$property->getLabel()
			];
		}

		if ( $this->reqLock === false && $this->protectionValidator->hasCreateProtection( $title ) ) {
			$msg = 'smw-create-protection';

			if ( $title->exists() ) {
				$msg = 'smw-create-protection-exists';
			}

			return [
				'warning',
				$msg,
				$property->getLabel(),
				$this->protectionValidator->getCreateProtectionRight()
			];
		}

		if ( $this->reqLock === false && $this->protectionValidator->hasEditProtection( $title ) ) {
			return [
				$property->isUserDefined() ? 'error' : 'warning',
				'smw-edit-protection',
				$this->protectionValidator->getEditProtectionRight()
			];
		}

		if ( !$property->isUserDefined() ) {
			return $this->checkTypeForPredefinedProperty( $property );
		}

		$type = $property->findPropertyTypeID();

		if ( $type === '_ref_rec' || $type === '_rec' ) {
			return $this->checkFieldList( $property );
		}

		if ( $type === '_eid' ) {
			return $this->checkExternalFormatterUri( $property );
		}

		if ( $type === '_geo' ) {
			return $this->checkMaps( $property );
		}

		if ( $this->semanticData->getOption( MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE ) ) {
			return $this->checkImportedVocabType( $property );
		}
	}

	/**
	 * A violation occurs when a predefined property contains a `Has type` annotation
	 * that is incompatible with the default type.
	 */
	private function checkTypeForPredefinedProperty( $property ) {

		if ( $property->getKey() === '_EDIP' ) {
			return $this->checkEditProtectionRight( $property );
		}

		if ( !$this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_TYPE' ) ) ) {
			return;
		}

		$typeValues = $this->semanticData->getPropertyValues(
			$this->dataItemFactory->newDIProperty( '_TYPE' )
		);

		if ( $typeValues !== [] ) {
			list( $url, $type ) = explode( "#", end( $typeValues )->getSerialization() );
		}

		if ( DataTypeRegistry::getInstance()->isEqualByType( $type, $property->findPropertyTypeID() ) ) {
			return;
		}

		$prop = $this->dataItemFactory->newDIProperty( $type );

		return [
			'error',
			'smw-property-req-violation-predefined-type',
			$property->getCanonicalLabel(),
			$prop->getCanonicalLabel()
		];
	}

	/**
	 * Examines whether the setting `smwgEditProtectionRight` contains an appropriate
	 * value or is disabled in order for the `Is edit protected` property to function.
	 */
	private function checkEditProtectionRight( $property ) {

		if ( $this->protectionValidator->getEditProtectionRight() !== false ) {
			return;
		}

		return [
			'warning',
			'smw-edit-protection-disabled',
			$property->getCanonicalLabel()
		];
	}

	/**
	 * A violation occurs when a Reference or Record typed property does not denote
	 * a `Has fields` declaration.
	 */
	private function checkFieldList( $property ) {

		if ( $this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_LIST' ) ) ) {
			return;
		}

		$prop = $this->dataItemFactory->newDIProperty( $property->findPropertyTypeID() );

		return [
			'error',
			'smw-property-req-violation-missing-fields',
			$property->getLabel(),
			$prop->getCanonicalLabel()
		];
	}

	/**
	 * A violation occurs when the External Identifier typed property does not declare
	 * a `External formatter URI` declaration.
	 */
	private function checkExternalFormatterUri( $property ) {

		if ( $this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_PEFU' ) ) ) {
			return;
		}

		return [
			'error',
			'smw-property-req-violation-missing-formatter-uri',
			$property->getLabel()
		];
	}

	private function checkMaps( $property ) {

		if ( defined( 'SM_VERSION' ) ) {
			return;
		}

		return [
			'error',
			'smw-property-req-violation-missing-maps-extension',
			$property->getLabel()
		];
	}

	/**
	 * A violation occurs when the `Imported from` property detects an incompatible
	 * `Has type` declaration.
	 */
	private function checkImportedVocabType( $property ) {

		$typeValues = $this->semanticData->getPropertyValues(
			$this->dataItemFactory->newDIProperty( '_TYPE' )
		);

		$dataItem = $this->semanticData->getOption(
			MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE
		);

		if ( $dataItem instanceof DataItem && end( $typeValues )->equals( $dataItem ) ) {
			return;
		}

		return [
			'warning',
			'smw-property-req-violation-import-type',
			$property->getLabel()
		];
	}

}
