<?php

namespace Onoi\Tesa\Synonymizer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
interface Synonymizer {

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return string
	 */
	public function synonymize( $word );

}
