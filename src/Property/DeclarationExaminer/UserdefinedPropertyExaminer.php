<?php

namespace SMW\Property\DeclarationExaminer;

use SMW\DIProperty;
use SMW\Property\DeclarationExaminer as IDeclarationExaminer;
use SMW\DataTypeRegistry;
use SMW\Property\Annotators\MandatoryTypePropertyAnnotator;
use SMWDataItem as DataItem;
use SMW\Store;
use SMW\Message;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UserdefinedPropertyExaminer extends DeclarationExaminer {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param DeclarationExaminer $declarationExaminer
	 * @param Store $store
	 */
	public function __construct( IDeclarationExaminer $declarationExaminer, Store $store ) {
		$this->declarationExaminer = $declarationExaminer;
		$this->store = $store;
	}

	/**
	 * @see DeclarationExaminer::validate
	 *
	 * {@inheritDoc}
	 */
	protected function validate( DIProperty $property ) {

		if ( !$property->isUserDefined() ) {
			return;
		}

		$type = $property->findPropertyTypeID();

		$this->checkMessages( $property );

		$this->checkRecordType( $type, $property );
		$this->checkExternalIdentifierType( $type, $property );
		$this->checkGeoType( $type, $property );
		$this->checkImportedVocabType( $property );
		$this->checkSubpropertyParentType( $type, $property );
	}

	private function checkMessages( $property ) {

		$label = $property->getLabel();

		if ( $this->store->getPropertyTableInfoFetcher()->isFixedTableProperty( $property ) ) {
			$this->messages[] = [ 'info', 'smw-property-userdefined-fixedtable', $label ];
		}

		if ( Message::exists( 'smw-property-introductory-message-user' ) ) {
			$this->messages[] = [ 'info', 'smw-property-introductory-message-user', $label ];
		}
	}

	private function checkRecordType( $type, $property ) {

		if ( $type !== '_ref_rec' && $type !== '_rec' ) {
			return;
		}

		$semanticData = $this->getSemanticData();
		$prop = new DIProperty( $property->findPropertyTypeID() );
		$pv = $semanticData->getPropertyValues( new DIProperty( '_LIST' ) );

		// #3522
		// Multiple `Has fields`
		if ( count( $pv ) > 1 ) {
			$this->messages[] = [
				'error',
				'smw-property-req-violation-multiple-fields',
				$property->getLabel(),
				$prop->getCanonicalLabel()
			];
		}

		// No `Has fields`
		if ( count( $pv ) == 0 ) {
			$this->messages[] = [
				'error',
				'smw-property-req-violation-missing-fields',
				$property->getLabel(),
				$prop->getCanonicalLabel()
			];
		}
	}

	private function checkExternalIdentifierType( $type, $property ) {

		if ( $type !== '_eid' ) {
			return;
		}

		$semanticData = $this->getSemanticData();

		// A violation occurs when the External Identifier typed property does
		// not declare a `External formatter URI` declaration.
		if ( $semanticData->hasProperty( new DIProperty( '_PEFU' ) ) ) {
			return;
		}

		$this->messages[] = [
			'error',
			'smw-property-req-violation-missing-formatter-uri',
			$property->getLabel()
		];
	}

	private function checkGeoType( $type, $property ) {

		if ( $type !== '_geo' ) {
			return ;
		}

		if ( defined( 'SM_VERSION' ) ) {
			return;
		}

		$this->messages[] = [
			'error',
			'smw-property-req-violation-missing-maps-extension',
			$property->getLabel()
		];
	}

	private function checkImportedVocabType( $property ) {

		$semanticData = $this->getSemanticData();

		if ( !$semanticData->hasProperty( new DIProperty( '_IMPO' ) ) ) {
			return;
		}

		if ( !$semanticData->getOption( MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE ) ) {
			return;
		}

		$typeValues = $semanticData->getPropertyValues(
			new DIProperty( '_TYPE' )
		);

		$dataItem = $semanticData->getOption(
			MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE
		);

		// A violation occurs when the `Imported from` property detects an
		// incompatible `Has type` declaration.
		if ( $dataItem instanceof DataItem && end( $typeValues )->equals( $dataItem ) ) {
			return;
		}

		$this->messages[] = [
			'warning',
			'smw-property-req-violation-import-type',
			$property->getLabel()
		];
	}

	private function checkSubpropertyParentType( $type, $property ) {

		$semanticData = $this->getSemanticData();

		if ( !$semanticData->hasProperty( new DIProperty( '_SUBP' ) ) ) {
			return;
		}

		$dataItem = $semanticData->getOption(
			MandatoryTypePropertyAnnotator::ENFORCED_PARENTTYPE_INHERITANCE
		);

		if ( $dataItem instanceof DataItem ) {

			$parentProperty = new DIProperty( $dataItem->getDBKey() );

			$this->messages[] = [
				'error',
				'smw-property-req-violation-forced-removal-annotated-type',
				$property->getLabel(),
				$parentProperty->getLabel()
			];
		}

		$pv = $semanticData->getPropertyValues(
			new DIProperty( '_SUBP' )
		);

		if ( $pv === null || $pv === [] ) {
			return;
		}

		$key = end( $pv )->getDBKey();
		$parentProperty = new DIProperty( $key );

		if ( $type === $parentProperty->findPropertyTypeID() ) {
			return;
		}

		$this->messages[] = [
			'warning',
			'smw-property-req-violation-parent-type',
			$property->getLabel(),
			$parentProperty->getLabel(),
		];
	}

}
