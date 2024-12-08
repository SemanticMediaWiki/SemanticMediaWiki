<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats\SimpleResult;

use SMW\DIWikiPage;

/**
 * Data from a single subject (page or subobject)
 *
 * @since 3.2
 */
class Subject {

	private $wikiPage;
	private $propertyValueCollections;

	/**
	 * @param DIWikiPage $wikiPage
	 * @param PropertyValueCollection[] $propertyValueCollections
	 */
	public function __construct( DIWikiPage $wikiPage, array $propertyValueCollections ) {
		$this->wikiPage = $wikiPage;
		$this->propertyValueCollections = new PropertyValueCollections( $propertyValueCollections );
	}

	public function getWikiPage(): DIWikiPage {
		return $this->wikiPage;
	}

	/**
	 * @return PropertyValueCollection[]
	 */
	public function getPropertyValueCollections(): PropertyValueCollections {
		return $this->propertyValueCollections;
	}

}
