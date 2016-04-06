<?php

namespace SMW\SQLStore;

use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;
use SMWRequestOptions as RequestOptions;
use SMWStringCondition as StringCondition;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class RequestOptionsProcessor {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 2.3
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * Transform input parameters into a suitable array of SQL options.
	 * The parameter $valuecol defines the string name of the column to which
	 * sorting requests etc. are to be applied.
	 *
	 * @since 1.8
	 *
	 * @param RequestOptions|null $requestOptions
	 * @param string $valueCol
	 *
	 * @return array
	 */
	public function transformToSQLOptions( RequestOptions $requestOptions = null, $valueCol = '' ) {
		$sqlConds = array();

		if ( $requestOptions === null ) {
			return $sqlConds;
		}

		if ( $requestOptions->limit > 0 ) {
			$sqlConds['LIMIT'] = $requestOptions->limit;
		}

		if ( $requestOptions->offset > 0 ) {
			$sqlConds['OFFSET'] = $requestOptions->offset;
		}

		if ( ( $valueCol !== '' ) && ( $requestOptions->sort ) ) {
			$sqlConds['ORDER BY'] = $requestOptions->ascending ? $valueCol : $valueCol . ' DESC';
		}

		return $sqlConds;
	}

	/**
	 * Transform input parameters into a suitable string of additional SQL
	 * conditions. The parameter $valuecol defines the string name of the
	 * column to which value restrictions etc. are to be applied.
	 *
	 * @since 1.8
	 *
	 * @param RequestOptions|null $requestOptions
	 * @param string $valueCol name of SQL column to which conditions apply
	 * @param string $labelCol name of SQL column to which string conditions apply, if any
	 * @param boolean $addAnd indicate whether the string should begin with " AND " if non-empty
	 *
	 * @return string
	 */
	public function transformToSQLConditions( RequestOptions $requestOptions = null, $valueCol = '', $labelCol = '', $addAnd = true ) {
		$sqlConds = '';

		if ( $requestOptions === null ) {
			return $sqlConds;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		// Apply value boundary
		if ( ( $valueCol !== '' ) && ( $requestOptions->boundary !== null ) ) {

			if ( $requestOptions->ascending ) {
				$op = $requestOptions->include_boundary ? ' >= ' : ' > ';
			} else {
				$op = $requestOptions->include_boundary ? ' <= ' : ' < ';
			}

			$sqlConds .= ( $addAnd ? ' AND ' : '' ) . $valueCol . $op . $connection->addQuotes( $requestOptions->boundary );
		}

		// Apply string conditions
		if ( $labelCol !== '' ) {
			foreach ( $requestOptions->getStringConditions() as $strcond ) {
				$string = str_replace( '_', '\_', $strcond->string );

				switch ( $strcond->condition ) {
					case StringCondition::STRCOND_PRE:  $string .= '%';
					break;
					case StringCondition::STRCOND_POST: $string = '%' . $string;
					break;
					case StringCondition::STRCOND_MID:  $string = '%' . $string . '%';
					break;
				}

				$conditionOperator = $strcond->asDisjunctiveCondition ? ' OR ' : ' AND ';

				$sqlConds .= ( ( $addAnd || ( $sqlConds !== '' ) ) ? $conditionOperator : '' ) . $labelCol . ' LIKE ' . $connection->addQuotes( $string );
			}
		}

		return $sqlConds;
	}

	/**
	 * Not in all cases can requestoptions be forwarded to the DB using
	 * getSQLConditions() and getSQLOptions(): some data comes from caches
	 * that do not respect the options yet. This method takes an array of
	 * results (SMWDataItem objects) *of the same type* and applies the
	 * given requestoptions as appropriate.
	 *
	 * @since 1.8
	 * @param array $data array of SMWDataItem objects
	 * @param SMWRequestOptions|null $requestoptions
	 *
	 * @return SMWDataItem[]
	 */
	public function applyRequestOptionsTo( array $data, RequestOptions $requestOptions = null ) {

		if ( $data === array() || $requestOptions === null ) {
			return $data;
		}

		$result = array();
		$sortres = array();

		$sampleDataItem = reset( $data );
		$isNumeric = is_numeric( $sampleDataItem->getSortKey() );

		$i = 0;

		foreach ( $data as $item ) {

			list( $label, $value ) = $this->getSortKeyForItem( $item );

			$keepDataValue = $this->applyBoundaryConditions( $requestOptions, $value, $isNumeric );
			$keepDataValue = $this->applyStringConditions( $requestOptions, $label, $keepDataValue );

			if ( $keepDataValue ) {
				$result[$i] = $item;
				$sortres[$i] = $value;
				$i++;
			}
		}

		$this->applySortRestriction( $requestOptions, $result, $sortres, $isNumeric );
		$this->applyLimitRestriction( $requestOptions, $result );

		return $result;
	}

	private function applyStringConditions( $requestOptions, $label, $keepDataValue ) {

		foreach ( $requestOptions->getStringConditions() as $strcond ) { // apply string conditions
			switch ( $strcond->condition ) {
				case StringCondition::STRCOND_PRE:
					$keepDataValue = $keepDataValue && ( strpos( $label, $strcond->string ) === 0 );
					break;
				case StringCondition::STRCOND_POST:
					$keepDataValue = $keepDataValue && ( strpos( strrev( $label ), strrev( $strcond->string ) ) === 0 );
					break;
				case StringCondition::STRCOND_MID:
					$keepDataValue = $keepDataValue && ( strpos( $label, $strcond->string ) !== false );
					break;
			}
		}

		return $keepDataValue;
	}

	private function applyBoundaryConditions( $requestOptions, $value, $isNumeric ) {
		$keepDataValue = true; // keep datavalue only if this remains true

		if ( $requestOptions->boundary === null ) {
			return $keepDataValue;
		}

		// apply value boundary
		$strc = $isNumeric ? 0 : strcmp( $value, $requestOptions->boundary );

		if ( $requestOptions->ascending ) {
			if ( $requestOptions->include_boundary ) {
				$keepDataValue = $isNumeric ? ( $value >= $requestOptions->boundary ) : ( $strc >= 0 );
			} else {
				$keepDataValue = $isNumeric ? ( $value > $requestOptions->boundary ) : ( $strc > 0 );
			}
		} else {
			if ( $requestOptions->include_boundary ) {
				$keepDataValue = $isNumeric ? ( $value <= $requestOptions->boundary ) : ( $strc <= 0 );
			} else {
				$keepDataValue = $isNumeric ? ( $value < $requestOptions->boundary ) : ( $strc < 0 );
			}
		}

		return $keepDataValue;
	}

	private function getSortKeyForItem( $item ) {

		if ( $item instanceof DIWikiPage ) {
			$label = $this->store->getWikiPageSortKey( $item );
			$value = $label;
		} else {
			$label = ( $item instanceof DIBlob ) ? $item->getString() : '';
			$value = $item->getSortKey();
		}

		return array( $label, $value );
	}

	private function applySortRestriction( $requestOptions, &$result, $sortres, $isNumeric ) {

		if ( !$requestOptions->sort ) {
			return null;
		}

		$flag = $isNumeric ? SORT_NUMERIC : SORT_LOCALE_STRING;

		if ( $requestOptions->ascending ) {
			asort( $sortres, $flag );
		} else {
			arsort( $sortres, $flag );
		}

		$newres = array();

		foreach ( $sortres as $key => $value ) {
			$newres[] = $result[$key];
		}

		$result = $newres;
	}

	private function applyLimitRestriction( $requestOptions, &$result ) {

		if ( $requestOptions->limit > 0 ) {
			return $result = array_slice( $result, $requestOptions->offset, $requestOptions->limit );
		}

		$result = array_slice( $result, $requestOptions->offset );
	}

}
