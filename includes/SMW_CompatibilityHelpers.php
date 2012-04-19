<?php
/**
 * This file contains the SMWCompatibilityHelper class.
 * @note This file and its contents will vanish before SMW 1.7. Please modify your code to not require these helpers.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMW
 */

/**
 * Helper class to collect various static functions that provide some
 * interfaces used in SMW 1.6 that are no longer available in SMW 1.7.
 * In particular, this relates to the new data model based on data items
 * instead of data value classes with DB key arrays and singatures.
 * 
 * @since 1.6
 * @note This class will vanish before SMW 1.7. Please change your code to not require the old interfaces at all.
 *
 * @ingroup SMW
 */
class SMWCompatibilityHelpers {

	/**
	 * Method to create a dataitem from a type ID and array of DB keys.
	 * Throws SMWDataItemException if problems occur, to get our callers
	 * used to it.
	 *
	 * @param $typeid string id for the given type
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	static public function dataItemFromDBKeys( $typeid, $dbkeys ) {
		switch ( SMWDataValueFactory::getDataItemId( $typeid )  ) {
			case SMWDataItem::TYPE_ERROR: case SMWDataItem::TYPE_NOTYPE:
				break;
			case SMWDataItem::TYPE_NUMBER:
				return SMWDINumber::doUnserialize( $dbkeys[0] );
			case SMWDataItem::TYPE_STRING:
				return new SMWDIString( $dbkeys[0] );
			case SMWDataItem::TYPE_BLOB:
				return new SMWDIBlob( $dbkeys[0] );
			case SMWDataItem::TYPE_BOOLEAN:
				return new SMWDIBoolean( ( $dbkeys[0] == '1' ) );
			case SMWDataItem::TYPE_URI:
				if ( $typeid == '__typ' && $dbkeys[0]{0} == '_' ) { // b/c: old data stored as type ids
					return SMWTypesValue::getTypeUriFromTypeId( $dbkeys[0] );
				} else {
					return SMWDIUri::doUnserialize( $dbkeys[0] );
				}
			case SMWDataItem::TYPE_TIME:
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
				break;
			case SMWDataItem::TYPE_GEO:
				return new SMWDIGeoCoord( array( 'lat' => (float)$dbkeys[0], 'lon' => (float)$dbkeys[1] ) );
			case SMWDataItem::TYPE_CONTAINER:
				// provided for backwards compatibility only;
				// today containers are read from the store as substructures,
				// not retrieved as single complex values
				$semanticData = SMWContainerSemanticData::makeAnonymousContainer();
				foreach ( reset( $dbkeys ) as $value ) {
					if ( is_array( $value ) && ( count( $value ) == 2 ) ) {
						$diP = new SMWDIProperty( reset( $value ), false );
						$diV = self::dataItemFromDBKeys( $diP->findPropertyTypeID(), end( $value ) );
						$semanticData->addPropertyObjectValue( $diP, $diV );
					}
				}
				return new SMWDIContainer( $semanticData );
			case SMWDataItem::TYPE_WIKIPAGE:
				if ( $typeid == '__spf' ) {
					$pagedbkey = str_replace( ' ', '_', $dbkeys[0] );
					return new SMWDIWikiPage( $pagedbkey, SF_NS_FORM, '' );
				} elseif ( count( $dbkeys ) >= 5 ) { // with subobject name (and sortkey)
					return new SMWDIWikiPage( $dbkeys[0], intval( $dbkeys[1] ), $dbkeys[2], $dbkeys[4] );
				} elseif ( count( $dbkeys ) >= 3 ) { // without subobject name (just for b/c)
					return new SMWDIWikiPage( $dbkeys[0], intval( $dbkeys[1] ), $dbkeys[2] );
				}
				break;
			case SMWDataItem::TYPE_CONCEPT:
				if ( count( $dbkeys ) >= 5 ) {
					return new SMWDIConcept( $dbkeys[0], smwfXMLContentEncode( $dbkeys[1] ),
						$dbkeys[2], $dbkeys[3], $dbkeys[4] );
				}
				break;
			case SMWDataItem::TYPE_PROPERTY:
				return new SMWDIProperty( $dbkeys[0], false );
		}
		throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
	}

	/**
	 * Compatibility function for computing the old getDBkeys() array for
	 * the new SMW data items.
	 *
	 * @param $dataItem SMWDataItem
	 * @return array of mixed
	 */
	public static function getDBkeysFromDataItem( SMWDataItem $dataItem ) {
		switch ( $dataItem->getDIType() ) {
			case SMWDataItem::TYPE_STRING: case SMWDataItem::TYPE_BLOB:
				return array( $dataItem->getString() );
			case SMWDataItem::TYPE_URI:
				return array( $dataItem->getSerialization() );
			case SMWDataItem::TYPE_WIKIPAGE:
				return array( $dataItem->getDBkey(), $dataItem->getNamespace(), $dataItem->getInterwiki(), $dataItem->getSortKey() );
			case SMWDataItem::TYPE_NUMBER:
				return array( $dataItem->getSerialization(), floatval( $dataItem->getNumber() ) );
			case SMWDataItem::TYPE_TIME:
				$xsdvalue = $dataItem->getYear() . "/" .
						( ( $dataItem->getPrecision() >= SMWDITime::PREC_YM ) ? $dataItem->getMonth() : '' ) . "/" .
						( ( $dataItem->getPrecision() >= SMWDITime::PREC_YMD ) ? $dataItem->getDay() : '' ) . "T";
				if ( $dataItem->getPrecision() == SMWDITime::PREC_YMDT ) {
					$xsdvalue .= sprintf( "%02d", $dataItem->getHour() ) . ':' .
							sprintf( "%02d", $dataItem->getMinute()) . ':' .
							sprintf( "%02d", $dataItem->getSecond() );
				}
				return array( $xsdvalue, $dataItem->getSortKey() );
			case SMWDataItem::TYPE_BOOLEAN:
				return $dataItem->getBoolean() ? array( '1', 1 ) : array( '0', 0 );
			case SMWDataItem::TYPE_CONTAINER:
				return array( false );
			case SMWDataItem::TYPE_CONCEPT:
				return array( $dataItem->getConceptQuery(), $dataItem->getDocumentation(), $dataItem->getQueryFeatures(), $dataItem->getSize(), $dataItem->getDepth() );
			case SMWDataItem::TYPE_PROPERTY:
				return array( $dataItem->getKey() );
			case SMWDataItem::TYPE_GEO:
				$coordinateSet = $dataItem->getCoordinateSet();
				return array(
					$coordinateSet['lat'],
					$coordinateSet['lon']
				);
			default:
				return array( false );
		}
	}

