<?php

namespace SMW\Utils;

use Html;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlVTabs {

	/**
	 * Identifies which link/content to be active
	 */
	const IS_ACTIVE = 'active';

	/**
	 * Match an active status against a id
	 */
	const FIND_ACTIVE_LINK = 'find';

	/**
	 * Hide content
	 */
	const IS_HIDDEN = 'hidden';

	/**
	 * @var string
	 */
	private static $active = '';

	/**
	 * @var string
	 */
	private static $direction = 'right';

	/**
	 * @since 3.0
	 */
	public static function init() {
		self::$active = '';
		self::$direction = 'right';
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public static function getModules() {
		return [ 'ext.smw.vtabs' ];
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public static function getModuleStyles() {
		return [ 'ext.smw.vtabs.styles' ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $direction
	 */
	public static function setDirection( $direction ) {
		self::$direction = $direction;
	}

	/**
	 * Encapsulate generate tab links into a navigation container.
	 *
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function nav( $html = '', array $attributes = [] ) {

		$direction = self::$direction === 'right' ? 'nav-right' : 'nav-left';

		$attributes = self::mergeAttributes( "smw-vtab-nav", $attributes );
		$attributes['class'] .= " $direction";

		return Html::rawElement(
			'div',
			$attributes,
			$html
		);
	}

	/**
	 * Generate an individual tab link.
	 *
	 * @since 3.0
	 *
	 * @param string $id
	 * @param string $label
	 * @param string|array $flag
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function navLink( $id, $label = '', $flag = false, array $attributes = [] ) {

		if ( $flag === self::IS_HIDDEN ) {
			return '';
		}

		// Match an active status against an id
		if ( is_array( $flag ) && isset( $flag[self::FIND_ACTIVE_LINK] ) && $flag[self::FIND_ACTIVE_LINK] === $id ) {
			$flag = self::IS_ACTIVE;
		}

		$id = 'tab-' . $id;
		$direction = self::$direction === 'right' ? 'nav-right' : 'nav-left';

		$attributes['data-id'] = $id;
		$attributes['id'] = 'vtab-item-' . $id;

		$attributes = self::mergeAttributes( "smw-vtab-link", $attributes );
		$attributes['class'] .= " $direction";

		if ( $flag === self::IS_ACTIVE && self::$active == '' ) {
			$attributes['class'] .= ' active';
			self::$active = $id;
		}

		return Html::rawElement(
			'button',
			$attributes,
			Html::rawElement( 'a', [ 'href' => '#' . $id ], $label )
		);
	}

	/**
	 * Encapsulate the content that relates to a tab link using the ID as identifier
	 * to distinguish content sections.
	 *
	 * @since 3.0
	 *
	 * @param string $id
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function content( $id, $html = '', array $attributes = [] ) {

		$id = 'tab-' . $id;
		$attributes['id'] = $id;

		if ( self::$active !== $id ) {
			if ( !isset( $attributes['style'] ) ) {
				$attributes['style'] = 'display:none;';
			} else {
				$attributes['style'] .= ' display:none;';
			}
		}

		$attributes = self::mergeAttributes( 'smw-vtab-content', $attributes );

		return Html::rawElement(
			'div',
			$attributes,
			$html
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
