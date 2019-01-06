<?php

namespace SMW\Query;

use RuntimeException;
use SMW\QueryEngine;
use SMW\Store;
use SMW\StoreAware;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QuerySourceFactory {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var array
	 */
	private $querySources = [];

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param array $querySources
	 */
	public function __construct( Store $store, $querySources = [] ) {
		$this->store = $store;
		$this->querySources = $querySources;

		// Standard store
		$this->querySources['sql_store'] = 'SMW\SQLStore\SQLStore';
	}

	/**
	 * @see DefaultSettings::$smwgQuerySources
	 *
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return QueryEngine|Store
	 * @throws RuntimeException
	 */
	public function get( $source = null ) {

		$params = [];

		if ( $source !== '' && isset( $this->querySources[$source] ) ) {

			$querySource = $this->querySources[$source];

			// [ '\SMW\FooHandler', ... parameters ],
			if ( is_array( $querySource ) ) {
				$source = array_shift( $querySource );
				$params = $querySource;
			} else {
				$source = $this->querySources[$source];
			}
		}

		// Fallback to the default store
		if ( $source === null || !class_exists( $source ) ) {
			$source = $this->store;
		} elseif ( $params !== [] ) {
			$source = new $source( $params );
		} else {
			$source = new $source;
		}

		if ( !$source instanceof QueryEngine && !$source instanceof Store ) {
			throw new RuntimeException(  get_class( $source ) . " does not match the expected QueryEngine interface." );
		}

		if ( $source instanceof StoreAware ) {
			$source->setStore( $this->store );
		}

		return $source;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return string
	 */
	public function toString( $source = null ) {

		if ( $source === 'sql_store' ) {
			return 'SMWSQLStore';
		}

		if ( $source !== '' && $source !== null ) {
			return $source;
		}

		return json_encode( $this->store->getInfo( 'store' ) );
	}

}
