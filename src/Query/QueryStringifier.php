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
		return rawurlencode( self::toString( $query ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 * @param boolean $printParameters
	 *
	 * @return string
	 */
	public static function toArray( Query $query, $printParameters = false ) {

		$serialized = [];
		$serialized['conditions'] = $query->getQueryString();

		$serialized['parameters'] = [
			'limit' => $query->getLimit(),
			'offset' => $query->getOffset(),
			'mainlabel' => $query->getMainlabel()
		];

		if ( $query->getQuerySource() !== null && $query->getQuerySource() !== '' ) {
			$serialized['parameters']['source'] = $query->getQuerySource();
		}

		list( $serialized['sort'], $serialized['order'] ) = self::sortKeys(
			$query
		);

		if ( $serialized['sort'] !== [] ) {
			$serialized['parameters']['sort'] = implode( ',', $serialized['sort'] );
		}

		if ( $serialized['order'] !== [] ) {
			$serialized['parameters']['order'] = implode( ',', $serialized['order'] );
		}

		unset( $serialized['sort'] );
		unset( $serialized['order'] );

		$serialized['printouts'] = self::printouts(
			$query,
			$printParameters
		);

		return $serialized;
	}

	/**
	 * @since 2.5
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public static function toJson( Query $query, $printParameters = false ) {
		return json_encode( self::toArray( $query, $printParameters ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public static function toString( Query $query, $printParameters = false ) {

		$serialized = self::toArray( $query, $printParameters );

		$string = $serialized['conditions'];

		if ( $serialized['printouts'] !== [] ) {
			$string .= '|' . implode( '|', $serialized['printouts'] );
		}

		foreach ( $serialized['parameters'] as $key => $value ) {
			$string .= "|$key=$value";
		}

		return $string;
	}

	private static function printouts( $query, $showParams = false ) {

		$printouts = [];

		if ( $query->getExtraPrintouts() === null ) {
			return $printouts;
		}

		foreach ( $query->getExtraPrintouts() as $printout ) {
			if ( ( $serialisation = $printout->getSerialisation( $showParams ) ) !== '' ) {
				$printouts[] = $serialisation;
			}
		}

		return $printouts;
	}

	private static function sortKeys( $query ) {

		$sort = [];
		$order = [];

		if ( $query->getSortKeys() === null ) {
			return [ $sort, $order ];
		}

		foreach ( $query->getSortKeys() as $key => $value ) {

			if ( $key === '' ) {
				continue;
			}

			$sort[] = str_replace( '_', ' ', $key );
			$order[] = strtolower( $value );
		}

		return [ $sort, $order ];
	}

}
