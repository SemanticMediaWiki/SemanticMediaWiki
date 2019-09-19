<?php

namespace SMW\Query;

use SMW\Message;
use SMW\DIProperty;
use SMWInfolink as Infolink;
use SMWQuery as Query;

/**
 * Representing a Special:Ask query link to further query results
 *
 * @since 2.4
 *
 * @author mwjames
 */
class QueryLinker {

	/**
	 * @since 2.4
	 *
	 * @param Query $query
	 * @param array $parameters
	 *
	 * @return Infolink
	 */
	public static function get( Query $query, array $parameters = [] ) {

		$link = Infolink::newInternalLink( '', ':Special:Ask', false, [] );
		$link->setCompactLink( $GLOBALS['smwgCompactLinkSupport'] );

		foreach ( $parameters as $key => $value ) {

			if ( !is_string( $key ) ) {
				continue;
			}

			$link->setParameter( $value, $key );
		}

		$params = self::getParameters( $query );

		foreach ( $params as $key => $param ) {
			$link->setParameter( $param, is_string( $key ) ? $key : false );
		}

		$link->setCaption(
			' ' . Message::get( 'smw_iq_moreresults', Message::TEXT, Message::USER_LANGUAGE )
		);

		return $link;
	}

	private static function getParameters( $query ) {

		$params = [ trim( $query->getQueryString( true ) ) ];

		foreach ( $query->getExtraPrintouts() as /* PrintRequest */ $printout ) {
			if ( ( $serialisation = $printout->getSerialisation( true ) ) !== '' ) {
				$params[] = $serialisation;
			}
		}

		if ( $query->getMainLabel() !== false ) {
			$params['mainlabel'] = $query->getMainLabel();
		}

		if ( $query->getQuerySource() !== '' ) {
			$params['source'] = $query->getQuerySource();
		}

		$params['offset'] = $query->getOffset();

		if ( $params['offset'] === 0 ) {
			unset( $params['offset'] );
		}

		if ( $query->getLimit() > 0 ) {
			$params['limit'] = $query->getLimit();
		}

		$sortKeys = $query->getSortKeys();
		$count = count( $sortKeys );

		if ( $count == 0 ) {
			return $params;
		}

		$order = [];
		$sort = [];

		foreach ( $sortKeys as $key => $order_by ) {

			$order_by = strtolower( $order_by );

			// Default mode, skip
			if ( $count == 1 && $key === '' && $order_by === 'asc' ) {
				continue;
			}

			// Avoid predefined properties to appear as key as in _MDAT
			if ( $key !== '' && $key[0] === '_' ) {
				$key = DIProperty::newFromUserLabel( $key )->getLabel();
			} else {
				$key = str_replace( '_', ' ', $key );
			}

			$order[] = $order_by;
			$sort[] = $key;
		}

		if ( $sort !== [] ) {
			$params['order'] = implode( ',', $order );
			$params['sort'] = implode( ',', $sort );
		}

		return $params;
	}

}
