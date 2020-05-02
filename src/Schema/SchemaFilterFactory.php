<?php

namespace SMW\Schema;

use SMW\Schema\Filters\NamespaceFilter;
use SMW\Schema\Filters\CategoryFilter;
use SMW\Schema\Filters\PropertyFilter;
use SMW\Schema\Filters\CompositeFilter;
use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaFilterFactory {

	/**
	 * @since 3.2
	 *
	 * @param array $filters
	 *
	 * @return CompositeFilter
	 */
	public function newCompositeFilter( array $filters ) : CompositeFilter {
		return new CompositeFilter( $filters );
	}

	/**
	 * @since 3.2
	 *
	 * @param int|null $namespace
	 *
	 * @return NamespaceFilter
	 */
	public function newNamespaceFilter( ?int $namespace ) : NamespaceFilter {
		return new NamespaceFilter( $namespace );
	}

	/**
	 * @since 3.2
	 *
	 * @param string|array|callable $categories
	 *
	 * @return CategoryFilter
	 */
	public function newCategoryFilter( $categories ) : CategoryFilter {
		return new CategoryFilter( $categories );
	}

	/**
	 * @since 3.2
	 *
	 * @param string|array|callable $properties
	 *
	 * @return PropertyFilter
	 */
	public function newPropertyFilter( $properties ) : PropertyFilter {
		return new PropertyFilter( $properties );
	}

}
