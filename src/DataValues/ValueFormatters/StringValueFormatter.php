<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMW\Highlighter;
use SMWDataValue as DataValue;
use SMWStringValue as StringValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class StringValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof StringValue;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof StringValue ) {
			throw new RuntimeException( "The formatter is missing a valid StringValue object" );
		}

		if ( $type === self::VALUE ) {
			return $this->dataValue->isValid() ? $this->dataValue->getDataItem()->getString() : 'error';
		}

		if ( $this->dataValue->getCaption() !== false && $type === self::WIKI_SHORT ) {
			return $this->dataValue->getCaption();
		}

		if ( $this->dataValue->getCaption() !== false && $type === self::HTML_SHORT ) {
			return smwfXMLContentEncode( $this->dataValue->getCaption() );
		}

		if ( !$this->dataValue->isValid() ) {
			return '';
		}

		return $this->doFormatFinalOutputFor( $type, $linker );
	}

	protected function doFormatFinalOutputFor( $type, $linker ) {

		// Make a possibly shortened printout string for displaying the value.
		// The result is only escaped to be HTML-safe if this is requested
		// explicitly. The result will contain mark-up that must not be escaped
		// again.
		$abbreviate = $type === self::WIKI_LONG || $type === self::HTML_LONG;
		$text = $this->dataValue->getDataItem()->getString();

		// Appease the MW parser to correctly apply formatting on the
		// first indent
		if ( $text !== '' && ( $text{0} === '*' || $text{0} === '#' || $text{0} === ':' ) ) {
			$text = "\n" . $text . "\n";
		}

		if ( $type === self::HTML_SHORT || $type === self::HTML_LONG ) {
			$text = smwfXMLContentEncode( $text );
		}

		$length = mb_strlen( $text );

		return $abbreviate && $length > 255 ? $this->getAbbreviatedText( $text, $length, $linker ) : $text;
	}

	private function getAbbreviatedText( $text, $length, $linker ) {

		if ( $linker === false || $linker === null ) {
			$ellipsis = ' <span class="smwwarning">…</span> ';
		} else {
			$highlighter = Highlighter::factory( Highlighter::TYPE_TEXT );
			$highlighter->setContent( array (
				'caption' => ' … ',
				'content' => $text
			) );

			$ellipsis = $highlighter->getHtml();
		}

		return mb_substr( $text, 0, 42 ) . $ellipsis . mb_substr( $text, $length - 42 );
	}

}
