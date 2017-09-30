<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DIProperty;
use Exception;
use SMW\RequestOptions;
use SMW\Store;
use SMW\StringCondition;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ListAugmentor
	 */
	private $listAugmentor;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store, ListAugmentor $listAugmentor ) {
		$this->store = $store;
		$this->listAugmentor = $listAugmentor;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|integer
	 */
	public function getVersion() {
		return 'ListLookup:' . self::VERSION;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {

		$requestOptions = $this->newRequestOptions(
			$parameters
		);

		$limit = $requestOptions->getLimit();
		$list = [];
		$continueOffset = 0;

		// Increase by one to look ahead
		$requestOptions->setLimit( $limit + 1 );
		$ns = isset( $parameters['ns'] ) ? $parameters['ns'] : '';

		switch ( $ns ) {
			case NS_CATEGORY:
				$type = 'category';
				break;
			case SMW_NS_PROPERTY:
				$type = 'property';
				break;
			case SMW_NS_CONCEPT:
				$type = 'concept';
				break;
			default:
				$type = 'unlisted';
				break;
		}

		if ( isset( $parameters['search'] ) ) {
			list( $list, $continueOffset ) = $this->search( $ns, $requestOptions );
		}

		// Changing this output format requires to set a new version
		$res = [
			'query' => $list,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => $type,
				'limit' => $limit,
				'count' => count( $list )
			]
		];

		$this->listAugmentor->augment(
			$res,
			$parameters
		);

		return $res;
	}

	private function newRequestOptions( $parameters ) {

		$limit = 50;
		$offset = 0;
		$search = '';

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( $limit );
		$requestOptions->setOffset( $offset );

		if ( isset( $parameters['search'] ) ) {
			$search = $parameters['search'];

			if ( $search !== '' && $search{0} !== '_' ) {
				$search = str_replace( "_", " ", $search );
			}

			$requestOptions->addStringCondition(
				$search,
				StringCondition::STRCOND_MID
			);

			// Disjunctive condition to allow for auto searches to match foaf OR Foaf
			$requestOptions->addStringCondition(
				ucfirst( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			// Allow something like FOO to match the search string `foo`
			$requestOptions->addStringCondition(
				strtoupper( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			$requestOptions->addStringCondition(
				strtolower( $search ),
				StringCondition::STRCOND_MID,
				true
			);
		}

		return $requestOptions;
	}

	private function search( $ns, $requestOptions ) {

		$limit = $requestOptions->getLimit() - 1;
		$list = [];

		$fields = [
			'smw_id',
			'smw_title'
		];

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = $this->store->getSQLOptions( $requestOptions, 'smw_sort' );
		$fields[] = 'smw_sort';

		$conditions = [
			'smw_namespace' => $ns,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		if ( ( $cond = $this->store->getSQLConditions( $requestOptions, '', 'smw_sortkey', false ) ) !== '' ) {
			$conditions[] = $cond;
			$fields[] = 'smw_sortkey';
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$res = $connection->select(
			$connection->tableName( SQLStore::ID_TABLE ),
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		$count = 0;
		$continueOffset = 0;

		foreach ( $res as $row ) {

			$key = $row->smw_title;
			$count++;

			if ( $count > $limit ) {
				$continueOffset = $requestOptions->getOffset() + $limit;
				break;
			}

			if ( $ns === SMW_NS_PROPERTY ) {
				try {
					$label = DIProperty::newFromUserLabel( $row->smw_title )->getLabel();
				} catch( Exception $e ) {
					continue;
				}

			} else {
				$label = str_replace( '_', ' ', $row->smw_title );
			}

			$list[$key] = [
				 // Only keep the ID as internal field which is
				 // removed by the Augmentor
				'id'    => $row->smw_id,
				'label' => $label,
				'key'   => $key
			];
		}

		return [ $list, $continueOffset ];
	}

}
