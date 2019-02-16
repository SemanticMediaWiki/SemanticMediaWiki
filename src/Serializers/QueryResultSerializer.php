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
	 * @var integer
	 */
	private static $version = 2;

	/**
	 * @since 3.0
	 *
	 * @param integer $version
	 */
	public function version( $version ) {
		self::$version = (int)$version;
	}

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

		return $this->getSerializedQueryResult( $queryResult ) + [ 'serializer' => __CLASS__, 'version' => self::$version ];
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

					$recordDiValues = [];

					foreach ( $recordValue->getPropertyDataItems() as $property ) {
						$label = $property->getLabel();

						$recordDiValues[$label] = [
							'label'  => $label,
							'key'    => $property->getKey(),
							'typeid' => $property->findPropertyTypeID(),
							'item'   => []
						];

						foreach ( $recordValue->getDataItem()->getSemanticData()->getPropertyValues( $property ) as $value ) {

							if ( $property->findPropertyTypeID() === '_qty' ) {
								$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $value, $property );

								$recordDiValues[$label]['item'][] = [
									'value' => $dataValue->getNumber(),
									'unit' => $dataValue->getUnit()
								];
							} else {
								$recordDiValues[$label]['item'][] = self::getSerialization( $value );
							}
						}
					}
					$result = $recordDiValues;
				} else {
					$title = $dataItem->getTitle();

					$wikiPageValue = DataValueFactory::getInstance()->newDataValueByItem(
						$dataItem
					);

					$result = [
						'fulltext' => $title->getFullText(),
						'fullurl' => $title->getFullUrl(),
						'namespace' => $title->getNamespace(),
						'exists' => strval( $title->isKnown() ),
						'displaytitle' => $wikiPageValue->getDisplayTitle()
					];
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

					if ( $printRequest->isMode( \SMW\Query\PrintRequest::PRINT_CHAIN ) ) {
						$diProperty = $printRequest->getData()->getLastPropertyChainValue()->getDataItem();
					}

					$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $dataItem, $diProperty );

					$result = [
						'value' => $dataValue->getNumber(),
						'unit' => $dataValue->getUnit()
					];
				} else {
					$result = $dataItem->getNumber();
				}
				break;
			case DataItem::TYPE_GEO:
				$result = $dataItem->getCoordinateSet();
				break;
			case DataItem::TYPE_TIME:
				$result = [
					'timestamp' => $dataItem->getMwTimestamp(),
					'raw' => $dataItem->getSerialization()
				];
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
		$results = [];
		$printRequests = [];

		foreach ( $queryResult->getPrintRequests() as $printRequest ) {
			$printRequests[] = self::serialize_printrequest( $printRequest );
		}

		/**
		 * @var DIWikiPage $diWikiPage
		 * @var PrintRequest $printRequest
		 */
		foreach ( $queryResult->getResults() as $diWikiPage ) {

			if ( $diWikiPage === null || !($diWikiPage->getTitle() instanceof Title ) ) {
				continue;
			}

			$result = [ 'printouts' => [] ];

			foreach ( $queryResult->getPrintRequests() as $printRequest ) {
				$resultArray = SMWResultArray::factory( $diWikiPage, $printRequest, $queryResult );

				if ( $printRequest->getMode() === PrintRequest::PRINT_THIS ) {
					$dataItems = $resultArray->getContent();
					$result += self::getSerialization( array_shift( $dataItems ), $printRequest );
				} elseif ( $resultArray->getContent() !== [] ) {
					$values = [];

					foreach ( $resultArray->getContent() as $dataItem ) {
						$values[] = self::getSerialization( $dataItem, $printRequest );
					}
					$result['printouts'][$printRequest->getLabel()] = $values;
				} else {
					// For those objects that are empty return an empty array
					// to keep the output consistent
					$result['printouts'][$printRequest->getLabel()] = [];
				}
			}

			$id = $diWikiPage->getTitle()->getFullText();

			/**
			 * #3038
			 *
			 * Version 2: ... "results": { "Foo": {} ... }
			 * Version 3: ... "results": [ { "Foo": {} } ... ]
			 */
			if ( self::$version >= 3 ) {
				$results[] = [ $id => $result ];
			} else{
				$results[$id] = $result;
			}
		}

		$serialization = [
			'printrequests' => $printRequests,
			'results' => $results,

			// If we wanted to be able to deserialize a serialized QueryResult,
			// we would need to following information as well.
			// 'ask' => $queryResult->getQuery()->toArray()
		];

		return $serialization;
	}

	private static function serialize_printrequest( $printRequest ) {

		$serialized = [
			'label'  => $printRequest->getLabel(),
			'key'    => '',
			'redi'   => '',
			'typeid' => $printRequest->getTypeID(),
			'mode'   => $printRequest->getMode(),
			'format' => $printRequest->getOutputFormat()
		];

		$data = $printRequest->getData();

		if ( $printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
			$serialized['chain'] = $data->getDataItem()->getString();
			$serialized['key'] = $data->getLastPropertyChainValue()->getDataItem()->getKey();
		}

		if ( !$printRequest->isMode( PrintRequest::PRINT_PROP ) ) {
			return $serialized;
		}

		if ( $data === null ) {
			return $serialized;
		}

		$serialized['redi'] = '';

		// To match forwarded redirects
		if ( !$data->getInceptiveProperty()->equals( $data->getDataItem() ) ) {
			$serialized['redi'] = $data->getInceptiveProperty()->getKey();
		}

		// To match internal properties like _MDAT
		$serialized['key'] = $data->getDataItem()->getKey();

		return $serialized;
	}

}
