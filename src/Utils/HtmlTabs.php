<?php

namespace SMW\Utils;

use Html;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlTabs {

	/**
	 * @var []
	 */
	private $tabs = [];

	/**
	 * @var []
	 */
	private $contents = [];

	/**
	 * @var []
	 */
	private $hidden = [];

	/**
	 * @var []
	 */
	private $activeTab = '';

	/**
	 * @since 3.0
	 *
	 * @param string $activeTab
	 */
	public function setActiveTab( $activeTab ) {
		$this->activeTab = $activeTab;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function buildHTML( array $attributes = [] ) {

		$tabs = $this->tabs;
		$contents = $this->contents;

		$this->tabs = [];
		$this->contents = [];

		$attributes = $this->mergeAttributes( 'smw-tabs', $attributes );

		return Html::rawElement(
			'div',
			$attributes,
			implode( '', $tabs ) . implode( '', $contents )
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 * @param string $name
	 * @param array $params
	 *
	 * @return string
	 */
	public function tab( $id, $name = '', array $params = [] ) {

		if ( isset( $params['hide'] ) && $params['hide'] ) {
			return $this->hidden[$id] = true;
		}

		$isChecked = false;

		if ( $id === $this->activeTab ) {
			$isChecked = true;
		}

		$this->tabs[] = Html::rawElement(
			'input',
			[
				'id'    => "tab-$id",
				'class' => 'nav-tab',
				'type'  => 'radio',
				'name'  => 'tabs'
			] + ( $isChecked ? [ 'checked' => 'checked' ] : [] )
		) . Html::rawElement(
			'label',
			[
				'for' => "tab-$id"
			] + $this->mergeAttributes( 'nav-label', $params ),
			$name
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 * @param string $content
	 */
	public function content( $id, $content ) {

		// Tab hidden?
		if ( isset( $this->hidden[$id] ) ) {
			return;
		}

		$this->contents[] = Html::rawElement(
			'section',
			[
				'id' => "tab-content-$id"
			],
			$content
		);
	}

	private function mergeAttributes( $class, $attr ) {

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
