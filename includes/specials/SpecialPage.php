<?php

namespace SMW;

/**
 * Semantic MediaWiki SpecialPage base class
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Semantic MediaWiki SpecialPage base class
 *
 * @ingroup SpecialPage
 * @codeCoverageIgnore
 */
class SpecialPage extends \SpecialPage {

	/** @var Store */
	protected $store = null;

	/** @var Settings */
	protected $settings = null;

	/**
	 * @see SpecialPage::__construct
	 *
	 * @since 1.9
	 *
	 * @param $name
	 * @param $restriction
	 */
	public function __construct( $name = '', $restriction = '' ) {
		parent::__construct( $name, $restriction );
		$this->store = StoreFactory::getStore();
	}

	/**
	 * Sets store instance
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
		return $this;
	}

	/**
	 * Returns store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * Sets Settings object
	 *
	 * @since 1.9
	 *
	 * @param Settings $settings
	 */
	public function setSettings( Settings $settings ) {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * Returns Settings object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getSettings() {

		if ( $this->settings === null ) {
			$this->settings = Settings::newFromGlobals();
		}

		return $this->settings;
	}

}
