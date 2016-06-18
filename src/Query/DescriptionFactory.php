<?php

namespace SMW\Query;

use SMW\DataValues\MonolingualTextValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionFactory {

	/**
	 * @since 2.4
	 *
	 * @param DataItem $dataItem
	 * @param DIProperty|null $property = null
	 * @param integer $comparator
	 *
	 * @return ValueDescription
	 */
	public function newValueDescription( DataItem $dataItem, DIProperty $property = null, $comparator = SMW_CMP_EQ ) {
		return new ValueDescription( $dataItem, $property, $comparator );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param Description $description
	 *
	 * @return SomeProperty
	 */
	public function newSomeProperty( DIProperty $property, Description $description ) {
		return new SomeProperty( $property, $description );
	}

	/**
	 * @since 2.4
	 *
	 * @return ThingDescription
	 */
	public function newThingDescription() {
		return new ThingDescription();
	}

	/**
	 * @since 2.4
	 *
	 * @param Description[] $descriptions
	 *
	 * @return Disjunction
	 */
	public function newDisjunction( $descriptions = array() ) {
		return new Disjunction( $descriptions );
	}

	/**
	 * @since 2.4
	 *
	 * @param Description[] $descriptions
	 *
	 * @return Conjunction
	 */
	public function newConjunction( $descriptions = array() ) {
		return new Conjunction( $descriptions );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $ns
	 *
	 * @return NamespaceDescription
	 */
	public function newNamespaceDescription( $ns ) {
		return new NamespaceDescription( $ns );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $category
	 *
	 * @return ClassDescription
	 */
	public function newClassDescription( DIWikiPage $category ) {
		return new ClassDescription( $category );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $concept
	 *
	 * @return ConceptDescription
	 */
	public function newConceptDescription( DIWikiPage $concept ) {
		return new ConceptDescription( $concept );
	}

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 *
	 * @return Description
	 */
	public function newFromDataValue( DataValue $dataValue ) {

		if ( !$dataValue->isValid() ) {
			return $this->newThingDescription();
		}

		// RecordValue is missing

		// FIXME This knowledge should reside with the DV itself
		if ( $dataValue instanceof MonolingualTextValue ) {
			$container =  $dataValue->getDataItem();

			$value = '';

			foreach ( $dataValue->getPropertyDataItems() as $property ) {
				foreach ( $container->getSemanticData()->getPropertyValues( $property ) as $val ) {
					$value .= ( $property->getKey() == '_LCODE' ? '@' : '' ) . $val->getString();
				}
			}

			return $dataValue->getQueryDescription( $value );
		}

		return $this->newSomeProperty(
			$dataValue->getProperty(),
			$this->newValueDescription( $dataValue->getDataItem() )
		);
	}

}
