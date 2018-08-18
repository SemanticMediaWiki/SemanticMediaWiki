<?php

namespace SMW\Elastic\Lookup;

use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMWDITime as DITime;
use SMWDataItem as DataItem;
use SMW\DIProperty;
use SMW\Store;
use SMW\RequestOptions;
use RuntimeException;

/**
 * Experimental implementation to showcase how a Elasticsearch specific implementation
 * for a property value lookup can be used and override the default SQL service.
 *
 * The class is targeted to be used for API (e.g. autocomplete etc.) intensive
 * services.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProximityPropertyValueLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
		$this->fieldMapper = new FieldMapper();
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 * @param string $value
	 * @param RequestOptions $opts
	 *
	 * @return array
	 */
	public function lookup( DIProperty $property, $value = '', RequestOptions $opts ) {

		$connection = $this->store->getConnection( 'elastic' );
		$continueOffset = 0;

		$pid = $this->fieldMapper->getPID(
			$this->store->getObjectIds()->getSMWPropertyID( $property )
		);

		$diType = DataTypeRegistry::getInstance()->getDataItemByType(
			$property->findPropertyTypeID()
		);

		$field = $this->fieldMapper->getField( $property );

		if ( $value === '' ) {
			// Just create a list of available values where the property exists
			$params = $this->fieldMapper->exists( "$pid.$field" );

			// Increase the range of the initial match since a property field
			// stores are all sorts of values, this is to make sure that the
			// aggregation has enough objects available to build a selection
			// list that satisfies the RequestOptions::getLimit
			$limit = 500;
		} elseif( $diType === DataItem::TYPE_TIME ) {
			$limit = 500;

			$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
				$property,
				$value
			);

			$params = $this->fieldMapper->bool(
				'must',
				$this->fieldMapper->range( "$pid.$field", $dataValue->getDataItem()->getJD(), SMW_CMP_GEQ )
			);
		} elseif( $diType === DataItem::TYPE_NUMBER ) {
			$limit = 500;

			if ( strpos( $value, '*' ) === false ) {
				$value = "*$value*";
			}

			$params = $this->fieldMapper->bool(
				'must',
				$this->fieldMapper->wildcard( "$pid.$field.keyword", $value )
			);
		} else {
			$limit = 500;

			if ( strpos( $value, '*' ) === false ) {
				$value = "$value*";
			}

			$params = $this->fieldMapper->bool(
				'must',
				$this->fieldMapper->match_phrase( "$pid.$field", $value )
			);
		}

		$body = [
			'_source' => [ "$pid.$field" ],
			'from'    => $opts->getOffset(),
			'size'    => $limit,
			'query'   => $params
		];

		$limit = $opts->getLimit() + 1;

		// Aggregation is used to filter a specific value aspect from a property
		// field contents
		if ( $value !== '' ) {
			// Setting size to 0 which avoids executing the fetch query of the search
			// hereby making the request more efficient.
			$body['size'] = 0;

			$body += $this->aggs_filter( $diType, $pid, $field, $limit, $property, trim( $value, '*' ) );
		}

		if ( $opts->sort ) {
			$body += [ 'sort' => [ "$pid.$field" => [ 'order' => $opts->sort ] ] ];
		}

		$params = [
			'index' => $connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'body'  => $body
		];

		list( $res, $errors ) = $connection->search( $params );

		if ( isset( $res['aggregations'] ) ) {
			list( $list, $i ) = $this->match_aggregations( $res['aggregations'], $diType, $limit );
		} elseif ( isset( $res['hits'] ) ) {
			list( $list, $i ) = $this->match_hits( $res['hits'], $pid, $field, $limit );
		} else {
			$list = [];
			$i = 0;
		}

		if ( $list !== [] ) {
			$list = array_values( $list );

			if (  $diType === DataItem::TYPE_TIME ) {
				foreach ( $list as $key => $value ) {

					if ( strpos( $value, '/' ) !== false ) {
						$dataItem = DITime::doUnserialize( $value );
					} else {
						$dataItem = DITime::newFromJD( $value );
					}

					$list[$key] = DataValueFactory::getInstance()->newDataValueByItem( $dataItem, $property )->getWikiValue();
				}
			}
		}

		return $list;
	}

	private function aggs_filter( $diType, $pid, $field, $limit, $property, $value ) {

		// A field on ES to a property can can all different kind of values and
		// the request is only interested in those values that match a certain
		// prefix or affix hence use `include` to only return aggregated values
		// that contain the search term or value

		if ( $diType === DataItem::TYPE_TIME ) {

			$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
				$property,
				$value
			);

			return [
				'aggs' => [
					'value_terms' => [
						'terms' => [
							'field' => "$pid.dat_raw",
							'size'  => $limit,
							"order" => [ "_key" => "asc" ],
							'include' => $dataValue->getDataItem()->getSerialization() . ".*"
						]
					]
				]
			];
		}

		if ( $diType === DataItem::TYPE_NUMBER ) {
			return [
				'aggs' => [
					'value_terms' => [
						'terms' => [
							'field' => "$pid.$field.keyword",
							'size'  => $limit,
							"order" => [ "_key" => "asc" ],
							'include' => ".*" . $value . ".*"
						]
					]
				]
			];
		}

		return [
			'aggs' => [
				'value_terms' => [
					'terms' => [
						'field' => "$pid.$field.keyword",
						'size' => $limit,
						'include' =>
							".*" . $value . ".*|" .
							".*" . ucfirst( $value ) . ".*|" .
							".*" . mb_strtoupper( $value ) . ".*"
					]
				]
			]
		];
	}

	private function match_aggregations( $res, $diType, $limit ) {

		$isNumeric = $diType === DataItem::TYPE_NUMBER;
		$list = [];
		$i = 0;

		foreach ( $res as $aggs ) {
			foreach ( $aggs as $val ) {

				if ( !is_array( $val ) ) {
					continue;
				}

				foreach ( $val as $v ) {

					if ( $i >= $limit ) {
						break;
					}

					if ( isset( $v['key'] ) ) {
						$val = (string)$v['key'];

						// Aggregation happens on keyword field, numerics are of type
						// double hence is coerced as 5 -> 5.0
						if ( $isNumeric && substr( $val, -2 ) === '.0' ) {
							$val = substr( $val, 0, -2 );
						}

						$list[] = $val;
						$i++;
					}
				}
			}
		}

		return [ $list, $i ];
	}

	private function match_hits( $res, $pid, $field, $limit ) {

		$list = [];
		$i = 0;

		foreach ( $res as $key => $value ) {

			if ( $key !== 'hits' ) {
				continue;
			}

			foreach ( $value as $v ) {

				if ( !isset( $v['_source'][$pid][$field] ) ) {
					continue;
				}

				foreach ( $v['_source'][$pid][$field] as $match ) {

					if ( $i >= $limit ) {
						break;
					}

					// Filter duplicates
					$hash = md5( $match );

					if ( isset( $list[$hash] ) ) {
						continue;
					}

					$list[$hash] = (string)$match;
					$i++;
				}
			}
		}

		return [ $list, $i ];
	}

}
