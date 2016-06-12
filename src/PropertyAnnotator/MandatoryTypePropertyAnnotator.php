<?php

namespace SMW\PropertyAnnotator;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MandatoryTypePropertyAnnotator extends PropertyAnnotatorDecorator {

	protected function addPropertyValues() {

		$subject = $this->getSemanticData()->getSubject();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$property = DIProperty::newFromUserLabel(
			str_replace( '_', ' ', $subject->getDBKey() )
		);

		if ( !$property->isUserDefined() ) {
			return;
		}

		$this->findMandatoryTypeForImportVocabulary();
	}

	private function findMandatoryTypeForImportVocabulary() {

		$property = new DIProperty( '_IMPO' );

		$dataItems = $this->getSemanticData()->getPropertyValues(
			$property
		);

		if ( $dataItems === null || $dataItems === array() ) {
			return;
		}

		$this->addTypeFromImportVocabulary( $property, current( $dataItems ) );
	}

	private function addTypeFromImportVocabulary( $property, $dataItem ) {

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		if ( strpos( $importValue->getTermType(), ':' ) === false ) {
			return;
		}

		$property = new DIProperty( '_TYPE' );

		list( $ns, $type ) = explode( ':', $importValue->getTermType(), 2 );

		$typeId = DataTypeRegistry::getInstance()->findTypeId( $type );

		if ( $typeId === '' ) {
			return;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			$property,
			$typeId
		);

		$this->replaceAnyTypeByImportType( $property, $dataValue );
	}

	private function replaceAnyTypeByImportType( DIProperty $property, $dataValue ) {

		foreach ( $this->getSemanticData()->getPropertyValues( $property ) as $dataItem ) {
			$this->getSemanticData()->removePropertyObjectValue(
				$property,
				$dataItem
			);
		}

		$this->getSemanticData()->addDataValue( $dataValue );
	}

}
