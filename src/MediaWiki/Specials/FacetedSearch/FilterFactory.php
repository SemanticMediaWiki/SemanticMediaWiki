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
	 * @var TemplateParser
	 */
	private $templateParser;

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
	 * @param TemplateParser $templateParser
	 * @param TreeBuilder $treeBuilder
	 * @param SchemaFactory $schemaFactory
	 */
	public function __construct( TemplateParser $templateParser, TreeBuilder $treeBuilder, SchemaFactory $schemaFactory ) {
		$this->templateParser = $templateParser;
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
