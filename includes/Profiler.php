<?php

namespace SMW;

/**
 * Encapsulates MediaWiki's profiling class
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This class encapsulates MediaWiki's profiling class, if enabled enabled
 * track the process caller allowing it to generate a call tree
 *
 * @ingroup Utility
 */
class Profiler {

	/** @var Profiler */
	private static $instance = null;

	/**
	 * Return a \Profiler instance
	 *
	 * @note Profiler::$__instance only made public in 1.22 therefore
	 * we use our own static to keep overhead at a minimum
	 *
	 * @see $wgProfiler
	 * @see http://www.mediawiki.org/wiki/Profiling#Profiling
	 *
	 * @since 1.9
	 *
	 * @return \Profiler|null
	 */
	public static function getInstance() {

		// Nothing we can do to avoid the global state here until we have
		// public access to Profiler::$__instance
		$profiler = isset( $GLOBALS['wgProfiler']['class'] );

		if ( self::$instance === null && $profiler ) {
			self::$instance = \Profiler::instance();
		}

		if ( !$profiler ) {
			self::reset();
		}

		return self::$instance;
	}

	/**
	 * Begin profiling of a processor
	 *
	 * @since 1.9
	 *
	 * @param string $name name of the function we will profile
	 * @param boolean $caller if the caller should be profiled as well
	 */
	public static function In( $name = false, $caller = false ) {

		$instance = self::getInstance();

		if ( $instance instanceof \Profiler ) {

			$processor = $name ? $name : wfGetCaller( 2 );
			$instance->profileIn( $processor );

			if ( $caller ) {
				$instance->profileIn( $processor . '-' . wfGetCaller( 3 ) );
			}
		}

		return $instance;
	}

	/**
	 * Stop profiling of a processor
	 *
	 * @since 1.9
	 *
	 * @param string $name name of the function we will profile
	 * @param boolean $caller if the caller should be profiled as well
	 */
	public static function Out( $name = false, $caller = false ) {

		$instance = self::getInstance();

		if ( $instance instanceof \Profiler ) {
			$processor = $name ? $name : wfGetCaller( 2 );

			if ( $caller ) {
				$instance->profileOut( $processor . '-' . wfGetCaller( 3 ) );
			}

			$instance->profileOut( $processor );
		}

		return $instance;
	}

	/**
	 * Reset the instance
	 *
	 * @since 1.9
	 */
	public static function reset() {
		self::$instance = null;
	}
}
