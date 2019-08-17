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
	 * @var string|null
	 */
	private $activeTab = null;

	/**
	 * @var string
	 */
	private $group = 'tabs';

	/**
	 * @var boolean
	 */
	private $isRTL = false;

	/**
	 * @var boolean
	 */
	private $isSubTab = false;

	/**
	 * @since 3.0
	 *
	 * @param boolean $isRTL
	 */
	public function isRTL( $isRTL ) {
		$this->isRTL = (bool)$isRTL;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $activeTab
	 */
	public function setActiveTab( $activeTab ) {
		$this->activeTab = $activeTab;
	}

	/**
	 * The MW Parser has issues with <section> that appear as part of a sub level
	 * which requires to have the content loaded via JS to be able to access the
	 * tab content.
	 *
	 * @since 3.1
	 *
	 * @param boolean $isSubTab
	 */
	public function isSubTab( $isSubTab = true ) {
		$this->isSubTab = $isSubTab;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $group
	 */
	public function setGroup( $group ) {
		$this->group = $group;
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
		$class = 'smw-tabs';

		if ( $this->isSubTab ) {
			$class .= ' smw-subtab';
		}

		$attributes = $this->mergeAttributes( $class, $attributes );
		$tabs = implode( '', $tabs );

		// Attach the tab definition as `data` element so it can be loaded using
		// JS
		if ( $this->isSubTab ) {
			$attributes['data-subtab'] = json_encode( $tabs );
			$tabs = '';
		}

		if ( $this->isRTL ) {
			$attributes['dir'] = 'rtl';
		}

		return Html::rawElement(
			'div',
			$attributes,
			$tabs . implode( '', $contents )
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
	public function html( $html, array $params = [] ) {

		if ( isset( $params['hide'] ) && $params['hide'] ) {
			return;
		}

		$this->tabs[] = $html;
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

		// No active tab means, select the first tab being added
		if ( $this->activeTab === null ) {
			$this->activeTab = $id;
		}

		if ( $id === $this->activeTab ) {
			$isChecked = true;
		}

		$this->tabs[] = Html::rawElement(
			'input',
			[
				'id'    => "tab-$id",
				'class' => 'nav-tab',
				'type'  => 'radio',
				'name'  => $this->group
			] + ( $isChecked ? [ 'checked' => 'checked' ] : [] )
		) . Html::rawElement(
			'label',
			[
				'id'  => "tab-label-$id",
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
			$this->isSubTab ? 'div' : 'section',
			[
				'id' => "tab-content-$id",
			] + (
				$this->isSubTab ? [ 'class' => 'subtab-content' ] : []
			),
			$content
		);
	}

	private function mergeAttributes( $class, $attr ) {

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
