<?php

namespace SMW\DataValues\ValueFormatters;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
interface ValueFormatter {

	/**
	 * @since 2.4
	 *
	 * @param mixed $type
	 * @param mixed|null $linker
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function format( $type, $linker = null );

}
