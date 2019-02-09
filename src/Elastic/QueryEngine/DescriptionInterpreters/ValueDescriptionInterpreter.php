<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\DIWikiPage;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\Language\ValueDescription;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDInumber as DINumber;
use SMWDITime as DITime;
use SMW\Utils\CharExaminer;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ValueDescriptionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
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
		$this->fieldMapper = $this->conditionBuilder->getFieldMapper();

		$params = [];
		$pid = false;
		$filter = false;

		if ( $property === null ) {
			$field = "subject.sortkey";
		} else {
			$pid = 'P:' . $this->conditionBuilder->getID( $property );

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

		$hierarchy = $this->conditionBuilder->findHierarchyMembers(
			$property,
			$hierarchyDepth
		);

		if ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_EQ && $property === null ) {
			// We want an exact match!
			$field = '_id';
			$value = $this->conditionBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_NEQ && $property === null ) {
			// We want an exact match!
			$field = '_id';
			$value = $this->conditionBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_EQ ) {
			$field = "$pid.wpgID";
			$value = $this->conditionBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_NEQ ) {
			$field = "$pid.wpgID";
			$value = $this->conditionBuilder->getID( $dataItem );
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

		if ( mb_strlen( $value ) > $this->conditionBuilder->getOption( 'maximum.value.length' ) ) {
			$value = mb_substr( $value, 0, $this->conditionBuilder->getOption( 'maximum.value.length' ) );
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

		$condition = $this->conditionBuilder->newCondition( $params );

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

		// Wide proximity uses ~~ as identifier as in [[~~ ... ]] or
		// [[in:fox jumps]]
		if ( $value{0} === '~' ) {
			$isWide = true;

			// Remove the ~ to avoid a `QueryShardException[Failed to parse query ...`
			$value = substr( $value, 1 );

			if ( !$hasWildcard && $this->conditionBuilder->getOption( 'wide.proximity.as.match_phrase', true ) ) {
				$value = trim( $value, '"' );
				$value = "\"$value\"";
			}

			$field = $this->conditionBuilder->getOption( 'wide.proximity.fields', [ 'text_copy' ] );
		}

		// Wide or simple proximity? + wildcard?
		// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html#operator-min
		if ( $hasWildcard && $isWide && !$isPhrase ) {

			// cjk.best.effort.proximity.match
			if ( $this->isCJK( $value ) ) {
				// Increase match accuracy by relying on a `phrase` to define char
				// boundaries
				$params = $this->fieldMapper->match( $field, "\"$value\"" );
			} else {
				$params = $this->fieldMapper->query_string( $field, $value, [ 'minimum_should_match' => 1 ] );
			}

		} elseif ( $hasWildcard && !$isWide && !$isPhrase ) {
			// [[~Foo/Bar/*]] (simple proximity) is only used on subject.sortkey
			// which is why we want to use a `not_analyzed` field to exactly
			// match the content before the *.
			// `lowercase` uses a normalizer to achieve case insensitivity
			if ( $this->conditionBuilder->getOption( 'page.field.case.insensitive.proximity.match', true ) ) {
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

	/**
	 * Fields that use a standard analyzer will split CJK terms into single chars
	 * and any enclosing like *...* makes a term not applicable to the same
	 * treatment which prevents a split and hereby causing the search match to be
	 * worse off hence remove `*` in case of CJK usage.
	 */
	private function isCJK( &$text ) {

		// Only use the examiner on the standard index_def since ICU provides
		// better CJK and may handle `*` more sufficiently
		if ( !$this->conditionBuilder->getOption( 'cjk.best.effort.proximity.match', false ) ) {
			return false;
		}

		if ( !CharExaminer::isCJK( $text ) ) {
			return false;
		}

		if ( $text{0} === '*' ) {
			$text = mb_substr( $text, 1 );
		}

		if ( mb_substr( $text , -1 ) === '*' ) {
			$text = mb_substr( $text, 0, -1 );
		}

		return true;
	}

}
