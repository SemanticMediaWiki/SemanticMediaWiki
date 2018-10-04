<?php

namespace SMW\Elastic\QueryEngine;

use Psr\Log\LoggerAwareTrait;
use SMW\DataTypeRegistry;
use SMW\DIProperty;
use SMW\Store;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SortBuilder {

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @var string
	 */
	private $scoreField;

	/**
	 * @var boolean
	 */
	private $isScoreSort = false;

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
	 * @param string $scoreField
	 */
	public function setScoreField( $scoreField ) {
		$this->scoreField = $scoreField;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isScoreSort() {
		return $this->isScoreSort;
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return array
	 */
	public function makeSortField( Query $query ) {

		// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html#_memory_considerations
		// "... the relevant sorted field values are loaded into memory. This means
		// that per shard, there should be enough memory ... string based types,
		// the field sorted on should not be analyzed / tokenized ... numeric
		// types it is recommended to explicitly set the type to narrower types"

		$this->isScoreSort = $query->getOption( Query::SCORE_SORT );

		if ( $query->getOption( Query::SCORE_SORT ) ) {
			return [ [ '_score' => [ 'order' => $query->getOption( Query::SCORE_SORT ) ] ], [], false, false];
		}

		return $this->getFields( $query->getSortKeys() );
	}

	private function getFields( array $sortKeys ) {

		$isRandom = false;
		$isConstantScore = true;
		$sort = [];
		$sortFields = [];
		$sortKeysCount = count( $sortKeys );

		foreach ( $sortKeys as $key => $order ) {
			$order = strtolower( $order );
			$isRandom = strpos( $order, 'rand' ) !== false;

			if ( strtolower( $key ) === $this->scoreField ) {
				$key = '_score';
				$this->isScoreSort = true;
				$isConstantScore = false;
			}

			if ( $key === '' || $key === '#' ) {
				$this->addDefaultField( $sort, $order, $sortKeysCount );
			} else {
				$this->addField( $sort, $sortFields, $key, $order );
			}
		}

		return [ $sort, $sortFields, $isRandom, $isConstantScore ];
	}

	private function addDefaultField( &$sort, $order, $sortKeysCount ) {
		$sort['subject.sortkey.sort'] = [ 'order' => $order ];

		// Add title as extra criteria in case an entity uses the same sortkey
		// to clarify its relative position, @see T:P0416#8
		// Only add the title as determining factor when no other sort parameter
		// is available
		if ( $sortKeysCount == 1 ) {
			$sort['subject.title.sort'] = [ 'order' => $order ];
		}
	}

	private function addField( &$sort, &$sortFields, $key, $order ) {

		$dataTypeRegistry = DataTypeRegistry::getInstance();
		$chain = false;

		// Chain?
		if ( strpos( $key, '.' ) !== false ) {
			$list = explode( '.', $key );
			$last = current( $list );
		} else {
			$list = [ $key ];
		}

		foreach ( $list as $key ) {

			if ( $key === '_score' ) {
				$field = '_score';
			} else {
				$property = DIProperty::newFromUserLabel( $key );

				$field = $this->fieldMapper->getField( $property, 'Field' );

				$pid = $this->fieldMapper->getPID(
					$this->store->getObjectIds()->getSMWPropertyID( $property )
				);

				// Only record the last key to be used as possible existential
				// enforcement
				if ( $chain === false ) {
					$sortFields[] = "$pid.$field";
				}

				// Use special sort field on mapped fields which is not analyzed
				if ( $this->sort_field( $field ) ) {
					$field = "$field.sort";
				}

				$field = "$pid.$field";
			}

			if ( !isset( $sort[$field] ) ) {
				$sort[$field] = [ 'order' => $order ];
			}

			$chain = true;
		}
	}

	private function sort_field( $field ) {
		return strpos( $field, 'txt' ) !== false || strpos( $field, 'wpgField' ) !== false || strpos( $field, 'uriField' ) !== false;
	}

}
