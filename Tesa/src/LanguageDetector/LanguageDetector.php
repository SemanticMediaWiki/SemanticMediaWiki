<?php

namespace Onoi\Tesa\LanguageDetector;

/**
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
interface LanguageDetector {

	/**
	 * @since 0.1
	 *
	 * @param string $text
	 *
	 * @return string|null
	 */
	public function detect( $text );

}
