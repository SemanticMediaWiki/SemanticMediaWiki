<?php

namespace SMW\Annotator;

use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMW\DataValueFactory;
use SMW\DataTypeRegistry;
use SMW\Store;
use SMWErrorValue as ErrorValue;

/**
 * Adding type from an import reference
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class TypeByImportPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @since 2.2
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param Store $store
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator ) {
		parent::__construct( $propertyAnnotator );
	}

	protected function addPropertyValues() {

		$subject = $this->getSemanticData()->getSubject();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$property = DIProperty::newFromUserLabel( str_replace( '_', ' ', $subject->getDBKey() ) );

		if ( !$property->isUserDefined() ) {
			return;
		}

		$dataItems = $this->getSemanticData()->getPropertyValues(
			new DIProperty( '_IMPO' )
		);

		if ( $dataItems === null || $dataItems === array() ) {
			return;
		}

		$this->addTypeFromImportVocabulary( current( $dataItems ) );
	}

	private function addTypeFromImportVocabulary( $dataItem ) {

		$importValue = DataValueFactory::getInstance()->newDataItemValue(
			$dataItem,
			new DIProperty( '_IMPO' )
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

		$dataValue = DataValueFactory::getInstance()->newPropertyObjectValue(
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
