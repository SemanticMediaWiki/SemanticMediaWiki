<?php

namespace SMW;

/**
 * Interface describing a Id generrator
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface IdGenerator {

	/**
	 * Generates an id
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function generateId();

}
