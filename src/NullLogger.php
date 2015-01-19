<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NullLogger implements Logger {

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function logToTable( $type, $performer, $target, $comment ) {}

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function log( $fname, $comment ) {}

}
