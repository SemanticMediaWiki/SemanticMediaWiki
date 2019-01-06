<?php

namespace SMW\SQLStore;

use SMW\DIWikiPage;
use SMW\Store;
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
class RequestOptionsProc {

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
	public static function getSQLOptions( RequestOptions $requestOptions = null, $valueCol = '' ) {
		$sqlConds = [];

		if ( $requestOptions === null ) {
			return $sqlConds;
		}

		if ( $requestOptions->getLimit() > 0 ) {
			$sqlConds['LIMIT'] = $requestOptions->getLimit();
		}

		if ( $requestOptions->getOffset() > 0 ) {
			$sqlConds['OFFSET'] = $requestOptions->getOffset();
		}

		if ( ( $valueCol !== '' ) && ( $requestOptions->sort ) ) {
			$sqlConds['ORDER BY'] = $requestOptions->ascending ? $valueCol : $valueCol . ' DESC';
		}

		if ( $requestOptions->getOption( 'GROUP BY' ) ) {
			$sqlConds['GROUP BY'] = $requestOptions->getOption( 'GROUP BY' );
		}

		if ( $requestOptions->getOption( 'DISTINCT' ) ) {
			$sqlConds['DISTINCT'] = $requestOptions->getOption( 'DISTINCT' );
		}

		// Avoid a possible filesort (likely caused by ORDER BY) when limit is
		// less than 2
		if ( $requestOptions->limit < 2 || $requestOptions->getOption( 'ORDER BY' ) === false ) {
			unset( $sqlConds['ORDER BY'] );
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
	 * @param Store $store
	 * @param RequestOptions|null $requestOptions
	 * @param string $valueCol name of SQL column to which conditions apply
	 * @param string $labelCol name of SQL column to which string conditions apply, if any
	 * @param boolean $addAnd indicate whether the string should begin with " AND " if non-empty
	 *
	 * @return string
	 */
	public static function getSQLConditions( Store $store, RequestOptions $requestOptions = null, $valueCol = '', $labelCol = '', $addAnd = true ) {
		$sqlConds = '';

		if ( $requestOptions === null ) {
			return $sqlConds;
		}

		$connection = $store->getConnection( 'mw.db' );

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
				$condition = 'LIKE';

				switch ( $strcond->condition ) {
					case StringCondition::COND_PRE:
						$string .= '%';
					break;
					case StringCondition::COND_POST:
						$string = '%' . $string;
					break;
					case StringCondition::COND_MID:
						$string = '%' . $string . '%';
					break;
					case StringCondition::COND_EQ:
						$string = $strcond->string;
						$condition = '=';
					break;
				}

				$conditionOperator = $strcond->isOr ? ' OR ' : ' AND ';

				if ( $strcond->isNot ) {
					$sqlConds = " ($sqlConds) AND ($labelCol NOT $condition ". $connection->addQuotes( $string ) . ") ";
				} else {
					$sqlConds .= ( ( $addAnd || ( $sqlConds !== '' ) ) ? $conditionOperator : '' ) . "$labelCol $condition " . $connection->addQuotes( $string );
				}
			}
		}

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {

			$expr = $addAnd ? 'AND' : '';

			if ( is_array( $extraCondition ) ) {
				foreach ( $extraCondition as $k => $v ) {
					$expr = $k;
					$extraCondition = $v;
				}
			}

			$sqlConds .= ( ( $addAnd || ( $sqlConds !== '' ) ) ? " $expr " : '' ) . $extraCondition;
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
	 *
	 * @param Store $store
	 * @param array $data array of SMWDataItem objects
	 * @param SMWRequestOptions|null $requestoptions
	 *
	 * @return SMWDataItem[]
	 */
	public static function applyRequestOptions( Store $store, array $data, RequestOptions $requestOptions = null ) {

		if ( $data === [] || $requestOptions === null ) {
			return $data;
		}

		$result = [];
		$sortres = [];

		$sampleDataItem = reset( $data );
		$isNumeric = is_numeric( $sampleDataItem->getSortKey() );

		$i = 0;

		foreach ( $data as $item ) {

			list( $label, $value ) = self::getSortKeyForItem( $store, $item );

			$keepDataValue = self::applyBoundaryConditions( $requestOptions, $value, $isNumeric );
			$keepDataValue = self::applyStringConditions( $requestOptions, $label, $keepDataValue );

			if ( $keepDataValue ) {
				$result[$i] = $item;
				$sortres[$i] = $value;
				$i++;
			}
		}

		self::applySortRestriction( $requestOptions, $result, $sortres, $isNumeric );
		self::applyLimitRestriction( $requestOptions, $result );

		return $result;
	}

	private static function applyStringConditions( $requestOptions, $label, $keepDataValue ) {

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

	private static function applyBoundaryConditions( $requestOptions, $value, $isNumeric ) {
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

	private static function getSortKeyForItem( $store, $item ) {

		if ( $item instanceof DIWikiPage ) {
			$label = $store->getWikiPageSortKey( $item );
			$value = $label;
		} else {
			$label = ( $item instanceof DIBlob ) ? $item->getString() : '';
			$value = $item->getSortKey();
		}

		return [ $label, $value ];
	}

	private static function applySortRestriction( $requestOptions, &$result, $sortres, $isNumeric ) {

		if ( !$requestOptions->sort ) {
			return null;
		}

		$flag = $isNumeric ? SORT_NUMERIC : SORT_LOCALE_STRING;

		// SORT_NATURAL is selected on n-asc, n-desc
		if ( isset( $requestOptions->natural ) ) {
			$flag = SORT_NATURAL;
		}

		if ( $requestOptions->ascending ) {
			asort( $sortres, $flag );
		} else {
			arsort( $sortres, $flag );
		}

		$newres = [];

		foreach ( $sortres as $key => $value ) {
			$newres[] = $result[$key];
		}

		$result = $newres;
	}

	private static function applyLimitRestriction( $requestOptions, &$result ) {

		// In case of a `conditionConstraint` the restriction is set forth by the
		// SELECT statement.
		if ( isset( $requestOptions->conditionConstraint ) ) {
			return $result;
		}

		if ( $requestOptions->limit > 0 ) {
			return $result = array_slice( $result, $requestOptions->offset, $requestOptions->limit );
		}

		$result = array_slice( $result, $requestOptions->offset );
	}

}
