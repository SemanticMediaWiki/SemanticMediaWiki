<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use Html;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class FormatterWidget {

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function div( $html = '', $attributes = array() ) {
		return Html::rawElement( 'div', $attributes, $html );
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $hide
	 *
	 * @return string
	 */
	public static function horizontalRule( $html = '', $attributes = array() ) {
		return Html::rawElement( 'hr', self::mergeAttributes( 'smw-ask-horizontalrule', $attributes ) );
	}

	private static function mergeAttributes( $class, $attr = array() ) {

		$attributes = array();

		// A bit of attribute order
		if ( isset( $attr['id'] ) ) {
			$attributes['id'] = $attr['id'];
		}

		if ( isset( $attr['class'] ) ) {
			$attributes['class'] = $class . ' ' . $attr['class'];
		} else {
			$attributes['class'] = $class;
		}

		return $attributes += $attr;
	}

}
