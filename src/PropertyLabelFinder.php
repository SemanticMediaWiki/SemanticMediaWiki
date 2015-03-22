<?php

namespace SMW;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class PropertyLabelFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * Array with entries "property id" => "property label"
	 *
	 * @var string[]
	 */
	private $languageIndependentPropertyLabels = array();

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param array $languageIndependentPropertyLabels
	 */
	public function __construct( Store $store, array $languageIndependentPropertyLabels ) {
		$this->store = $store;

		foreach ( $languageIndependentPropertyLabels as $id => $label ) {
			$this->registerPropertyLabel( $id, $label );
		}
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getKownPredefinedPropertyLabels() {
		return $this->languageIndependentPropertyLabels;
	}

	/**
	 * @note An empty string is returned for incomplete translation (language
	 * bug) or deliberately invisible property
	 *
	 * @since 2.2
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function findPropertyLabelById( $id ) {

		if ( array_key_exists( $id, $this->languageIndependentPropertyLabels ) ) {
			return $this->languageIndependentPropertyLabels[$id];
		}

		return '';
	}

	/**
	 * @since 2.2
	 *
	 * @param string $label
	 *
	 * @return string|false
	 */
	public function searchPropertyIdByLabel( $label ) {
		return array_search( $label, $this->languageIndependentPropertyLabels );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $id
	 * @param string $label
	 */
	public function registerPropertyLabel( $id, $label ) {
		$this->languageIndependentPropertyLabels[$id] = $label;
	}

}