	/**
	 * Compatibility function for computing the old getSignature() string
	 * based on dataitem IDs. To maintain full compatibility, the typeid
	 * is relevant here, too.
	 *
	 * @note Use SMWDataValueFactory::getDataItemId() if only the $typeid
	 * is known.
	 *
	 * @param $dataItemId integer
	 * @param $typeid string
	 * @return string
	 */
	public static function getSignatureFromDataItemId( $dataItemId, $typeid ) {
		switch ( $dataItemId ) {
			case SMWDataItem::TYPE_STRING:
				return ( ( $typeid == '_txt' ) || ( $typeid == '_cod' ) ) ? 'l' : 't';
			case SMWDataItem::TYPE_URI: case SMWDataItem::TYPE_PROPERTY:
				return 't';
			case SMWDataItem::TYPE_WIKIPAGE:
				return 'tnwt';
			case SMWDataItem::TYPE_NUMBER: case SMWDataItem::TYPE_TIME:
				return 'tf';
			case SMWDataItem::TYPE_BOOLEAN:
				return 'tn';
			case SMWDataItem::TYPE_CONTAINER:
				return 'c';
			case SMWDataItem::TYPE_CONCEPT:
				return 'llnnn';
			case SMWDataItem::TYPE_GEO:
				return 'ff';
			default:
				return '';
		}
	}

	/**
	 * Compatibility function for computing the old getValueIndex() and
	 * getLabelIndex() numbers based on dataitem IDs. To maintain full
	 * compatibility, the typeid is relevant here, too.
	 *
	 * @note Use SMWDataValueFactory::getDataItemId() if only the $typeid
	 * is known.
	 *
	 * @param $dataItemId integer
	 * @param $typeid string
	 * @param $labelIndex boolean, if true get the label index, else the value index
	 * @return string
	 */
	public static function getIndexFromDataItemId( $dataItemId, $typeid, $labelIndex = false ) {
		switch ( $dataItemId ) {
			case SMWDataItem::TYPE_STRING:
				return ( ( $typeid == '_txt' ) || ( $typeid == '_cod' ) ) ? -1 : 0;
			case SMWDataItem::TYPE_WIKIPAGE:
				return 3;
			case SMWDataItem::TYPE_NUMBER: case SMWDataItem::TYPE_TIME: case SMWDataItem::TYPE_BOOLEAN:
				return $labelIndex ? 0 : 1;
			case SMWDataItem::TYPE_URI: case SMWDataItem::TYPE_PROPERTY:
			case SMWDataItem::TYPE_CONCEPT: case SMWDataItem::TYPE_GEO:
				return 0;
			case SMWDataItem::TYPE_CONTAINER: default:
				return -1;
		}
	}

}
