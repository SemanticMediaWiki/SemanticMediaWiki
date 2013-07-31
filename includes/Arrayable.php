<?php

namespace SMW;

use ArrayObject;
use InvalidArgumentException;

/**
 * Interface specifying returning an array
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
interface Arrayable {

	/**
	 * Returns an array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray();

}

