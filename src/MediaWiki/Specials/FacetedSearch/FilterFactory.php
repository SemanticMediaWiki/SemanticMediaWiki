<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\CategoryFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\PropertyFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory;
use SMW\Schema\SchemaFactory;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class FilterFactory {

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly TemplateParser $templateParser,
		private readonly TreeBuilder $treeBuilder,
		private readonly SchemaFactory $schemaFactory,
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return PropertyFilter
	 */
	public function newPropertyFilter( array $params ): PropertyFilter {
		return new PropertyFilter( $this->templateParser, $this->treeBuilder, $params );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return CategoryFilter
	 */
	public function newCategoryFilter( array $params ): CategoryFilter {
		return new CategoryFilter( $this->templateParser, $this->treeBuilder, $params );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return ValueFilter
	 */
	public function newValueFilter( array $params ): ValueFilter {
		$valueFilterFactory = new ValueFilterFactory(
			$this->templateParser
		);

		$valueFilter = new ValueFilter(
			$this->templateParser,
			$valueFilterFactory,
			$this->schemaFactory->newSchemaFinder(),
			$params
		);

		return $valueFilter;
	}

}
