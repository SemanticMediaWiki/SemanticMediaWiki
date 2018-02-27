<?php

namespace SMW;

use RuntimeException;
use SMW\Exception\StoreNotFoundException;
use Onoi\MessageReporter\NullMessageReporter;
use Psr\Log\NullLogger;

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

	/**
	 * @var array
	 */
	private static $instance = array();

	/**
	 * @since 1.9
	 *
	 * @param string|null $store
	 *
	 * @return Store
	 * @throws RuntimeException
	 * @throws StoreNotFoundException
	 */
	public static function getStore( $store = null ) {

		if ( $store === null ) {
			$store = $GLOBALS['smwgDefaultStore'];
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
	}

	private static function newInstance( $store ) {

		if ( !class_exists( $store ) ) {
			throw new RuntimeException( "Expected a {$store} class" );
		}

		$instance = new $store;

		if ( !( $instance instanceof Store ) ) {
			throw new StoreNotFoundException( "{$store} can not be used as a store instance" );
		}

		$instance->setMessageReporter( new NullMessageReporter() );
		$instance->setLogger( new NullLogger() );

		return $instance;
	}

}
