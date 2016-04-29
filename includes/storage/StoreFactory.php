<?php

namespace SMW;

use RuntimeException;

/**
 * Factory method that returns an instance of the default store, or an
 * alternative store
 *
 * @ingroup Factory
 * @ingroup Store
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class StoreFactory {

	/** @var Store[] */
	private static $instance = array();

	/** @var string */
	private static $defaultStore = null;

	/**
	 * @since 1.9
	 *
	 * @param string|null $store
	 *
	 * @return Store
	 * @throws RuntimeException
	 * @throws InvalidStoreException
	 */
	public static function getStore( $store = null ) {

		if ( self::$defaultStore === null ) {
			self::$defaultStore = self::getConfiguration()->get( 'smwgDefaultStore' );
		}

		if ( $store === null ) {
			$store = self::$defaultStore;
		}

		if ( !isset( self::$instance[$store] ) ) {
			self::$instance[$store] = self::newInstance( $store );
		}

		return self::$instance[$store];
	}

	/**
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = array();
		self::$defaultStore = null;
	}

	private static function getConfiguration() {
		return Settings::newFromGlobals();
	}

	private static function newInstance( $store ) {

		if ( !class_exists( $store ) ) {
			throw new RuntimeException( "Expected a {$store} class" );
		}

		$instance = new $store;

		if ( !( $instance instanceof Store ) ) {
			throw new InvalidStoreException( "{$store} can not be used as a store instance" );
		}

		return $instance;
	}

}
