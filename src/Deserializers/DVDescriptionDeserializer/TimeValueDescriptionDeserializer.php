<?php

namespace SMW\Deserializers\DVDescriptionDeserializer;

use SMWTimeValue as TimeValue;
use SMWDITime as DITime;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\Conjunction;
use InvalidArgumentException;
use DateTime;
use DateInterval;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class TimeValueDescriptionDeserializer extends DescriptionDeserializer {

	/**
	 * @since 2.3
	 *
	 * {@inheritDoc}
	 */
	public function isDeserializerFor( $serialization ) {
		return $serialization instanceof TimeValue;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function deserialize( $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'The value needs to be a string' );
		}

		$comparator = SMW_CMP_EQ;
		$this->prepareValue( $value, $comparator );

		if( $comparator !== SMW_CMP_LIKE && $comparator !== SMW_CMP_NLKE ) {

			$this->dataValue->setUserValue( $value );

			if ( $this->dataValue->isValid() ) {
				return new ValueDescription( $this->dataValue->getDataItem(), $this->dataValue->getProperty(), $comparator );
			} else {
				return new ThingDescription();
			}
		}

		$this->dataValue->setUserValue( $value, false, true );

		if ( !$this->dataValue->isValid() ) {
			return new ThingDescription();
		}

		$dataItem = $this->dataValue->getDataItem();
		$property = $this->dataValue->getProperty();

		$upperLimitDataItem = $this->getUpperLimit( $dataItem );

		if ( $this->getErrors() !== array() ) {
			return new ThingDescription();
		}

		if( $comparator === SMW_CMP_LIKE ) {
			$description = new Conjunction( array(
				new ValueDescription( $dataItem, $property, SMW_CMP_GEQ ),
				new ValueDescription( $upperLimitDataItem, $property, SMW_CMP_LESS )
			) );
		}

		if( $comparator === SMW_CMP_NLKE ) {
			$description = new Disjunction( array(
				new ValueDescription( $dataItem, $property, SMW_CMP_LESS ),
				new ValueDescription( $upperLimitDataItem, $property, SMW_CMP_GEQ )
			) );
		}

		return $description;
	}

	private function getUpperLimit( $dataItem ) {

		$prec = $dataItem->getPrecision();

		$dateTime = new DateTime();
		$dateTime->setDate( $dataItem->getYear(), $dataItem->getMonth(), $dataItem->getDay() );
		$dateTime->setTime( $dataItem->getHour(), $dataItem->getMinute(), $dataItem->getSecond() );

		if ( $dateTime === false ) {
			return $this->addError( 'Cannot compute interval for ' . $dataItem->getSerialization() );
		}

		if ( $prec === DITime::PREC_Y ) {
			$dateTime->add( new DateInterval( 'P1Y' ) );
		} elseif( $prec === DITime::PREC_YM ) {
			$dateTime->add( new DateInterval( 'P1M' ) );
		} elseif( $prec === DITime::PREC_YMD ) {
			$dateTime->add( new DateInterval( 'P1D' ) );
		} elseif( $prec === DITime::PREC_YMDT ) {

			if ( $dataItem->getSecond() > 0 ) {
				$dateTime->add( new DateInterval( 'PT1S' ) );
			} elseif( $dataItem->getMinute() > 0 ) {
				$dateTime->add( new DateInterval( 'PT1M' ) );
			} elseif( $dataItem->getHour() > 0 ) {
				$dateTime->add( new DateInterval( 'PT1H' ) );
			} else {
				$dateTime->add( new DateInterval( 'PT24H' ) );
			}
		}

		return DITime::doUnserialize( $dataItem->getCalendarModel() . '/' . $dateTime->format( 'Y/m/d/H/i/s' ) );
	}

}
