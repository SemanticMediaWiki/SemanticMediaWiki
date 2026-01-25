<?php

namespace SMW\MediaWiki\Renderer;

use MediaWiki\Html\Html;

class HtmlUtil {

	/**
	 * Convenience function to build an HTML checkbox with a label.
	 * (Cut-n-paste from <https://gerrit.wikimedia.org/r/c/mediawiki/core/+/1196532>)
	 *
	 * @param string $label
	 * @param string $name
	 * @param string $id
	 * @param bool $checked
	 * @param array $attribs
	 * @return string HTML
	 *
	 */
	public static function checkLabel( $label, $name, $id, $checked = false, $attribs = [] ) {
		$labelAttr = array_intersect_key( $attribs, array_flip( [ 'class', 'title' ] ) );
		return Html::check( $name, $checked, [ 'id' => $id ] + $attribs ) .
			"\u{00A0}" .
			Html::label( $label, $id, $labelAttr );
	}

	/**
	 * Shortcut for creating fieldsets.
	 *
	 * @param string|false $legend Legend of the fieldset. If evaluates to false,
	 *   legend is not added.
	 * @param string|false $content Pre-escaped content for the fieldset. If false,
	 *   only open fieldset is returned.
	 * @param array $attribs Any attributes to fieldset-element.
	 * @return string
	 */
	public static function fieldset( $legend = false, $content = false, $attribs = [] ) {
		$s = Html::openElement( 'fieldset', $attribs ) . "\n";

		if ( $legend ) {
			$s .= Html::element( 'legend', null, $legend ) . "\n";
		}

		if ( $content !== false ) {
			$s .= $content . "\n";
			$s .= Html::closeElement( 'fieldset' ) . "\n";
		}

		return $s;
	}
}
