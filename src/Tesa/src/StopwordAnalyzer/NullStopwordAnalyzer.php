<?php

namespace Onoi\Tesa\StopwordAnalyzer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NullStopwordAnalyzer implements StopwordAnalyzer {

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return boolean
	 */
	public function isStopWord( $word ) {
		return false;
	}

}
