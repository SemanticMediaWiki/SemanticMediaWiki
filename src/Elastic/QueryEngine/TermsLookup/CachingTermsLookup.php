<?php

namespace SMW\Elastic\QueryEngine\TermsLookup;

use Onoi\Cache\Cache;
use RuntimeException;
use SMW\Elastic\QueryEngine\Condition;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CachingTermsLookup extends TermsLookup {

	/**
	 * Identifies the cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:elastic:lookup';

	/**
	 * @var TermsLookup
	 */
	private $termsLookup;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var []
	 */
	private $quick_cache = [];

	/**
	 * @since 3.0
	 *
	 * @param TermsLookup $termsLookup
	 * @param Cache $cache
	 */
	public function __construct( TermsLookup $termsLookup, Cache $cache ) {
		$this->termsLookup = $termsLookup;
		$this->cache = $cache;
	}

	/**
	 * @since 3.0
	 */
	public function clear() {
		$this->quick_cache = [];
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function makeCacheKey() {
		return smwfCacheKey( self::CACHE_NAMESPACE, func_get_args() );
	}

	/**
	 * @since 3.0
	 *
	 * @param $type
	 * @param Parameters $parameters
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function lookup( $type, Parameters $parameters ) {

		if ( $type === 'concept' ) {
			return $this->concept_lookup( $parameters );
		}

		if ( $type === 'chain' ) {
			return $this->chain_lookup( $parameters );
		}

		if ( $type === 'predef' ) {
			return $this->predef_lookup( $parameters );
		}

		if ( $type === 'inverse' ) {
			return $this->inverse_lookup( $parameters );
		}

		throw new RuntimeException( "$type is unknown!" );
	}

	/**
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function concept_lookup( Parameters $parameters ) {

		// @see Indexer::delete
		$parameters->set( 'id', md5( $parameters->get( 'id' ) ) );
		$id = $parameters->get( 'id' );
		$parameters->set( 'count', 0 );

		$threshold = $this->termsLookup->getOption(
			'concept.terms.lookup.result.size.index.write.threshold',
			100
		);

		$parameters->set( 'threshold', $threshold );

		$key = $this->makeCacheKey(
			$id,
			$threshold,
			$parameters->get( 'fingerprint' )
		);

		if ( isset( $this->quick_cache[$key] ) ) {
			$parameters->set( 'query.info', $this->quick_cache[$key]['info'] );
			return $this->quick_cache[$key]['params'];
		}

		if ( ( $count = $this->cache->fetch( $key ) ) !== false ) {

			$info = [
				'cached_concept_lookup' => $parameters->get( 'query.string' ),
				'count' => $count,
				'isFromCache' => ['id' => $id ]
			];

			$parameters->set( 'query.info', $info );

			$params = $this->termsLookup->terms_filter(
				'_id',
				$this->termsLookup->path_filter( $id )
			);

			$this->quick_cache[$key] = [
				'params' => $params,
				'info' => $parameters->get( 'query.info' )
			];

			return $params;
		}

		$ttl = $this->termsLookup->getOption(
			'concept.terms.lookup.cache.lifetime',
			60
		);

		$params = $this->termsLookup->concept_index_lookup(
			$parameters
		);

		$count = $parameters->get( 'count' );

		if ( $count >= $threshold ) {
			$this->cache->save( $key, $count, $ttl );
		}

		$this->quick_cache[$key] = [
			'params' => $params,
			'info' => $parameters->get( 'query.info' )
		];

		if ( isset( $params['type'] ) && isset( $params['id'] ) ) {
			$params = $this->termsLookup->terms_filter(
				'_id',
				$this->termsLookup->path_filter( $params['id'] )
			);
		}

		return $params;
	}

	/**
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function chain_lookup( Parameters $parameters ) {

		$params = $parameters->get( 'params' );

		if ( $params instanceof Condition ) {
			$id = 'chain:' . md5( $params->__toString() );
		} else {
			$id = 'chain:' . md5( json_encode( $params ) );
		}

		$parameters->set( 'id', $id );
		$parameters->set( 'count', 0 );

		$threshold = $this->termsLookup->getOption(
			'subquery.terms.lookup.result.size.index.write.threshold',
			100
		);

		$parameters->set( 'threshold', $threshold );

		$key = $this->makeCacheKey(
			$id,
			$threshold
		);

		if ( ( $count = $this->cache->fetch( $key ) ) !== false ) {

			$info = [
				'cached_chain_lookup' => [
					$parameters->get( 'property.key' ),
					$parameters->get( 'query.string' )
				],
				'count' => $count,
				'isFromCache' => [ 'id' => $id ]
			];

			$parameters->set( 'query.info', $info );

			$params = $this->termsLookup->terms_filter(
				$parameters->get( 'terms_filter.field' ),
				$this->termsLookup->path_filter( $id )
			);

			return $params;
		}

		$ttl = $this->termsLookup->getOption(
			'subquery.terms.lookup.cache.lifetime',
			60
		);

		$params = $this->termsLookup->chain_index_lookup(
			$parameters
		);

		$count = $parameters->get( 'count' );

		if ( $count >= $threshold ) {
			$this->cache->save( $key, $count, $ttl );
		}

		return $params;
	}

	/**
	 * `[[Has monolingual text:: <q>[[Text::two]] [[Language code::fr]]</q> ]] [[Has number::123]]`
	 *
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function predef_lookup( Parameters $parameters ) {

		$params = $parameters->get( 'params' );

		if ( $params instanceof Condition ) {
			$id = 'pre:' . md5( $params->__toString() );
		} else {
			$id = 'pre:' . md5( json_encode( $params ) );
		}

		$parameters->set( 'id', $id );
		$parameters->set( 'count', 0 );

		$threshold = $this->termsLookup->getOption(
			'subquery.terms.lookup.result.size.index.write.threshold',
			100
		);

		$parameters->set( 'threshold', $threshold );

		$key = $this->makeCacheKey(
			$id,
			$threshold
		);

		if ( ( $count = $this->cache->fetch( $key ) ) !== false ) {

			$info = [
				'cached_predefined_lookup' => $parameters->get( 'query.string' ),
				'count' => $count,
				'isFromCache' => [ 'id' => $id ]
			];

			$parameters->set( 'query.info', $info );

			$params = $this->termsLookup->terms_filter(
				$parameters->get( 'field' ),
				$this->termsLookup->path_filter( $id )
			);

			return $params;
		}

		$ttl = $this->termsLookup->getOption(
			'subquery.terms.lookup.cache.lifetime',
			60
		);

		$params = $this->termsLookup->predef_index_lookup(
			$parameters
		);

		$count = $parameters->get( 'count' );

		if ( $count >= $threshold ) {
			$this->cache->save( $key, $count, $ttl );
		}

		return $params;
	}

	/**
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function inverse_lookup( Parameters $parameters ) {

		$params = $parameters->get( 'params' );

		if ( $params instanceof Condition ) {
			$id = 'inv:' . md5( $parameters->get( 'field' ) . $params->__toString() );
		} else {
			$id = 'inv:' . md5( json_encode( [ $parameters->get( 'field' ), $params ] ) );
		}

		$parameters->set( 'id', $id );
		$parameters->set( 'count', 0 );

		$threshold = $this->termsLookup->getOption(
			'subquery.terms.lookup.result.size.index.write.threshold',
			100
		);

		$parameters->set( 'terms_filter.field', '_id' );
		$parameters->set( 'threshold', $threshold );

		$key = $this->makeCacheKey(
			$id,
			$threshold
		);

		if ( ( $count = $this->cache->fetch( $key ) ) !== false ) {

			$info = [
				'cached_inverse_lookup' => [
					$parameters->get( 'property.key' ),
					$parameters->get( 'query.string' )
				],
				'count' => $count,
				'isFromCache' => [ 'id' => $id ]
			];

			$parameters->set( 'query.info', $info );

			// Return the _id field
			$params = $this->termsLookup->terms_filter(
				$parameters->get( 'terms_filter.field' ),
				$this->termsLookup->path_filter( $id )
			);

			return $params;
		}

		$ttl = $this->termsLookup->getOption(
			'subquery.terms.lookup.cache.lifetime',
			60
		);

		$params = $this->termsLookup->inverse_index_lookup(
			$parameters
		);

		$count = $parameters->get( 'count' );

		if ( $count >= $threshold ) {
			$this->cache->save( $key, $count, $ttl );
		}

		return $params;
	}

}
