<?php

namespace SMW\Query\PrintRequest;

use Linker;
use SMW\Query\PrintRequest;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class Formatter {

	const FORMAT_WIKI = SMW_OUTPUT_WIKI;
	const FORMAT_HTML = SMW_OUTPUT_HTML;

	/**
	 * Obtain an HTML-formatted or Wiki-formatted representation of the label.
	 * The $linker is a Linker object used for generating hyperlinks.
	 * If it is NULL, no links will be created.
	 *
	 * @since 2.5
	 *
	 * @param PrintRequest $printRequest
	 * @param Linker|null $linker
	 * @param integer|null $outputType
	 *
	 * @return string
	 */
	public static function format( PrintRequest $printRequest, $linker = null, $outputType = null ) {

		if ( $outputType === self::FORMAT_WIKI || $outputType === SMW_OUTPUT_WIKI ) {
			return self::getWikiText( $printRequest, $linker );
		}

		return self::getHTMLText( $printRequest, $linker );
	}

	private static function getHTMLText( $printRequest, $linker = null ) {

		$label = $printRequest->getLabel();

		if ( \SMW\Parser\InTextAnnotationParser::hasPropertyLink( $label ) ) {
			return \SMW\Message::get( [ 'smw-parse', $label ], \SMW\Message::PARSE );
		}

		$label = htmlspecialchars( $printRequest->getLabel() );

		if ( $linker === null || $linker === false || $label === '' ) {
			return $label;
		}

		switch ( $printRequest->getMode() ) {
			case PrintRequest::PRINT_CATS:
				return Linker::link( Title::newFromText( 'Categories', NS_SPECIAL ), $label );
			case PrintRequest::PRINT_CCAT:
				return Linker::link( $printRequest->getData(), $label );
			case PrintRequest::PRINT_CHAIN:
			case PrintRequest::PRINT_PROP:
				return $printRequest->getData()->getShortHTMLText( $linker );
			case PrintRequest::PRINT_THIS:
			default:
				return $label;
		}
	}

	private static function getWikiText( $printRequest, $linker = false ) {

		$label = $printRequest->getLabel();

		if ( $linker === null || $linker === false || $label === '' ) {
			return $label;
		}

		switch ( $printRequest->getMode() ) {
			case PrintRequest::PRINT_CATS:
				return '[[:' . 'Special:Categories' . '|' . $label . ']]';
			case PrintRequest::PRINT_CHAIN:
			case PrintRequest::PRINT_PROP:
				return $printRequest->getData()->getShortWikiText( $linker );
			case PrintRequest::PRINT_CCAT:
				return '[[:' . $printRequest->getData()->getPrefixedText() . '|' . $label . ']]';
			case PrintRequest::PRINT_THIS:
			default:
				return $label;
		}
	}

}
