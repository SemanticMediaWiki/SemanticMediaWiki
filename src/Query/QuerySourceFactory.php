<?php

namespace SMW\Query;

use SMW\Store;
use SMW\QueryEngine;
use SMW\StoreAware;
use RuntimeException;

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
	private $querySources = array();

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param array $querySources
	 */
	public function __construct( Store $store, $querySources = array() ) {
		$this->store = $store;
		$this->querySources = $querySources;
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
	public function getWithLocalFallback( $source = null ) {

		if ( $source !== '' && isset( $this->querySources[$source] ) ) {
			$source = $this->querySources[$source];
		}

		if ( $source !== '' && class_exists( $source ) ) {
			$source = new $source;
		} else {
			$source = $this->store;
		}

		if ( !$source instanceof QueryEngine && !$source instanceof Store ) {
			throw new RuntimeException(  get_class( $source ) . " does not match the expected QueryEngine interface." );
		}

		if ( $source instanceof StoreAware ) {
			$source->setStore( $this->store );
		}

		return $source;
	}

}
