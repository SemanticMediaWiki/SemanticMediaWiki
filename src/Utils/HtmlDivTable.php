<?php

namespace SMW\Utils;

use Html;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlDivTable {

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function table( $html = '', array $attributes = [] ) {
		return self::open( $attributes ) . $html . self::close();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function open( array $attributes = [] ) {
		return Html::openElement(
			'div',
			self::mergeAttributes( 'smw-table', $attributes ),
			''
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function header( $html = '', array $attributes = [] ) {
		return Html::rawElement(
			'div',
			self::mergeAttributes( 'smw-table-header', $attributes ),
			$html
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function body( $html = '', array $attributes = [] ) {
		return Html::rawElement(
			'div',
			self::mergeAttributes( 'smw-table-body', $attributes ),
			$html
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function footer( $html = '', array $attributes = [] ) {
		return Html::rawElement(
			'div',
			self::mergeAttributes( 'smw-table-footer', $attributes ),
			$html
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function row( $html = '', array $attributes = [] ) {
		return Html::rawElement(
			'div',
			self::mergeAttributes( 'smw-table-row', $attributes ),
			$html
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function cell( $html = '', array $attributes = [] ) {
		return Html::rawElement(
			'div',
			self::mergeAttributes( 'smw-table-cell', $attributes ),
			$html
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function close() {
		return Html::closeElement(
			'div'
		);
	}

	private static function mergeAttributes( $class, $attr ) {

		$attributes = [];

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
