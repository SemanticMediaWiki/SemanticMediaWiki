<?php

namespace SMW\Query;

use SMW\DIWikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Utils\Tokenizer;
use SMWDIBlob as DIBlob;

/**
 * For a wildcard search, build tokens from the query string, and allow to highlight
 * them in the result set.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryToken {

	// TokensHighlighter
	// QueryTokensHighlighter

	/**
	 * Highlighter marker type
	 */
	const HL_WIKI = 'HL_WIKI';
	const HL_BOLD = 'HL_BOLD';
	const HL_SPAN = 'HL_SPAN';
	const HL_UNDERLINE = 'HL_UNDERLINE';

	/**
	 * @var array
	 */
	private $tokens = [];

	/**
	 * @var array
	 */
	private $minHighlightTokenLength = 4;

	/**
	 * @var array
	 */
	private $highlightType = 4;

	/**
	 * @var string
	 */
	private $outputFormat;

	/**
	 * @since 2.5
	 *
	 * @param array $tokens
	 */
	public function __construct( array $tokens =  [] ) {
		$this->tokens = $tokens;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getTokens() {
		return $this->tokens;
	}

	/**
	 * @since 2.5
	 *
	 * @param Description $description
	 */
	public function addFromDesciption( Description $description ) {

		if ( $description instanceof Conjunction ) {
			foreach ( $description->getDescriptions() as $desc ) {
				return $this->addFromDesciption( $desc );
			}
		}

		if ( $description instanceof SomeProperty ) {
			return $this->addFromDesciption( $description->getDescription() );
		}

		if ( !$description instanceof ValueDescription ) {
			return;
		}

		$isProximate = $description->getComparator() === SMW_CMP_LIKE || $description->getComparator() === SMW_CMP_PRIM_LIKE;

		// [[SomeProperty::~*Foo*]] / [[SomeProperty::like:*Foo*]]
		if ( $isProximate && $description->getDataItem() instanceof DIBlob ) {
			return $this->addTokensFromText( $description->getDataItem()->getString() );
		}

		// [[~~* ... *]]
		if ( $description->getDataItem() instanceof DIWikiPage && strpos( $description->getDataItem()->getDBKey(), '~' ) !== false ) {
			return $this->addTokensFromText( $description->getDataItem()->getDBKey() );
		}
	}

	/**
	 * Sets format information (|?Foo#-hl) from a result printer
	 *
	 * @since 2.5
	 *
	 * @param string $outputFormat
	 */
	public function setOutputFormat( $outputFormat ) {
		$this->outputFormat = $outputFormat;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 * @param type $text
	 *
	 * @return string
	 */
	public function highlight( $text, $type = self::HL_BOLD ) {

		if ( $this->tokens === [] || strpos( strtolower( $this->outputFormat ), '-hl' ) === false ) {
			return $text;
		}

		return $this->doHighlight( $text, $type, array_keys( $this->tokens ) );
	}

	private function doHighlight( $text, $type, $tokens ) {

		if ( $type === self::HL_BOLD ) {
			$replacement = "<b>$0</b>";
		} elseif ( $type === self::HL_UNDERLINE ) {
			$replacement = "<u>$0</u>";
		} elseif ( $type === self::HL_SPAN ) {
			$replacement = "<span class='smw-query-token'>$0</span>";
		} else {
			$replacement = "'''$0'''";
		}

		// Match all tokens except those within [ ... ] to avoid breaking links
		// and annotations
		$pattern = '/(' . implode( '|', $tokens ) . ')+(?![^\[]*\])/iu';

		return preg_replace( $pattern, $replacement, $text );
	}

	private function addTokensFromText( $text ) {

		// Remove query related chars
		$text = str_replace(
			[ '*', '"', '~', '_', '+', '-' ],
			[ '',  '',  '',  ' ', '', '' ],
			$text
		);

		return $this->tokens += array_flip( Tokenizer::tokenize( $text ) );
	}

}
