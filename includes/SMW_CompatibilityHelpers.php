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
	 * @param $diProperty mixed SMWDIProperty or null, the property for which this value is built, currently needed for records
	 *
	 * @return SMWDataItem
	 */
	static public function dataItemFromDBKeys( $typeid, $dbkeys, $diProperty = null ) {
		switch ( SMWDataValueFactory::getDataItemId( $typeid )  ) {
			case SMWDataItem::TYPE_ERROR:
				break;
			case SMWDataItem::TYPE_NUMBER:
				return SMWDINumber::doUnserialize( $dbkeys[0], $typeid );
			case SMWDataItem::TYPE_STRING:
				return new SMWDIString( $dbkeys[0], $typeid );
			case SMWDataItem::TYPE_BLOB:
				return new SMWDIBlob( $dbkeys[0], $typeid );
			case SMWDataItem::TYPE_BOOLEAN:
				return new SMWDIBoolean( ( $dbkeys[0] == '1' ), $typeid );
			case SMWDataItem::TYPE_URI:
				return SMWDIUri::doUnserialize( $dbkeys[0], $typeid);
			case SMWDataItem::TYPE_TIME:
				$timedate = explode( 'T', $dbkeys[0], 2 );
				if ( ( count( $dbkeys ) == 2 ) && ( count( $timedate ) == 2 ) ) {
					$date = reset( $timedate );
					$year = $month = $day = $hours = $minutes = $seconds = $timeoffset = false;
					if ( ( end( $timedate ) == '' ) ||
					     ( SMWTimeValue::parseTimeString( end( $timedate ), $hours, $minutes, $seconds, $timeoffset ) == true ) ) {
						$d = explode( '/', $date, 3 );
						if ( count( $d ) == 3 ) {
							list( $year, $month, $day ) = $d;
						} elseif ( count( $d ) == 2 ) {
							list( $year, $month ) = $d;
						} elseif ( count( $d ) == 1 ) {
							list( $year ) = $d;
						}
						if ( $month == '' ) $month = false;
						if ( $day == '' ) $day = false;
						$calendarmodel = SMWDITime::CM_GREGORIAN;
						return new SMWDITime( $calendarmodel, $year, $month, $day, $hours, $minutes, $seconds, $typeid );
					}
				}
				break;
			case SMWDataItem::TYPE_GEO:
				return new SMWDIGeoCoord( array( 'lat' => (float)$dbkeys[0], 'lon' => (float)$dbkeys[1] ), $typeid );
			case SMWDataItem::TYPE_CONTAINER:
				$semanticData = new SMWContainerSemanticData();
				if ( $typeid == '_rec' ) {
					$types = SMWRecordValue::findTypeIds( $diProperty );
					foreach ( reset( $dbkeys ) as $value ) {
						if ( is_array( $value ) && ( count( $value ) == 2 ) ) {
							$diP = new SMWDIProperty( reset( $value ), false );
							$pnum = intval( substr( reset( $value ), 1 ) ); // try to find the number of this property
							if ( array_key_exists( $pnum - 1, $types ) ) {
								$diV = self::dataItemFromDBKeys( $types[$pnum - 1], end( $value ) );
								$semanticData->addPropertyObjectValue( $diP, $diV );
							}
						}
					}
				} else {
					foreach ( reset( $dbkeys ) as $value ) {
						if ( is_array( $value ) && ( count( $value ) == 2 ) ) {
							$diP = new SMWDIProperty( reset( $value ), false );
							$diV = self::dataItemFromDBKeys( $diP->findPropertyTypeID(), end( $value ) );
							$semanticData->addPropertyObjectValue( $diP, $diV );
						}
					}
				}
				return new SMWDIContainer( $semanticData, $typeid );
			case SMWDataItem::TYPE_WIKIPAGE:
				if ( $typeid == '__typ' ) { // DBkeys for types values are special (used to be a SMWSimpleWikiPageValue)
					$pagedbkey = str_replace( ' ', '_', SMWDataValueFactory::findTypeLabel( $dbkeys[0] ) );
					return new SMWDIWikiPage( $pagedbkey, SMW_NS_TYPE, '', $typeid );
				} elseif ( $typeid == '__spf' ) {
					$pagedbkey = str_replace( ' ', '_', SMWDataValueFactory::findTypeLabel( $dbkeys[0] ) );
					return new SMWDIWikiPage( $pagedbkey, SF_NS_FORM, '', $typeid );
				} elseif ( count( $dbkeys ) >= 3 ) {
					return new SMWDIWikiPage( $dbkeys[0], floatval( $dbkeys[1] ), $dbkeys[2], $typeid );
				}
				break;
			case SMWDataItem::TYPE_CONCEPT:
				if ( count( $dbkeys ) >= 5 ) {
					new SMWDIConcept( $dbkeys[0], smwfXMLContentEncode( $dbkeys[1] ), $dbkeys[2], $dbkeys[3], $dbkeys[4], $typeid );
				}
				break;
			case SMWDataItem::TYPE_PROPERTY:
				return new SMWDIProperty( $dbkeys[0], false, $typeid );
			case SMWDataItem::TYPE_NOTYPE: 
				if ( ( $typeid != '' ) && ( $typeid{0} != '_' ) ) { // linear conversion type
					return SMWDINumber::doUnserialize( $dbkeys[0], $typeid );
				}
		}
		throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
	}

	/**
	 * Compatibility function for computing the old getDBkeys() array for the new SMW data items.
	 */
	public static function getDBkeysFromDataItem( SMWDataItem $dataItem ) {
		switch ( $dataItem->getTypeId() ) {
			case '_txt': case '_cod': case '_str': case '__sps': case '__tls': case '__imp':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_STRING ) break;
				return array( $dataItem->getString() );
			case '_ema': case '_uri': case '_anu': case '_tel': case '__spu':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_URI ) break;
				return array( $dataItem->getSerialization() );
			case '_wpg': case '_wpp': case '_wpc': case '_wpf': case '__sup':
			case '__suc': case '__spf': case '__sin': case '__red':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_WIKIPAGE ) break;
				return array( $dataItem->getDBkey(), $dataItem->getNamespace(), $dataItem->getInterwiki(), $dataItem->getDBkey() );
			case '_num': case '_tem': case '__lin':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_NUMBER ) break;
				return array( $dataItem->getSerialization(), floatval( $dataItem->getNumber() ) );
			case '_dat':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_TIME ) break;
				$xsdvalue = $dataItem->getYear() . "/" .
						( ( $dataItem->getPrecision() >= SMWDITime::PREC_YM ) ? $dataItem->getMonth() : '' ) . "/" .
						( ( $dataItem->getPrecision() >= SMWDITime::PREC_YMD ) ? $dataItem->getDay() : '' ) . "T";
				if ( $dataItem->getPrecision() == SMWDITime::PREC_YMDT ) {
					$xsdvalue .= sprintf( "%02d", $dataItem->getHour() ) . ':' .
							sprintf( "%02d", $dataItem->getMinute()) . ':' .
							sprintf( "%02d", $dataItem->getSecond() );
				}
				return array( $xsdvalue, $dataItem->getSortKey() );
			case '_boo':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_BOOLEAN ) break;
				return $dataItem->getBoolean() ? array( '1', 1 ) : array( '0', 0 );
			case '_rec':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_CONTAINER ) break;
				return array( false );
			case '__typ':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_WIKIPAGE ) break;
				return array( SMWDataValueFactory::findTypeID( str_replace( '_', ' ', $dataItem->getDBkey() ) ) );
			case '__con':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_CONCEPT ) break;
				return array( $dataItem->getConceptQuery(), $dataItem->getDocumentation(), $dataItem->getQueryFeatures(), $dataItem->getSize(), $dataItem->getDepth() );
			case '__err':
				return array( false );
			case '__pro':
				if ( $dataItem->getDIType() !== SMWDataItem::TYPE_PROPERTY ) break;
				return array( $dataItem->getKey() );
			case '_geo':
				$coordinateSet = $dataItem->getCoordinateSet();

				return array(
					$coordinateSet['lat'],
					$coordinateSet['lon']
				);
				break;
			default:
				$typeid = $dataItem->getTypeId();
				if ( ( $typeid != '' ) && ( $typeid{0} != '_' ) && 
				     ( $dataItem->getDIType() == SMWDataItem::TYPE_NUMBER ) ) { // linear conversion type
					return array( $dataItem->getSerialization(), floatval( $dataItem->getNumber() ) );
				}
		}
		return array( false );
	}

}
