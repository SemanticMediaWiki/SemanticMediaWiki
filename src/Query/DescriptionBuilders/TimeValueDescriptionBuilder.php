<?php

namespace SMW\Query\DescriptionBuilders;

use DateInterval;
use InvalidArgumentException;
use SMWDITime as DITime;
use SMWTimeValue as TimeValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class TimeValueDescriptionBuilder extends DescriptionBuilder {

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
		return $serialization instanceof TimeValue;
	}

	/**
	 * @since 2.3
	 *
	 * @param TimeValue $dataValue
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function newDescription( TimeValue $dataValue, $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'The value needs to be a string' );
		}

		$this->dataValue = $dataValue;
		$property = $this->dataValue->getProperty();

		$comparator = SMW_CMP_EQ;
		$this->prepareValue( $property, $value, $comparator );

		if( $comparator !== SMW_CMP_LIKE && $comparator !== SMW_CMP_NLKE ) {

			$this->dataValue->setUserValue( $value );

			if ( $this->dataValue->isValid() ) {
				return $this->descriptionFactory->newValueDescription( $this->dataValue->getDataItem(), $property, $comparator );
			} else {
				return $this->descriptionFactory->newThingDescription();
			}
		}

		// #1178 to support queries like [[Has date::~ Dec 2001]]
		$this->dataValue->setOption( TimeValue::OPT_QUERY_COMP_CONTEXT, true );
		$this->dataValue->setUserValue( $value );

		if ( !$this->dataValue->isValid() ) {
			return $this->descriptionFactory->newThingDescription();
		}

		$dataItem = $this->dataValue->getDataItem();
		$property = $this->dataValue->getProperty();

		$upperLimitDataItem = $this->getUpperLimit( $dataItem );

		if ( $this->getErrors() !== [] ) {
			return $this->descriptionFactory->newThingDescription();
		}

		if( $comparator === SMW_CMP_LIKE ) {
			$description = $this->descriptionFactory->newConjunction( [
				$this->descriptionFactory->newValueDescription( $dataItem, $property, SMW_CMP_GEQ ),
				$this->descriptionFactory->newValueDescription( $upperLimitDataItem, $property, SMW_CMP_LESS )
			] );
		}

		if( $comparator === SMW_CMP_NLKE ) {
			$description = $this->descriptionFactory->newDisjunction( [
				$this->descriptionFactory->newValueDescription( $dataItem, $property, SMW_CMP_LESS ),
				$this->descriptionFactory->newValueDescription( $upperLimitDataItem, $property, SMW_CMP_GEQ )
			] );
		}

		return $description;
	}

	private function getUpperLimit( $dataItem ) {

		$prec = $dataItem->getPrecision();
		$dateTime = $dataItem->asDateTime();

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
