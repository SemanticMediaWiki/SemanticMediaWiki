<?php

namespace SMW\Tests\Utils;

use ReflectionClass;
use SMW\Query\ResultPrinter;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ResultPrinterReflector {

	/**
	 * Helper method sets result printer parameters
	 *
	 * @param ResultPrinter $instance
	 * @param array $parameters
	 *
	 * @return ResultPrinter
	 */
	public function addParameters( ResultPrinter $instance, array $parameters ) {

		$reflector = new ReflectionClass( $instance );
		$params = $reflector->getProperty( 'params' );
		$params->setAccessible( true );
		$params->setValue( $instance, $parameters );

		if ( isset( $parameters['searchlabel'] ) ) {
			$searchlabel = $reflector->getProperty( 'mSearchlabel' );
			$searchlabel->setAccessible( true );
			$searchlabel->setValue( $instance, $parameters['searchlabel'] );
		}

		if ( isset( $parameters['headers'] ) ) {
			$searchlabel = $reflector->getProperty( 'mShowHeaders' );
			$searchlabel->setAccessible( true );
			$searchlabel->setValue( $instance, $parameters['headers'] );
		}

		return $instance;
	}

	public function invoke( ResultPrinter $instance, $queryResult, $outputMode ) {

		$reflector = new ReflectionClass(  $instance );
		$method = $reflector->getMethod( 'getResultText' );
		$method->setAccessible( true );

		return $method->invoke( $instance, $queryResult, $outputMode );
	}

}