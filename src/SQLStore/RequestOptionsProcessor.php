<?php

namespace SMW\SQLStore;

use SMW\MediaWiki\Database;
use SMWStringCondition as StringCondition;
use SMWRequestOptions as RequestOptions;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;

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

				$sqlConds .= ( ( $addAnd || ( $sqlConds !== '' ) ) ? ' AND ' : '' ) . $labelCol . ' LIKE ' . $connection->addQuotes( $string );
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
	public function applyRequestOptionsTo( array $data, RequestOptions $requestoptions = null ) {

		if ( $data === array() || $requestoptions === null ) {
			return $data;
		}

		$result = array();
		$sortres = array();

		$sampleDataItem = reset( $data );
		$numeric = is_numeric( $sampleDataItem->getSortKey() );

		$i = 0;

		foreach ( $data as $item ) {
			$ok = true; // keep datavalue only if this remains true

			if ( $item instanceof DIWikiPage ) {
				$label = $this->store->getWikiPageSortKey( $item );
				$value = $label;
			} else {
				$label = ( $item instanceof DIBlob ) ? $item->getString() : '';
				$value = $item->getSortKey();
			}

			if ( $requestoptions->boundary !== null ) { // apply value boundary
				$strc = $numeric ? 0 : strcmp( $value, $requestoptions->boundary );

				if ( $requestoptions->ascending ) {
					if ( $requestoptions->include_boundary ) {
						$ok = $numeric ? ( $value >= $requestoptions->boundary ) : ( $strc >= 0 );
					} else {
						$ok = $numeric ? ( $value > $requestoptions->boundary ) : ( $strc > 0 );
					}
				} else {
					if ( $requestoptions->include_boundary ) {
						$ok = $numeric ? ( $value <= $requestoptions->boundary ) : ( $strc <= 0 );
					} else {
						$ok = $numeric ? ( $value < $requestoptions->boundary ) : ( $strc < 0 );
					}
				}
			}

			foreach ( $requestoptions->getStringConditions() as $strcond ) { // apply string conditions
				switch ( $strcond->condition ) {
					case StringCondition::STRCOND_PRE:
						$ok = $ok && ( strpos( $label, $strcond->string ) === 0 );
						break;
					case StringCondition::STRCOND_POST:
						$ok = $ok && ( strpos( strrev( $label ), strrev( $strcond->string ) ) === 0 );
						break;
					case StringCondition::STRCOND_MID:
						$ok = $ok && ( strpos( $label, $strcond->string ) !== false );
						break;
				}
			}

			if ( $ok ) {
				$result[$i] = $item;
				$sortres[$i] = $value;
				$i++;
			}
		}

		if ( $requestoptions->sort ) {
			$flag = $numeric ? SORT_NUMERIC : SORT_LOCALE_STRING;

			if ( $requestoptions->ascending ) {
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

		if ( $requestoptions->limit > 0 ) {
			$result = array_slice( $result, $requestoptions->offset, $requestoptions->limit );
		} else {
			$result = array_slice( $result, $requestoptions->offset );
		}

		return $result;
	}

}
