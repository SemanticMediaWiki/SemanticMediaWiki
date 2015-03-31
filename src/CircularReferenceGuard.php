<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CircularReferenceGuard {

	/**
	 * @var array
	 */
	private static $circularRefGuard = array();

	/**
	 * @var string
	 */
	private $namespace = '';

	/**
	 * @var integer
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
	 * @param integer $maxRecursionDepth
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
	 * @return boolean
	 */
	public function isCircularByRecursionFor( $hash ) {
		return $this->get( $hash ) > $this->maxRecursionDepth;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $hash
	 *
	 * @return integer
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
		self::$circularRefGuard[$namespace] =  array();
	}

}
