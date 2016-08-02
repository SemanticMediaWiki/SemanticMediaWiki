<?php

namespace Onoi\Tesa\Synonymizer;

use Cdb\Reader;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NullSynonymizer implements Synonymizer {

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return string
	 */
	public function synonymize( $word ) {
		return $word;
	}

}
