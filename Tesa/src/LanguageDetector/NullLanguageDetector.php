<?php

namespace Onoi\Tesa\LanguageDetector;

/**
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class NullLanguageDetector implements LanguageDetector {

	/**
	 * @since 0.1
	 *
	 * @param string $text
	 *
	 * @return null
	 */
	public function detect( $text ) {
		return null;
	}

}
