<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMW\DataValues\StringValue;
use SMW\Highlighter;
use SMW\Utils\Normalizer;
use SMWDataValue as DataValue;

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
	public function format( $dataValue, $options = null ) {

		if ( !is_array( $options ) ) {
			throw new RuntimeException( "Option is not an array!" );
		}

		// Normally we would do `list( $type, $linker ) = $options;` BUT due to
		// PHP 7.0 ... "The order that the assignment operations are performed in has changed."

		$type = $options[0];
		$linker = isset( $options[1] ) ? $options[1] : null;

		if ( !$dataValue instanceof StringValue ) {
			throw new RuntimeException( "The formatter is missing a valid StringValue object" );
		}

		if ( $type === self::VALUE ) {
			return $dataValue->isValid() ? $dataValue->getDataItem()->getString() : 'error';
		}

		if ( $dataValue->getCaption() !== false && $type === self::WIKI_SHORT ) {
			return $dataValue->getCaption();
		}

		if ( $dataValue->getCaption() !== false && $type === self::HTML_SHORT ) {
			return smwfXMLContentEncode( $dataValue->getCaption() );
		}

		if ( !$dataValue->isValid() ) {
			return $dataValue->getDataItem()->getUserValue();
		}

		return $this->doFormat( $dataValue, $type, $linker );
	}

	protected function doFormat( $dataValue, $type, $linker ) {

		$text = $dataValue->getDataItem()->getString();
		$length = mb_strlen( $text );

		// Make a possibly shortened printout string for displaying the value.
		// The result is only escaped to be HTML-safe if this is requested
		// explicitly. The result will contain mark-up that must not be escaped
		// again.
		$abbreviate = $type === self::WIKI_LONG || $type === self::HTML_LONG;
		$requestedLength = intval( $dataValue->getOutputFormat() );

		// Appease the MW parser to correctly apply formatting on the
		// first indent
		if ( $text !== '' && ( $text[0] === '*' || $text[0] === '#' || $text[0] === ':' ) ) {
			$text = "\n" . $text . "\n";
		}

		if ( $requestedLength > 0 && $requestedLength < $length ) {
			// Reduces the length and finish it with a whole word
			return Normalizer::reduceLengthTo( $text, $requestedLength ) . ' …';
		}

		if ( $type === self::HTML_SHORT || $type === self::HTML_LONG ) {
			$text = smwfXMLContentEncode( $text );
		}

		if ( $abbreviate && $length > 255 ) {
			$text = $this->getAbbreviatedText( $text, $length, $linker );
		}

		return $text;
	}

	private function getAbbreviatedText( $text, $length, $linker ) {

		if ( $linker === false || $linker === null ) {
			$ellipsis = ' <span class="smwwarning">…</span> ';
		} else {
			$highlighter = Highlighter::factory( Highlighter::TYPE_TEXT );
			$highlighter->setContent(  [
				'caption' => ' … ',
				'content' => $text
			] );

			$ellipsis = $highlighter->getHtml();
		}

		$startOff = 42;
		$endOff = 42;

		// Avoid breaking a link (i.e. [[ ... ]])
		if ( ( $pos = stripos ( $text, '[[' ) ) && $pos < 42 ) {
			$startOff = stripos ( $text, ']]' ) + 2;
		}

		if ( ( $pos = strrpos ( $text, ']]' ) ) && $pos > $length - $endOff ) {
			$endOff = $length - strrpos( $text, '[[' );
		}

		return mb_substr( $text, 0, $startOff ) . $ellipsis . mb_substr( $text, $length - $endOff );
	}

}
