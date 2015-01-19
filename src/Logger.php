<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
interface Logger {

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 * @param string $performer
	 * @param string $target
	 * @param string $comment
	 */
	public function logToTable( $type, $performer, $target, $comment );

	/**
	 * @since 2.1
	 *
	 * @param string $fname
	 * @param string $comment
	 */
	public function log( $fname, $comment );

}
