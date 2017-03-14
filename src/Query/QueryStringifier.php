<?php

namespace SMW\Query;

use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryStringifier {

	/**
	 * @since 2.5
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public static function rawUrlEncode( Query $query ) {
		return rawurlencode( self::get( $query ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public static function get( Query $query ) {
		$serialized = [];

		$serialized['conditions'] = $query->getQueryString();

		$serialized['parameters'] = [
			'limit=' . $query->getLimit(),
			'offset=' . $query->getOffset(),
			'mainlabel=' . $query->getMainlabel()
		];

		if ( $query->getQuerySource() !== null && $query->getQuerySource() !== '' ) {
			$serialized['parameters'] = array_merge( $serialized['parameters'], [ 'source=' . $query->getQuerySource() ] );
		}

		list( $serialized['sort'], $serialized['order'] ) = self::doSerializeSortKeys( $query );
		$serialized['printouts'] = self::doSerializePrintouts( $query );

		$encoded = $serialized['conditions'] . '|' .
			( $serialized['printouts'] !== [] ? implode( '|', $serialized['printouts'] ) . '|' : '' ) .
			implode( '|', $serialized['parameters'] ) .
			( $serialized['sort'] !==  [] ? '|sort=' . implode( ',', $serialized['sort'] ) : '' ) .
			( $serialized['order'] !== [] ? '|order=' . implode( ',', $serialized['order'] ) : '' );

		return $encoded;
	}

	private static function doSerializePrintouts( $query ) {

		$printouts = [];

		if ( $query->getExtraPrintouts() === null ) {
			return $printouts;
		}

		foreach ( $query->getExtraPrintouts() as $printout ) {
			$serialization = $printout->getSerialisation();
			if ( $serialization !== '?#' ) {
				// #show adds an extra = at the end which is interpret as
				// requesting an empty result hence it is removed
				$printouts[] = substr( $serialization, -1 ) === '=' ? substr( $serialization, 0, -1 ) : $serialization;
			}
		}

		return $printouts;
	}

	private static function doSerializeSortKeys( $query ) {

		$sort = [];
		$order = [];

		if ( $query->getSortKeys() === null ) {
			return [ $sort, $order ];
		}

		foreach ( $query->getSortKeys() as $key => $value ) {

			if ( $key === '' ) {
				continue;
			}

			$sort[] = $key;
			$order[] = strtolower( $value );
		}

		return [ $sort, $order ];
	}

}
