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

		// This disables all active wiki and HTML markup:
		$result = str_replace(
			array( '<', '>', ' ', '[', '{', '=', "'", ':', "\n" ),
			array( '&lt;', '&gt;', '&#160;', '&#x005B;', '&#x007B;', '&#x003D;', '&#x0027;', '&#58;', "<br />" ),
			$text );

		if ( $abbreviate ) {
			$result = "<div style=\"height:5em; overflow:auto;\">$result</div>";
		}

		return "<div class=\"smwpre\">$result</div>";
	}

}
