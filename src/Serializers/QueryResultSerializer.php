<?php

namespace SMW\Serializers;

use OutOfBoundsException;
use Serializers\DispatchableSerializer;
use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMWDataItem as DataItem;
use SMWQueryResult as QueryResult;
use SMWResultArray;
use Title;

/**
 * Class for serializing SMWDataItem and SMWQueryResult objects to a context
 * independent object consisting of arrays and associative arrays, which can
 * be fed directly to json_encode, the MediaWiki API, and similar serializers.
 *
 * This class is distinct from SMWSerializer and the SMWExpData object
 * it takes, in that here semantic context is lost.
 *
 * @ingroup Serializers
 *
 * @licence GNU GPL v2+
 * @since 1.7
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class QueryResultSerializer implements DispatchableSerializer {

	/**
	 * @see SerializerInterface::serialize
	 *
	 * @since 1.9
	 *
	 * @return array
	 * @throws OutOfBoundsException
	 */
	public function serialize( $queryResult ) {

		if ( !( $this->isSerializerFor( $queryResult ) ) ) {
			throw new OutOfBoundsException( 'Object was not identified as a QueryResult instance' );
		}

		return $this->getSerializedQueryResult( $queryResult ) + array( 'serializer' => __CLASS__, 'version' => 0.11 );
	}

	/**
	 * @see Serializers::isSerializerFor
	 *
	 * @since  1.9
	 */
	public function isSerializerFor( $queryResult ) {
		return $queryResult instanceof QueryResult;
	}

	/**
	 * Get the serialization for the provided data item.
	 *
	 * @since 1.7
	 *
	 * @param SMWDataItem $dataItem
	 *
	 * @return mixed
	 */
	public static function getSerialization( DataItem $dataItem, $printRequest = null ) {
		switch ( $dataItem->getDIType() ) {
			case DataItem::TYPE_WIKIPAGE:

				// Support for a deserializable _rec type with 0.6
				if ( $printRequest !== null && strpos( $printRequest->getTypeID(), '_rec' ) !== false ) {
					$recordValue = DataValueFactory::getInstance()->newDataValueByItem(
						$dataItem,
						$printRequest->getData()->getDataItem()
					);

					$recordDiValues = array();

					foreach ( $recordValue->getPropertyDataItems() as $property ) {
						$label = $property->getLabel();

						$recordDiValues[$label] = array(
							'label'  => $label,
							'key'    => $property->getKey(),
							'typeid' => $property->findPropertyTypeID(),
							'item'   => array()
						);

						foreach ( $recordValue->getDataItem()->getSemanticData()->getPropertyValues( $property ) as $value ) {
							$recordDiValues[$label]['item'][] = self::getSerialization( $value );
						}
					}
					$result = $recordDiValues;
				} else {
					$title = $dataItem->getTitle();

					$wikiPageValue = DataValueFactory::getInstance()->newDataValueByItem(
						$dataItem
					);

					$result = array(
						'fulltext' => $title->getFullText(),
						'fullurl' => $title->getFullUrl(),
						'namespace' => $title->getNamespace(),
						'exists' => strval( $title->isKnown() ),
						'displaytitle' => $wikiPageValue->getDisplayTitle()
					);
				}
				break;
			case DataItem::TYPE_NUMBER:
				// dataitems and datavalues
				// Quantity is a datavalue type that belongs to dataitem
				// type number which means in order to identify the correct
				// unit, we have re-factor the corresponding datavalue otherwise
				// we will not be able to determine the unit
				// (unit is part of the datavalue object)
				if ( $printRequest !== null && $printRequest->getTypeID() === '_qty' ) {
					$diProperty = $printRequest->getData()->getDataItem();
					$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $dataItem, $diProperty );

					$result = array(
						'value' => $dataValue->getNumber(),
						'unit' => $dataValue->getUnit()
					);
				} else {
					$result = $dataItem->getNumber();
				}
				break;
			case DataItem::TYPE_GEO:
				$result = $dataItem->getCoordinateSet();
				break;
			case DataItem::TYPE_TIME:
				$result = array(
					'timestamp' => $dataItem->getMwTimestamp(),
					'raw' => $dataItem->getSerialization()
				);
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
	public static function getSerializedQueryResult( QueryResult $queryResult ) {
		$results = array();
		$printRequests = array();

		foreach ( $queryResult->getPrintRequests() as $printRequest ) {
			$printRequests[] = self::getSerializedPrintRequestFormat( $printRequest );
		}

		/**
		 * @var DIWikiPage $diWikiPage
		 * @var PrintRequest $printRequest
		 */
		foreach ( $queryResult->getResults() as $diWikiPage ) {

			if ( !($diWikiPage->getTitle() instanceof Title ) ) {
				continue;
			}

			$result = array( 'printouts' => array() );

			foreach ( $queryResult->getPrintRequests() as $printRequest ) {
				$resultArray = new SMWResultArray( $diWikiPage, $printRequest, $queryResult->getStore() );

				if ( $printRequest->getMode() === PrintRequest::PRINT_THIS ) {
					$dataItems = $resultArray->getContent();
					$result += self::getSerialization( array_shift( $dataItems ), $printRequest );
				} elseif ( $resultArray->getContent() !== array() ) {
					$values = array();

					foreach ( $resultArray->getContent() as $dataItem ) {
						$values[] = self::getSerialization( $dataItem, $printRequest );
					}
					$result['printouts'][$printRequest->getLabel()] = $values;
				} else {
					// For those objects that are empty return an empty array
					// to keep the output consistent
					$result['printouts'][$printRequest->getLabel()] = array();
				}
			}

			$results[$diWikiPage->getTitle()->getFullText()] = $result;

		}

		return array( 'printrequests' => $printRequests, 'results' => $results);
	}

	private static function getSerializedPrintRequestFormat( $printRequest ) {

		$serialized = array(
			'label'  => $printRequest->getLabel(),
			'key'    => '',
			'redi'   => '',
			'typeid' => $printRequest->getTypeID(),
			'mode'   => $printRequest->getMode(),
			'format' => $printRequest->getOutputFormat()
		);

		if ( $printRequest->getMode() !== PrintRequest::PRINT_PROP ) {
			return $serialized;
		}

		$data = $printRequest->getData();

		if ( $data === null ) {
			return $serialized;
		}

		// To match forwarded redirects
		$serialized['redi'] = !$data->getInceptiveProperty()->equals( $data->getDataItem() ) ? $data->getInceptiveProperty()->getKey() : '';

		// To match internal properties like _MDAT
		$serialized['key'] = $data->getDataItem()->getKey();

		return $serialized;
	}

}
