<?php

namespace SMW\Property\Annotators;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class MandatoryTypePropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * Indicates a forced removal for imported type annotation
	 */
	const IMPO_REMOVED_TYPE = 'mandatorytype.propertyannotator.impo.removed.type';

	/**
	 * Indicates a forced removal for subproperty/parent type mismatch
	 */
	const ENFORCED_PARENTTYPE_INHERITANCE = 'mandatorytype.propertyannotator.subproperty.parent.type.inheritance';

	/**
	 * @var bool
	 */
	private $subpropertyParentTypeInheritance = false;

	/**
	 * @since 3.1
	 *
	 * @param bool $subpropertyParentTypeInheritance
	 */
	public function setSubpropertyParentTypeInheritance( $subpropertyParentTypeInheritance ): void {
		$this->subpropertyParentTypeInheritance = (bool)$subpropertyParentTypeInheritance;
	}

	protected function addPropertyValues() {
		$subject = $this->getSemanticData()->getSubject();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY ) {
			return;
		}

		$property = Property::newFromUserLabel(
			str_replace( '_', ' ', $subject->getDBKey() )
		);

		if ( !$property->isUserDefined() ) {
			return;
		}

		$this->enforceMandatoryTypeForImportVocabulary();

		// #3528
		$this->enforceMandatoryTypeForSubproperty();
	}

	private function enforceMandatoryTypeForSubproperty(): void {
		if ( !$this->subpropertyParentTypeInheritance ) {
			return;
		}

		$property = new Property( '_SUBP' );
		$semanticData = $this->getSemanticData();

		if ( !$semanticData->hasProperty( $property ) ) {
			return;
		}

		$dataItems = $semanticData->getPropertyValues(
			$property
		);

		$dataItem = end( $dataItems );
		$parentProperty = Property::newFromUserLabel( $dataItem->getDBKey() );

		if ( $parentProperty->isUserDefined() ) {
			$type_id = $parentProperty->findPropertyTypeID();
		} else {
			$type_id = $parentProperty->getKey();
		}

		$semanticData->removeProperty( new Property( '_TYPE' ) );

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			new Property( '_TYPE' ),
			$type_id
		);

		$semanticData->setOption( self::ENFORCED_PARENTTYPE_INHERITANCE, $dataItem );
		$semanticData->addDataValue( $dataValue );
	}

	private function enforceMandatoryTypeForImportVocabulary(): void {
		$property = new Property( '_IMPO' );

		$dataItems = $this->getSemanticData()->getPropertyValues(
			$property
		);

		if ( $dataItems === null || $dataItems === [] ) {
			return;
		}

		$this->addTypeFromImportVocabulary( $property, current( $dataItems ) );
	}

	private function addTypeFromImportVocabulary( Property $property, DataItem $dataItem ): void {
		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		if ( strpos( $importValue->getTermType(), ':' ) === false ) {
			return;
		}

		$property = new Property( '_TYPE' );

		[ $ns, $type ] = explode( ':', $importValue->getTermType(), 2 );

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

	private function replaceAnyTypeByImportType( Property $property, $dataValue ): void {
		foreach ( $this->getSemanticData()->getPropertyValues( $property ) as $dataItem ) {
			$this->getSemanticData()->setOption( self::IMPO_REMOVED_TYPE, $dataItem );

			$this->getSemanticData()->removePropertyObjectValue(
				$property,
				$dataItem
			);
		}

		$this->getSemanticData()->addDataValue( $dataValue );
	}

}
