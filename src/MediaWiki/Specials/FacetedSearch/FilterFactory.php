<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\CategoryFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\PropertyFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory;
use SMW\Schema\SchemaFactory;
use SMW\Utils\TemplateEngine;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class FilterFactory {

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var TreeBuilder
	 */
	private $treeBuilder;

	/**
	 * @var SchemaFactory
	 */
	private $schemaFactory;

	/**
	 * @since 3.2
	 *
	 * @param TemplateEngine $templateEngine
	 * @param TreeBuilder $treeBuilder
	 * @param SchemaFactory $schemaFactory
	 */
	public function __construct( TemplateEngine $templateEngine, TreeBuilder $treeBuilder, SchemaFactory $schemaFactory ) {
		$this->templateEngine = $templateEngine;
		$this->treeBuilder = $treeBuilder;
		$this->schemaFactory = $schemaFactory;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return PropertyFilter
	 */
	public function newPropertyFilter( array $params ): PropertyFilter {
		return new PropertyFilter( $this->templateEngine, $this->treeBuilder, $params );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return CategoryFilter
	 */
	public function newCategoryFilter( array $params ): CategoryFilter {
		return new CategoryFilter( $this->templateEngine, $this->treeBuilder, $params );
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
			$this->templateEngine
		);

		$valueFilter = new ValueFilter(
			$this->templateEngine,
			$valueFilterFactory,
			$this->schemaFactory->newSchemaFinder(),
			$params
		);

		return $valueFilter;
	}

}
