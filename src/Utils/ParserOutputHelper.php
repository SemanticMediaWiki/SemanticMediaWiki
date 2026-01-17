<?php

namespace SMW\Utils;

use LogicException;
use MediaWiki\Parser\ParserOutput;

/**
 * Utility class for handling ParserOutput text extraction across different MediaWiki versions
 *
 * @license GPL-2.0-or-later
 * @since 6.02
 *
 * @author Semantic MediaWiki
 */
class ParserOutputHelper {

	/**
	 * Get text from ParserOutput using the appropriate method based on MediaWiki version
	 *
	 * This method provides a compatibility layer for the getText() deprecation
	 * in MediaWiki core. It prioritizes the new getContentHolderText() method
	 * while maintaining backward compatibility with older versions.
	 *
	 * @param ParserOutput $parserOutput
	 * @return string The text content, or empty string if no content is available
	 */
	public static function getText( ParserOutput $parserOutput ): string {
		if ( method_exists( $parserOutput, 'getContentHolderText' ) ) {
			try {
				return $parserOutput->getContentHolderText();
			} catch ( LogicException $e ) {
				// Handle case where there is no body content
				return '';
			}
		} elseif ( method_exists( $parserOutput, 'getRawText' ) ) {
			return $parserOutput->getRawText();
		} elseif ( method_exists( $parserOutput, 'getText' ) ) {
			return $parserOutput->getText();
		}

		return '';
	}

}
