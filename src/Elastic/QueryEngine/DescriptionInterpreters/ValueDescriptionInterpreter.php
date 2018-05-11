<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Description;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Options;
use SMWDIGeoCoord as DIGeoCoord;
use SMWDITime as DITime;
use SMWDIBoolean as DIBoolean;
use SMWDInumber as DINumber;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ValueDescriptionInterpreter {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @since 3.0
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param ValueDescription $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( ValueDescription $description, $isConjunction = false ) {

		$dataItem = $description->getDataItem();
		$comparator = $description->getComparator();

		$property = $description->getProperty();
		$this->fieldMapper = $this->queryBuilder->getFieldMapper();

		$params = [];
		$pid = false;
		$filter = false;

		if ( $property === null ) {
			$field = "subject.sortkey";
		} else {
			$pid = 'P:' . $this->queryBuilder->getID( $property );

			if ( $property->isInverse() ) {
				// Want to know if this case happens and if so we need to handle
				// it somewhow ...
				throw new RuntimeException( "ValueDescription with an inverted property! PID: $pid, " . $description->getQueryString() );
			} else {
				$field = $this->fieldMapper->getField( $property, 'Field' );
			}

			$field = "$pid.$field";
		}

		//$description->getHierarchyDepth(); ??
		$hierarchyDepth = null;

		$hierarchy = $this->queryBuilder->findHierarchyMembers(
			$property,
			$hierarchyDepth
		);

		if ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_EQ && $property === null ) {
			// We want an exact match!
			$field = '_id';
			$value = $this->queryBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_NEQ && $property === null ) {
			// We want an exact match!
			$field = '_id';
			$value = $this->queryBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_EQ ) {
			$field = "$pid.wpgID";
			$value = $this->queryBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_NEQ ) {
			$field = "$pid.wpgID";
			$value = $this->queryBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage ) {
			$value = $dataItem->getSortKey();
		} elseif ( $dataItem instanceof DITime ) {
			$field = "$field.keyword";
			$value = $dataItem->getJD();
		} elseif ( $dataItem instanceof DIBoolean ) {
			$value = $dataItem->getBoolean();
		} elseif ( $dataItem instanceof DINumber ) {
			$value = $dataItem->getNumber();
		} else {
			$value = $dataItem->getSerialization();
		}

		if ( $dataItem instanceof DIWikiPage && $this->isRange( $comparator ) ) {
			$params = $this->fieldMapper->range( "$field.keyword", $value, $comparator );
		} elseif ( $dataItem instanceof DIBlob && $comparator === SMW_CMP_EQ ) {
			$params = $this->fieldMapper->match( "$field", "\"$value\"" );
		} elseif ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {
			$params = $this->fieldMapper->terms( "$field", $value );
		} elseif ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) {
			$params = $this->proximity_bool( $field, $value );
		} elseif ( $this->isRange( $comparator ) ) {
			$params = $this->fieldMapper->range( $field, $value, $comparator );
		} else {
			$params = $this->fieldMapper->match( $field, $value );
		}

		if ( $params !== [] && $pid ) {
			$params = $this->fieldMapper->hierarchy( $params, $pid, $hierarchy );
		}

		$condition = $this->queryBuilder->newCondition( $params );

		if ( $this->isNot( $comparator ) && $isConjunction ) {
			$condition->type( 'must_not' );
		}

		if ( !$isConjunction ) {
			$condition->type( ( $this->isNot( $comparator ) ? 'must_not' : ( $filter ? 'filter' : 'must' ) ) );
		}

		$condition->log( [ 'ValueDescription' => $description->getQueryString() ] );

		return $condition;
	}

	private function isRange( $comparator ) {
		return $comparator === SMW_CMP_GRTR || $comparator === SMW_CMP_GEQ || $comparator === SMW_CMP_LESS || $comparator === SMW_CMP_LEQ;
	}

	private function isNot( $comparator ) {
		return $comparator === SMW_CMP_NLKE || $comparator === SMW_CMP_NEQ;
	}

	private function proximity_bool( $field, $value ) {

		$params = [];
		$hasWildcard = strpos( $value, '*' ) !== false;

		// Q1203
		// [[phrase:fox jump*]] (aka ~"fox jump*") + wildcard; use match with
		// a `multi_match` and type `phrase_prefix`
		$isPhrase = strpos( $value, '"' ) !== false;
		$isWide = false;

		//
		// Wide promimity uses ~~ as identifer as in [[~~ ... ]] or
		// [[in:fox jumps]]
		if ( $value{0} === '~' ) {
			$isWide = true;

			// Remove the ~ to avoid a `QueryShardException[Failed to parse query ...`
			$value = substr( $value, 1 );

			if ( !$hasWildcard && $this->queryBuilder->getOption( 'wide.proximity.as.match_phrase', true ) ) {
				$value = trim( $value, '"' );
				$value = "\"$value\"";
			}

			$field = $this->queryBuilder->getOption( 'wide.proximity.fields', [ 'text_copy' ] );
		}

		// Wide or simple promximity? + wildcard?
		// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html#operator-min
		if ( $hasWildcard && $isWide && !$isPhrase ) {
			$params = $this->fieldMapper->query_string( $field, $value, [ 'minimum_should_match' => 1 ] );
		} elseif ( $hasWildcard && !$isWide && !$isPhrase ) {
			// [[~Foo/Bar/*]] (simple proximity) is only used on subject.sortkey
			// which is why we want to use a `not_analyzed` field to exactly
			// match the content before the *.
			// `lowercase` uses a normalizer to achieve case insensitivity
			if ( $this->queryBuilder->getOption( 'page.field.case.insensitive.proximity.match', true ) ) {
				$field = "$field.lowercase";
			} else {
				$field = "$field.keyword";
			}

			$params = $this->fieldMapper->wildcard( $field, $value );
			$filter = true;
		} else {
			$params = $this->fieldMapper->match( $field, $value );
		}

		return $params;
	}

}
