<?php

namespace Onoi\Tesa\LanguageDetector;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NullLanguageDetector implements LanguageDetector {

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return null
	 */
	public function detect( $text ) {
		return null;
	}

}
