<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DataItems\Property;
use SMW\RequestOptions;
use SMW\Store;
use Traversable;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PValueLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.0
	 */
	public function getVersion(): string {
		return __METHOD__ . self::VERSION;
	}

	/**
	 * @since 3.0
	 */
	public function lookup( array $parameters ): array {
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

			$property = Property::newFromUserLabel(
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

	private function is_iterable( $obj ): bool {
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof Traversable ) );
	}

}
