<?php

namespace SMW;

use Onoi\MessageReporter\NullMessageReporter;
use Psr\Log\NullLogger;
use RuntimeException;
use SMW\Exception\StoreNotFoundException;

/**
 * Factory method that returns an instance of the default store, or an
 * alternative store instance.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class StoreFactory {

	private static array $instance = [];

	/**
	 * @since 1.9
	 *
	 * @template T of Store
	 * @param class-string<T>|null $class
	 * @return T
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
	public static function clear(): void {
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
