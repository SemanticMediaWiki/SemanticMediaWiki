<?php

namespace SMW\Utils;

use Html;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlModal {

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public static function getModules() {
		return [ 'ext.smw.modal' ];
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public static function getModuleStyles() {
		return [ 'ext.smw.modal.styles' ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function link( $name, array $attributes = [] ) {

		$attributes = self::mergeAttributes(
			'smw-modal-link is-disabled',
			$attributes
		);

		return Html::rawElement(
			'span',
			$attributes,
			Html::rawElement(
				'a',
				[
					'href' => '#help',
					'rel'  => 'nofollow'
				],
				$name
			)
		);
	}

	/**
	 * Embbeded page links will have issues with href and #help therefore
	 * just provide a simple wrapper for an in-page usage.
	 *
	 * @since 3.1
	 *
	 * @param string $html
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function element( $name, array $attributes = [] ) {

		$attributes = self::mergeAttributes(
			'smw-modal-link is-disabled',
			$attributes
		);

		return Html::rawElement(
			'span',
			$attributes,
			$name
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
	public static function modal( $title = '', $html = '', array $attributes = [] ) {

		$attributes = self::mergeAttributes(
			'smw-modal',
			$attributes
		);

		$title = Html::rawElement(
			'span',
			[
				'class' => 'smw-modal-title'
			],
			$title
		);

		$html = Html::rawElement(
			'div',
			[
				'class' => 'smw-modal-content'
			],
			Html::rawElement(
				'div',
				[
					'class' => 'smw-modal-header'
				],
				Html::rawElement(
					'span',
					[
						'class' => 'smw-modal-close'
					],
					'&#215;'
				) . $title
			). Html::rawElement(
				'div',
				[
					'class' => 'smw-modal-body'
				],
				$html
			) . Html::rawElement(
				'div',
				[
					'class' => 'smw-modal-footer'
				],
				''
			)
		);

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
