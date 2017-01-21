<?php

namespace SMW\DataValues\ValueFormatters;

use SMWDataValue as DataValue;
use SMWOutputs as Outputs;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CodeStringValueFormatter extends StringValueFormatter {

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue->getTypeID() === '_cod';
	}

	/**
	 * @see StringValueFormatter::doFormatFinalOutputFor
	 */
	protected function doFormatFinalOutputFor( $type, $linker ) {

		$abbreviate = $type === self::WIKI_LONG || $type === self::HTML_LONG;
		$text = $this->dataValue->getDataItem()->getString();

		// Escape and wrap values of type Code. The result is escaped to be
		// HTML-safe (it will also work in wiki context). The result will
		// contain mark-up that must not be escaped again.

		Outputs::requireResource( 'ext.smw.style' );

		if ( $this->isJson( $text ) ) {
			$result = self::formatAsPrettyJson( $text );
		} else {
			// This disables all active wiki and HTML markup:
			$result = str_replace(
				array( '<', '>', ' ', '[', '{', '=', "'", ':', "\n" ),
				array( '&lt;', '&gt;', '&#160;', '&#91;', '&#x007B;', '&#x003D;', '&#x0027;', '&#58;', "<br />" ),
				$text
			);
		}

		if ( $abbreviate ) {
			$result = "<div style=\"min-height:5em; overflow:auto;\">$result</div>";
		}

		return "<div class=\"smwpre\">$result</div>";
	}

	/**
	 * @since 2.5
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function formatAsPrettyJson( $string ) {
		return defined( 'JSON_PRETTY_PRINT' ) ? json_encode( json_decode( $string ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : $string;
	}

	private function isJson( $string ) {

		// Don't bother
		if ( substr( $string, 0, 1 ) !== '{' ) {
			return false;
		}

		json_decode( $string );

		return ( json_last_error() == JSON_ERROR_NONE );
	}

}
