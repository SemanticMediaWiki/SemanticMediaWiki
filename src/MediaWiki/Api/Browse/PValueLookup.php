<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\RequestOptions;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWDITime as DIime;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PValueLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|integer
	 */
	public function getVersion() {
		return __METHOD__ . self::VERSION;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {

		$limit = 20;
		$offset = 0;

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$res = [];
		$continueOffset = 0;
		$property = null;
		$sort = false;
		$count = 0;

		if ( isset( $parameters['property'] ) ) {
			$property = $parameters['property'];

			// Get the last which represents the final output
			// Foo.Bar.Foobar.Baz
			if ( strpos( $property, '.' ) !== false ) {
				$chain = explode( '.', $property );
				$property = array_pop( $chain );
			}
		}

		if ( $property === '' || $property === null ) {
			return [];
		}

		// Generally we don't want to sort results to avoid having the DB to use
		// temporary tables/filesort when the value pool is very large
		if ( isset( $parameters['sort'] ) ) {
			$sort = in_array( strtolower( $parameters['sort'] ), [ 'asc', 'desc' ] ) ? $parameters['sort'] : 'asc';
		}

		if ( isset( $parameters['search'] ) ) {

			$opts = new RequestOptions();
			$opts->limit = $limit;
			$opts->offset = $offset;
			$opts->sort = $sort;

			$property = DIProperty::newFromUserLabel(
				$property
			);

			$proximityPropertyValueLookup = $this->store->service(
				'ProximityPropertyValueLookup'
			);

			$res = $proximityPropertyValueLookup->lookup(
				$property,
				$parameters['search'],
				$opts
			);

			if ( $this->is_iterable( $res ) ) {
				$count = count( $res );
			}

			if ( $count > $limit ) {
				$continueOffset = $offset + $count;
				array_pop( $res );
			}
		}

		// Changing this output format requires to set a new version
		$res = [
			'query' => $res,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'pvalue',
				'limit' => $limit,
				'count' => $count
			]
		];

		return $res;
	}

	private function is_iterable( $obj ) {
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof \Traversable ) );
	}

}
