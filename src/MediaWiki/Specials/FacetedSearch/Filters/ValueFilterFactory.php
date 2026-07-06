<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Filters;

use MediaWiki\Html\TemplateParser;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxRangeGroupValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\ListValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\RangeValueFilter;
use SMW\Schema\CompartmentIterator;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ValueFilterFactory {

	/**
	 * @since 3.2
	 */
	public function __construct( private readonly TemplateParser $templateParser ) {
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return ListValueFilter
	 */
	public function newListValueFilter( array $params ): ListValueFilter {
		return new ListValueFilter( $this->templateParser, $params );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 *
	 * @return CheckboxValueFilter
	 */
	public function newCheckboxValueFilter( array $params ): CheckboxValueFilter {
		return new CheckboxValueFilter( $this->templateParser, $params );
	}

	/**
	 * @since 3.2
	 *
	 * @param CompartmentIterator $compartmentIterator
	 * @param array $params
	 *
	 * @return CheckboxRangeGroupValueFilter
	 */
	public function newCheckboxRangeGroupValueFilter( CompartmentIterator $compartmentIterator, array $params ): CheckboxRangeGroupValueFilter {
		return new CheckboxRangeGroupValueFilter( $this->templateParser, $compartmentIterator, $params );
	}

	/**
	 * @since 3.2
	 *
	 * @param CompartmentIterator $compartmentIterator
	 * @param array $params
	 *
	 * @return RangeValueFilter
	 */
	public function newRangeValueFilter( CompartmentIterator $compartmentIterator, array $params ): RangeValueFilter {
		return new RangeValueFilter( $this->templateParser, $compartmentIterator, $params );
	}

}
