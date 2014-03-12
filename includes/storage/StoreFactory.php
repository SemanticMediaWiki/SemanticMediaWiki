<?php

namespace SMW;

use RuntimeException;

/**
 * Factory method that handles store instantiation
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Factory method that handles store instantiation
 *
 * @ingroup Store
 */
class StoreFactory {

	/** @var Store[] */
	private static $instance = array();

	/**
	 * Returns a new store instance
	 *
	 * @since 1.9
	 *
	 * @param string $store
	 *
	 * @return Store
	 * @throws InvalidStoreException
	 */
	public static function newInstance( $store ) {

		if ( !class_exists( $store ) ) {
			throw new RuntimeException( "Expected a {$store} class" );
		}

		$instance = new $store;

		if ( !( $instance instanceof Store ) ) {
			throw new InvalidStoreException( "{$store} can not be used as a store instance" );
		}

		return $instance;
	}

	/**
	 * Returns an instance of the default store, or an alternative store
	 *
	 * @since 1.9
	 *
	 * @param string|null $store
	 *
	 * @return Store
	 */
	public static function getStore( $store = null ) {

		$configuration = Settings::newFromGlobals();
		$store = $store === null ? $configuration->get( 'smwgDefaultStore' ) : $store;

		if ( !isset( self::$instance[$store] ) ) {
			self::$instance[$store] = self::newInstance( $store );
			self::$instance[$store]->setConfiguration( $configuration );
		}

		return self::$instance[$store];
	}

	/**
	 * Reset instance
	 *
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = array();
	}
}
