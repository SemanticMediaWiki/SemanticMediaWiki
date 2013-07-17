<?php

namespace SMW;

/**
 * Semantic MediaWiki SpecialPage Base class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Semantic MediaWiki SpecialPage Base class
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
