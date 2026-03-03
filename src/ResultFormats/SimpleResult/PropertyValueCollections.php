<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats\SimpleResult;

use Traversable;

/**
 * @since 3.2
 */
class PropertyValueCollections implements \IteratorAggregate {

	private $propertyValueCollections;

	/**
	 * @param PropertyValueCollection[] $propertyValueCollections
	 */
	public function __construct( array $propertyValueCollections ) {
		$this->propertyValueCollections = $propertyValueCollections;
	}

	/**
	 * @return PropertyValueCollection[]
	 */
	public function toArray(): array {
		return $this->propertyValueCollections;
	}

	public function getIterator(): Traversable {
		return new \ArrayIterator( $this->propertyValueCollections );
	}

}
