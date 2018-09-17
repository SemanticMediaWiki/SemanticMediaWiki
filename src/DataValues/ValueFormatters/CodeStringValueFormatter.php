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
	 * @see StringValueFormatter::doFormat
	 */
	protected function doFormat( $dataValue, $type, $linker ) {

		$abbreviate = $type === self::WIKI_LONG || $type === self::HTML_LONG;
		$text = $dataValue->getDataItem()->getString();

		// Escape and wrap values of type Code. The result is escaped to be
		// HTML-safe (it will also work in wiki context). The result will
		// contain mark-up that must not be escaped again.

		Outputs::requireResource( 'ext.smw.style' );

		if ( $this->isJson( $text ) ) {
			$result = self::asJson( $text );
		} else {
			// This disables all active wiki and HTML markup:
			$result = str_replace(
				[ '<code>', '</code>', '<nowiki>', '</nowiki>', '<', '>', ' ', '[', '{', '=', "'", ':', "\n", '&#x005B;' ],
				[ '', '', '', '', '&lt;', '&gt;', '&#160;', '&#91;', '&#x007B;', '&#x003D;', '&#x0027;', '&#58;', "<br />", '&#91;' ],
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
	public static function asJson( $string, $flag = 0 ) {

		if ( $flag > 0 ) {
			return json_encode( json_decode( $string ), $flag );
		}

		return json_encode( json_decode( $string ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
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
