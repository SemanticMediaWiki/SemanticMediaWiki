<?php

namespace SMW\Query;

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
	 *
	 * @return Infolink
	 */
	public static function get( Query $query ) {

		$link = Infolink::newInternalLink( '', ':Special:Ask', false, array() );
		$params = self::getParameters( $query );

		foreach ( $params as $key => $param ) {
			$link->setParameter( $param, is_string( $key ) ? $key : false );
		}

		$link->setCaption(
			' ' . wfMessage( 'smw_iq_moreresults' )->inContentLanguage()->text()
		);

		return $link;
	}

	private static function getParameters( $query ) {

		$params = array( trim( $query->getQueryString( true ) ) );

		foreach ( $query->getExtraPrintouts() as /* PrintRequest */ $printout ) {
			$serialization = $printout->getSerialisation( true );

			// TODO: this is a hack to get rid of the mainlabel param in case it was automatically added
			// by SMWQueryProcessor::addThisPrintout. Should be done nicer when this link creation gets redone.
			if ( $serialization !== '?#' && $serialization !== '?=' . $query->getMainLabel() . '#' ) {
				$params[] = $serialization;
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

		if ( count( $query->sortkeys ) > 0 ) {
			$order = implode( ',', $query->sortkeys );
			$sort = implode( ',', array_keys( $query->sortkeys ) );

			if ( $sort !== '' || $order != 'ASC' ) {
				$params['order'] = $order;
				$params['sort'] = $sort;
			}
		}

		return $params;
	}

}
