<?php

namespace SMW\Utils;

use MediaWiki\Html\Html;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlTabs {

	private array $tabs = [];

	private array $contents = [];

	private array $hidden = [];

	private ?string $activeTab = null;

	private string $group = 'tabs';

	private bool $isRTL = false;

	private bool $isSubTab = false;

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function hasContents(): bool {
		return $this->contents !== [];
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isRTL
	 */
	public function isRTL( bool $isRTL ): void {
		$this->isRTL = $isRTL;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $activeTab
	 */
	public function setActiveTab( string $activeTab ): void {
		$this->activeTab = $activeTab;
	}

	/**
	 * The MW Parser has issues with <section> that appear as part of a sub level
	 * which requires to have the content loaded via JS to be able to access the
	 * tab content.
	 *
	 * @since 3.1
	 *
	 * @param bool $isSubTab
	 */
	public function isSubTab( bool $isSubTab = true ): void {
		$this->isSubTab = $isSubTab;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $group
	 */
	public function setGroup( string $group ): void {
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
			$attributes['data-mw-subtab'] = json_encode( $tabs );
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
	 * @param string $html
	 * @param array $params
	 *
	 * @return void
	 */
	public function html( string $html, array $params = [] ): void {
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
	 */
	public function tab( string $id, string $name = '', array $params = [] ): void {
		if ( isset( $params['hide'] ) && $params['hide'] ) {
			$this->hidden[$id] = true;
			return;
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
	public function content( string $id, string $content ): void {
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

	/**
	 * @param string $class
	 * @param array $attr
	 * @return mixed[]
	 */
	private function mergeAttributes( string $class, array $attr ): array {
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

		$attributes += $attr;
		return $attributes;
	}

}
