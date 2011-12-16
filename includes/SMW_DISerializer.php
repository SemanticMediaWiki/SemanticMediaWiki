<?php

/**
 * Class for serializing SMWDataItem and SMWQueryResult objects to a context independent
 * object consisting of arrays and associative arrays, which can be fed
 * directly to json_encode, the MediaWiki API, and similar serializers.
 *
 * This class is distinct from SMWSerializer and the SMWExpData object
 * it takes, in that here semantic context is lost.
 *
 * @since 1.7
 *
 * @file SMW_DISerializer.php
 * @ingroup SMW
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

class SMWDISerializer {

	/**
	 * Get the serialization for the provided data item.
	 * 
	 * @since 1.7
	 * 
	 * @param SMWDataItem $dataItem
	 * 
	 * @return mixed
	 */
	public static function getSerialization( SMWDataItem $dataItem ) {
		switch ( $dataItem->getDIType() ) {
			case SMWDataItem::TYPE_WIKIPAGE:
				$title = $dataItem->getTitle();
				$result = array(
					'fulltext' => $title->getFullText(),
					'fullurl' => $title->getFullUrl(),
				);
				break;
			case SMWDataItem::TYPE_NUMBER:
				$result = $dataItem->getNumber();
				break;
			case SMWDataItem::TYPE_GEO:
				$result = $dataItem->getCoordinateSet();
				break;
			case SMWDataItem::TYPE_TIME:
				$result = $dataItem->getMwTimestamp();
				break;
			default:
				$result = $dataItem->getSerialization();
				break;
		}
		
		return $result;
	}
	
	/**
	 * Get the serialization for a SMWQueryResult object.
	 * 
	 * @since 1.7
	 * 
	 * @param SMWQueryResult $result
	 * 
	 * @return array
	 */
	public static function getSerializedQueryResult( SMWQueryResult $queryResult ) {
		$results = array();
		$printRequests = array();
		
		foreach ( $queryResult->getPrintRequests() as /* SMWPrintRequest */ $printRequest ) {
			$printRequests[] = array(
				'label' => $printRequest->getLabel(),
				'typeid' => $printRequest->getTypeID(),
				'mode' => $printRequest->getMode(),
			);
		}
		
		foreach ( $queryResult->getResults() as /* SMWDIWikiPage */ $diWikiPage ) {
			$result = array( 'printouts' => array() );
			
			foreach ( $queryResult->getPrintRequests() as /* SMWPrintRequest */ $printRequest ) {
				$resultAarray = new SMWResultArray( $diWikiPage, $printRequest, $queryResult->getStore() );
				
				if ( $printRequest->getMode() === SMWPrintRequest::PRINT_THIS ) {
					$dataItems = $resultAarray->getContent();
					$result += self::getSerialization( array_shift( $dataItems ) );
				}
				else {
					$result['printouts'][$printRequest->getLabel()] = array_map(
						array( __class__, 'getSerialization' ),
						$resultAarray->getContent()
					);
				}

			}
			
			$results[$diWikiPage->getTitle()->getFullText()] = $result;
		}
		
		return array( 'results' => $results, 'printrequests' => $printRequests );
	}
	
}
