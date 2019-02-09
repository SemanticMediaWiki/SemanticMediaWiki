<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\Store;
use SMW\DIWikiPage;
use SMWQueryProcessor as QueryProcessor;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CheckQueryTask extends Task {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( $parameters['subject'] === '' || $parameters['query'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize(
			$parameters['subject']
		);

		foreach ( $parameters['query'] as $hash => $raw_query ) {

			// @see PostProcHandler::addQuery
			list( $query_hash, $result_hash ) = explode( '#', $hash );

			// Doesn't influence the fingerprint (aka query cache) so just
			// ignored it
			$printouts = [];
			$parameters = $raw_query['parameters'];

			if ( isset( $parameters['sortkeys']  ) ) {
				$order = [];
				$sort = [];

				foreach ( $parameters['sortkeys'] as $key => $order_by ) {
					$order[] = strtolower( $order_by );
					$sort[] = $key;
				}

				$parameters['sort'] = implode( ',', $sort );
				$parameters['order'] = implode( ',', $order );
				// Avoid "PHP Notice:  Undefined index: original-value ...
				// param-processor\src\Processor.php"
				unset( $parameters['sortkeys'] );
			}

			QueryProcessor::addThisPrintout( $printouts, $parameters );

			$query = QueryProcessor::createQuery(
				$raw_query['conditions'],
				QueryProcessor::getProcessedParams( $parameters, $printouts ),
				QueryProcessor::INLINE_QUERY,
				'',
				$printouts
			);

			$query->setUnboundLimit(
				$parameters['limit']
			);

			$query->setUnboundOffset(
				$parameters['offset']
			);

			$query->setQueryMode(
				$parameters['querymode']
			);

			$query->setContextPage(
				$subject
			);

			$query->setOption( Query::PROC_CONTEXT, 'task.api' );

			$res = $this->store->getQueryResult(
				$query
			);

			// If the result_hash from before the post-edit and the result_hash
			// after the post-edit check are not the same then it means that the
			// list of entities changed hence send a `reload` command to the
			// API promise.
			if ( $result_hash !== $res->getHash( 'quick' ) ) {
				return [ 'done' => true, 'reload' => true ];
			}
		}

		return [ 'done' => true ];
	}

}
