<?php

namespace SMW\Query\DescriptionBuilders;

use InvalidArgumentException;
use SMW\DataValueFactory;
use SMW\DataValues\MonolingualTextValue;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMWDIBlob as DIBlob;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueDescriptionBuilder extends DescriptionBuilder {

	/**
	 * @var DataValue
	 */
	private $dataValue;

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isBuilderFor( $serialization ) {
		return $serialization instanceof MonolingualTextValue;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function newDescription( MonolingualTextValue $dataValue, $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'Value needs to be a string' );
		}

		if ( $value === '' ) {
			$this->addError( wfMessage( 'smw_novalues' )->text() );
			return new ThingDescription();
		}

		$this->dataValue = $dataValue;

		$subdescriptions = [];
		list( $text, $languageCode ) = $this->dataValue->getValuesFromString( $value );

		foreach ( $this->dataValue->getPropertyDataItems() as $property ) {

			// If the DVFeature doesn't require a language code to be present then
			// allow to skip it as conjunctive condition when it is empty
			if (
				( $languageCode === '' ) &&
				( $property->getKey() === '_LCODE' ) &&
				( !$this->dataValue->hasFeature( SMW_DV_MLTV_LCODE ) ) ) {
				continue;
			}

			$value = $property->getKey() === '_LCODE' ? $languageCode : $text;
			$comparator = SMW_CMP_EQ;

			$this->prepareValue( $this->dataValue->getProperty(), $value, $comparator );

			// Directly use the DI instead of going through the DVFactory to
			// avoid having ~zh-* being validated when building a DV
			// If one of the values is empty use, ? so queries can be arbitrary
			// in respect of the query condition
			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				new DIBlob( $value === '' ? '?' : $value ),
				$property,
				false,
				$this->dataValue->getContextPage()
			);

			if ( !$dataValue->isValid() ) {
				$this->addError( $dataValue->getErrors() );
				continue;
			}

			$subdescriptions[] = $this->newSubdescription( $dataValue, $comparator );
		}

		return $this->newConjunction( $subdescriptions );
	}

	private function newConjunction( $subdescriptions ) {

		$count = count( $subdescriptions );

		if ( $count == 0 ) {
			return new ThingDescription();
		}

		if ( $count == 1 ) {
			return reset( $subdescriptions );
		}

		return new Conjunction( $subdescriptions );
	}

	private function newSubdescription( $dataValue, $comparator ) {

		$description = new ValueDescription(
			$dataValue->getDataItem(),
			$dataValue->getProperty(),
			$comparator
		);

		if ( $dataValue->getWikiValue() === '+' || $dataValue->getWikiValue() === '?' ) {
			$description = new ThingDescription();
		}

		return new SomeProperty(
			$dataValue->getProperty(),
			$description
		);
	}

}
