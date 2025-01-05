<?php

namespace SMW\Utils;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class CircularReferenceGuard {

	/**
	 * @var array
	 */
	private static $circularRefGuard = [];

	/**
	 * @var string
	 */
	private $namespace = '';

	/**
	 * @var int
	 */
	private $maxRecursionDepth = 1;

	/**
	 * @since 2.2
	 *
	 * @param string $namespace
	 */
	public function __construct( $namespace = '' ) {
		$this->namespace = $namespace;
	}

	/**
	 * @since 2.2
	 *
	 * @param int $maxRecursionDepth
	 */
	public function setMaxRecursionDepth( $maxRecursionDepth ) {
		$this->maxRecursionDepth = (int)$maxRecursionDepth;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $hash
	 */
	public function mark( $hash ) {
		if ( !isset( self::$circularRefGuard[$this->namespace][$hash] ) ) {
			self::$circularRefGuard[$this->namespace][$hash] = 0;
		}

		self::$circularRefGuard[$this->namespace][$hash]++;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $hash
	 */
	public function unmark( $hash ) {
		if ( isset( self::$circularRefGuard[$this->namespace][$hash] ) && self::$circularRefGuard[$this->namespace][$hash] > 0 ) {
			return self::$circularRefGuard[$this->namespace][$hash]--;
		}

		unset( self::$circularRefGuard[$this->namespace][$hash] );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $hash
	 *
	 * @return bool
	 */
	public function isCircular( $hash ) {
		return $this->get( $hash ) > $this->maxRecursionDepth;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $hash
	 *
	 * @return int
	 */
	public function get( $hash ) {
		if ( isset( self::$circularRefGuard[$this->namespace][$hash] ) ) {
			return self::$circularRefGuard[$this->namespace][$hash];
		}

		return 0;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $namespace
	 */
	public function reset( $namespace ) {
		self::$circularRefGuard[$namespace] = [];
	}

}
