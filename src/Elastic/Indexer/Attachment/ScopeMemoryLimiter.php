<?php

namespace SMW\Elastic\Indexer\Attachment;

/**
 * It has been observed that when large files are processed the job runner can
 * return with "Fatal error: Allowed memory size of ..." therefore temporary lift
 * and scope the memory limitation!
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ScopeMemoryLimiter {

	/**
	 * @var string
	 */
	private $memoryLimit = '1024M';

	/**
	 * @since 3.2
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function __construct( $memoryLimit = '1024M' ) {
		$this->memoryLimit = $memoryLimit;
	}

	/**
	 * @since 3.2
	 *
	 * @return int
	 */
	public function getMemoryLimit() : int {
		return ini_get( 'memory_limit' );
	}

	/**
	 * @since 3.2
	 *
	 * @param callable $callable
	 */
	public function execute( callable $callable ) {

		$memory_limit = ini_get( 'memory_limit' );

		if ( $this->toInt( $memory_limit ) < $this->toInt( $this->memoryLimit ) ) {
			ini_set( 'memory_limit', $this->memoryLimit );
		}

		( $callable )();

		ini_set( 'memory_limit', $memory_limit );
	}

	/**
	 * @see wfShorthandToInteger
	 */
	public function toInt( $string = '', $default = -1 ) {
		$string = trim( $string );

		if ( $string === '' ) {
			return $default;
		}

		$last = $string[strlen( $string ) - 1];
		$val = intval( $string );

		switch ( $last ) {
			case 'g':
			case 'G':
				$val *= 1024;
				// break intentionally missing
			case 'm':
			case 'M':
				$val *= 1024;
				// break intentionally missing
			case 'k':
			case 'K':
				$val *= 1024;
		}

		return $val;
	}

}
