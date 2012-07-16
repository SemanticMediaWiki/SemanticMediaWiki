<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Time data items.
 *
 * @since SMW.storerewrite
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerTime implements SMWDataItemHandler {

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'value_xsd' => 't', 'value_num' => 'f' );
	}

	/**
	 * Method to return array of indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'value_num', 'value_xsd' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		$xsdvalue = $dataItem->getYear() . "/" .
				( ( $dataItem->getPrecision() >= SMWDITime::PREC_YM ) ? $dataItem->getMonth() : '' ) . "/" .
				( ( $dataItem->getPrecision() >= SMWDITime::PREC_YMD ) ? $dataItem->getDay() : '' ) . "T";
		if ( $dataItem->getPrecision() == SMWDITime::PREC_YMDT ) {
			$xsdvalue .= sprintf( "%02d", $dataItem->getHour() ) . ':' .
					sprintf( "%02d", $dataItem->getMinute()) . ':' .
					sprintf( "%02d", $dataItem->getSecond() );
		}

		return array(
			'value_xsd' => $xsdvalue,
			'value_num' => $dataItem->getSortKey()
			);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		$xsdvalue = $dataItem->getYear() . "/" .
				( ( $dataItem->getPrecision() >= SMWDITime::PREC_YM ) ? $dataItem->getMonth() : '' ) . "/" .
				( ( $dataItem->getPrecision() >= SMWDITime::PREC_YMD ) ? $dataItem->getDay() : '' ) . "T";
		if ( $dataItem->getPrecision() == SMWDITime::PREC_YMDT ) {
			$xsdvalue .= sprintf( "%02d", $dataItem->getHour() ) . ':' .
					sprintf( "%02d", $dataItem->getMinute()) . ':' .
					sprintf( "%02d", $dataItem->getSecond() );
		}

		return array(
			'value_xsd' => $xsdvalue,
			'value_num' => $dataItem->getSortKey()
			);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField() {
		return 'value_num';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField() {
		return 'value_xsd';
	}

	/**
	 * Method to create a dataitem from a type ID and array of DB keys.
	 *
	 * @since SMW.storerewrite
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $typeId, $dbkeys ) {
		$timedate = explode( 'T', $dbkeys[0], 2 );
		if ( ( count( $dbkeys ) == 2 ) && ( count( $timedate ) == 2 ) ) {
			$date = reset( $timedate );
			$year = $month = $day = $hours = $minutes = $seconds = $timeoffset = false;
			if ( ( end( $timedate ) === '' ) ||
				 ( SMWTimeValue::parseTimeString( end( $timedate ), $hours, $minutes, $seconds, $timeoffset ) == true ) ) {
				$d = explode( '/', $date, 3 );
				if ( count( $d ) == 3 ) {
					list( $year, $month, $day ) = $d;
				} elseif ( count( $d ) == 2 ) {
					list( $year, $month ) = $d;
				} elseif ( count( $d ) == 1 ) {
					list( $year ) = $d;
				}
				if ( $month === '' ) $month = false;
				if ( $day === '' ) $day = false;
				$calendarmodel = SMWDITime::CM_GREGORIAN;
				return new SMWDITime( $calendarmodel, $year, $month, $day, $hours, $minutes, $seconds );
			}
		}
		throw new SMWDataItemException( 'Failed to create data item from DB keys.' );		
	}
}
