<?php

namespace SMW\Query\DescriptionBuilders;

use SMWDINumber as DINumber;
use SMWNumberValue as NumberValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NumberValueDescriptionBuilder extends DescriptionBuilder {

	/**
	 * @var DataValue
	 */
	private $dataValue;

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isBuilderFor( $serialization ) {
		return $serialization instanceof NumberValue;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return Description
	 */
	public function newDescription( NumberValue $dataValue, $value ) {

		$comparator = SMW_CMP_EQ;

		$this->dataValue = $dataValue;
		$property = $this->dataValue->getProperty();

		$this->prepareValue( $property, $value, $comparator );

		if( $comparator !== SMW_CMP_LIKE && $comparator !== SMW_CMP_PRIM_LIKE ) {

			$this->dataValue->setUserValue( $value );

			if ( $this->dataValue->isValid() ) {
				return $this->descriptionFactory->newValueDescription(
					$this->dataValue->getDataItem(),
					$property,
					$comparator
				);
			} else {
				return $this->descriptionFactory->newThingDescription();
			}
		}

		// Remove things that belong to SMW_CMP_LIKE
		$value = str_replace( [ '~', '*', '!' ], '', $value );

		$this->dataValue->setUserValue( $value );

		if ( !$this->dataValue->isValid() ) {
			return $this->descriptionFactory->newThingDescription();
		}

		$dataItem = $this->dataValue->getDataItem();

		if ( $this->getErrors() !== [] ) {
			return $this->descriptionFactory->newThingDescription();
		}

		// in:/~ signals a range request for a number context
		if ( $dataItem->getNumber() >= 0 ) {
			// `[[Has number::in:99]]` -> `[[Has number:: [[≥0]] [[≤99]] ]]`)
			$description = $this->descriptionFactory->newConjunction(
				[
					$this->descriptionFactory->newValueDescription( new DINumber( 0 ), $property, SMW_CMP_GEQ ),
					$this->descriptionFactory->newValueDescription( $dataItem, $property, SMW_CMP_LEQ )
				]
			);
		} else {
			// `[[Has number::in:-100]]` -> `[[Has number:: [[≥-100]] [[≤0]] ]]`
			$description = $this->descriptionFactory->newConjunction(
				[
					$this->descriptionFactory->newValueDescription( $dataItem, $property, SMW_CMP_GEQ ),
					$this->descriptionFactory->newValueDescription( new DINumber( 0 ), $property,SMW_CMP_LEQ  )
				]
			);
		}

		return $description;
	}

}
