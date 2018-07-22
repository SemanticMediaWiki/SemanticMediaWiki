<?php

namespace SMW;

use RuntimeException;
use SMW\Exception\StoreNotFoundException;
use Onoi\MessageReporter\NullMessageReporter;
use Psr\Log\NullLogger;

/**
 * Factory method that returns an instance of the default store, or an
 * alternative store instance.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreFactory {

	/**
	 * @var array
	 */
	private static $instance = [];

	/**
	 * @since 1.9
	 *
	 * @param string|null $class
	 *
	 * @return Store
	 * @throws RuntimeException
	 * @throws StoreNotFoundException
	 */
	public static function getStore( $class = null ) {

		if ( $class === null ) {
			$class = $GLOBALS['smwgDefaultStore'];
		}

		if ( !isset( self::$instance[$class] ) ) {
			self::$instance[$class] = self::newFromClass( $class );
		}

		return self::$instance[$class];
	}

	/**
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = [];
	}

	private static function newFromClass( $class ) {

		if ( !class_exists( $class ) ) {
			throw new RuntimeException( "{$class} was not found!" );
		}

		$instance = new $class;

		if ( !( $instance instanceof Store ) ) {
			throw new StoreNotFoundException( "{$class} cannot be used as a store instance!" );
		}

		$instance->setMessageReporter( new NullMessageReporter() );
		$instance->setLogger( new NullLogger() );

		return $instance;
	}

}
