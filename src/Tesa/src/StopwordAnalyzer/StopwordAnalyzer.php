<?php

namespace Onoi\Tesa\StopwordAnalyzer;

/**
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
interface StopwordAnalyzer {

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return bool
	 */
	public function isStopWord( $word );

}
