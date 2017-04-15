<?php

namespace SMW\Utils;

use Html;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlTable {

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function table( $html = '', array $attributes = array() ) {
		return self::open( $attributes ) . $html . self::close();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function open( array $attributes = array() ) {
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
	public static function header( $html = '', array $attributes = array() ) {
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
	public static function body( $html = '', array $attributes = array() ) {
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
	public static function footer( $html = '', array $attributes = array() ) {
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
	public static function row( $html = '', array $attributes = array() ) {
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
	public static function cell( $html = '', array $attributes = array() ) {
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

	private static function mergeAttributes( $class, $attributes ) {

		if ( isset( $attributes['class'] ) ) {
			$class .= ' ' . $attributes['class'];
			unset( $attributes['class'] );
		}

		$attributes['class'] = $class;

		return $attributes;
	}

}
