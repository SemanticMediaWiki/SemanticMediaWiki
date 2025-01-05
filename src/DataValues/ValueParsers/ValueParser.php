<?php

namespace SMW\DataValues\ValueParsers;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
interface ValueParser {

	/**
	 * @since  2.2
	 *
	 * @param mixed $value
	 *
	 * @return array|null
	 */
	public function parse( $value );

	/**
	 * @since  2.2
	 *
	 * @return array
	 */
	public function getErrors();

}
