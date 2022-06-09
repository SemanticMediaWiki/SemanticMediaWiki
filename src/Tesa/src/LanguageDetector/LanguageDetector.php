<?php

namespace Onoi\Tesa\LanguageDetector;

/**
 * @license GNU GPL v2+
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
