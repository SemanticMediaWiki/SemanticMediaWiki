<?php

namespace SMW;

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
	private $languageDependentPropertyLabels = array();

	/**
	 * Array with entries "property label" => "property id"
	 *
	 * @var string[]
	 */
	private $canonicalPropertyLabels = array();

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param array $languageDependentPropertyLabels
	 * @param array $canonicalPropertyLabels
	 */
	public function __construct( Store $store, array $languageDependentPropertyLabels, array $canonicalPropertyLabels = array() ) {
		$this->store = $store;
		$this->languageDependentPropertyLabels = $languageDependentPropertyLabels;
		$this->canonicalPropertyLabels = $canonicalPropertyLabels;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getKownPredefinedPropertyLabels() {
		return $this->languageDependentPropertyLabels;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|boolean
	 */
	public function findCanonicalPropertyLabelById( $id ) {

		// This is fixed otherwise the `_txt` assignment interferes with the label
		// declaration
		if ( $id === '_TEXT' ) {
			return 'Text';
		}

		return array_search( $id, $this->canonicalPropertyLabels );
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

		if ( array_key_exists( $id, $this->languageDependentPropertyLabels ) ) {
			return $this->languageDependentPropertyLabels[$id];
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
		return array_search( $label, $this->languageDependentPropertyLabels );
	}

	/**
	 * @since 2.2
	 *
	 * @param string $id
	 * @param string $label
	 */
	public function registerPropertyLabel( $id, $label, $asCanonical = true ) {
		$this->languageDependentPropertyLabels[$id] = $label;

		// This is done so extensions can register the property id/label as being
		// canonical in their representation while the alias may hold translated
		// language depedendant matches
		if ( $asCanonical ) {
			$this->canonicalPropertyLabels[$label] = $id;
		}
	}

}
