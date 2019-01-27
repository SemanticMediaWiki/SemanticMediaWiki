<?php

namespace SMW\Query\DescriptionBuilders;

use InvalidArgumentException;
use SMW\DataValueFactory;
use SMW\DataValues\ReferenceValue;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMWRecordValue as RecordValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class RecordValueDescriptionBuilder extends DescriptionBuilder {

	/**
	 * @var DataValue
	 */
	private $dataValue;

	/**
	 * @since 2.3
	 *
	 * {@inheritDoc}
	 */
	public function isBuilderFor( $serialization ) {
		return $serialization instanceof RecordValue || $serialization instanceof ReferenceValue;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function newDescription( $dataValue, $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'value needs to be a string' );
		}

		$this->dataValue = $dataValue;

		if ( $value === '' ) {
			$this->addError( wfMessage( 'smw_novalues' )->text() );
			return new ThingDescription();
		}

		$subdescriptions = [];
		$values = $this->dataValue->getValuesFromString( $value );

		$valueIndex = 0; // index in value array
		$propertyIndex = 0; // index in property list

		foreach ( $this->dataValue->getPropertyDataItems() as $diProperty ) {

			// stop if there are no values left
			if ( !is_array( $values ) || !array_key_exists( $valueIndex, $values ) ) {
				break;
			}

			$description = $this->getDescriptionForProperty(
				$diProperty,
				$values,
				$valueIndex,
				$propertyIndex
			);

			if ( $description !== null ) {
				 $subdescriptions[] = $description;
			}

			++$propertyIndex;
		}

		if ( $subdescriptions === [] ) {
			$this->addError( wfMessage( 'smw_novalues' )->text() );
		}

		return $this->getDescriptionFor( $subdescriptions );
	}

	private function getDescriptionFor( $subdescriptions ) {
		switch ( count( $subdescriptions ) ) {
			case 0:
			return new ThingDescription();
			case 1:
			return reset( $subdescriptions );
			default:
			return new Conjunction( $subdescriptions );
		}
	}

	private function getDescriptionForProperty( $diProperty, $values, &$valueIndex, $propertyIndex ) {

		$values[$valueIndex] = str_replace( "-3B", ";", $values[$valueIndex] );
		$beforePrepareValue = $values[$valueIndex];

		$description = null;
		$comparator = SMW_CMP_EQ;

		$this->prepareValue( $this->dataValue->getProperty(), $values[$valueIndex], $comparator );

		// generating the DVs:
		if ( ( $values[$valueIndex] === '' ) || ( $values[$valueIndex] == '?' ) ) { // explicit omission
			$valueIndex++;
			return $description;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			$diProperty,
			$values[$valueIndex],
			false,
			$this->dataValue->getContextPage()
		);

		if ( $dataValue->isValid() ) { // valid DV: keep
			$description = new SomeProperty(
				$diProperty,
				$dataValue->getQueryDescription( $beforePrepareValue )
			);
			$valueIndex++;
		} elseif ( ( count( $values ) - $valueIndex ) == ( count( $this->dataValue->getProperties() ) - $propertyIndex ) ) {
			$this->addError( $dataValue->getErrors() );
			++$valueIndex;
		}

		return $description;
	}

}
