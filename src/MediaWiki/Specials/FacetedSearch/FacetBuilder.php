<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\Localizer\MessageLocalizerTrait;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;
use Title;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class FacetBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var FilterFactory
	 */
	private $filterFactory;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var ResultFetcher
	 */
	private $resultFetcher;

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 * @param TemplateEngine $templateEngine
	 * @param FilterFactory $filterFactory
	 * @param ResultFetcher $resultFetcher
	 */
	public function __construct( Profile $profile, TemplateEngine $templateEngine, FilterFactory $filterFactory, ResultFetcher $resultFetcher ) {
		$this->profile = $profile;
		$this->templateEngine = $templateEngine;
		$this->filterFactory = $filterFactory;
		$this->resultFetcher = $resultFetcher;
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param UrlArgs $urlArgs
	 *
	 * @return string
	 */
	public function getPropertyFilterFacet( Title $title, UrlArgs $urlArgs ): string {
		$params = [
			'min_item' => $this->profile->get( 'filters.property_filter.filter_input.min_item' ),
			'hierarchy_tree' => $this->profile->get( 'filters.property_filter.hierarchy_tree', false )
		];

		$propertyFilter = $this->filterFactory->newPropertyFilter( $params );

		$html = $propertyFilter->create(
			$urlArgs,
			$this->resultFetcher->getPropertyFilters()
		);

		// If some value filter is active then close the property filter by
		// default if the users hasn't set any other preference
		$cardState = $urlArgs->getArray( 'cstate' );
		$collapsed = isset( $cardState['card-prop'] ) && $cardState['card-prop'] === 'c';

		if ( $collapsed ) {
			$cssClass = 'mw-collapsed property-filter';
		} elseif ( $urlArgs->getArray( 'pv' ) !== [] && $collapsed ) {
			$cssClass = 'mw-collapsed property-filter';
		} else {
			$cssClass = 'property-filter';
		}

		$pv = $urlArgs->getArray( 'pv' );
		$clear = '';

		if ( is_array( $pv ) && $pv !== [] ) {
			$clear = $this->createClearFilter( 'clear[p.all]', count( $pv ) );
		}

		$this->templateEngine->compile(
			'filter-facet',
			[
				'id' => 'card-prop',
				'label' => $this->msg( 'properties' ),
				'filterfacet' => $html,
				'class' => $cssClass,
				'clear' => $clear
			]
		);

		return $this->templateEngine->publish( 'filter-facet' );
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param UrlArgs $urlArgs
	 *
	 * @return string
	 */
	public function getCategoryFilterFacet( Title $title, UrlArgs $urlArgs ): string {
		$params = [
			'min_item' => $this->profile->get( 'filters.category_filter.filter_input.min_item', 10 ),
			'hierarchy_tree' => $this->profile->get( 'filters.category_filter.hierarchy_tree', false )
		];

		$categoryFilter = $this->filterFactory->newCategoryFilter( $params );

		$html = $categoryFilter->create(
			$urlArgs,
			$this->resultFetcher->getCategoryFilters()
		);

		// If some value filter is active then close the property filter by
		// default if the users hasn't set any other preference
		$cardState = $urlArgs->getArray( 'cstate' );
		$collapsed = isset( $cardState['card-cat'] ) && $cardState['card-cat'] === 'c';

		if ( $collapsed ) {
			$cssClass = 'mw-collapsed category-filter';
		} elseif ( $urlArgs->getArray( 'pv' ) !== [] && $collapsed ) {
			$cssClass = 'mw-collapsed category-filter';
		} else {
			$cssClass = 'category-filter';
		}

		$args = $urlArgs->clone();
		$c = $args->getArray( 'c' );
		$clear = '';

		if ( is_array( $c ) && $c !== [] ) {
			$clear = $this->createClearFilter( 'clear[c.all]', count( $c ) );
		}

		$this->templateEngine->compile(
			'filter-facet',
			[
				'id' => 'card-cat',
				'label' => $this->msg( 'smw-categories' ),
				'filterfacet' => $html,
				'class' => $cssClass,
				'clear' => $clear
			]
		);

		return $this->templateEngine->publish( 'filter-facet' );
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param UrlArgs $urlArgs
	 *
	 * @return string
	 */
	public function getValueFilterFacets( Title $title, UrlArgs $urlArgs ): string {
		$params = [
			'default_filter' => $this->profile->get( 'filters.value_filter.default_filter' ),
			'min_item' => $this->profile->get( 'filters.value_filter.filter_input.min_item' ),
			'filter_type' => $this->profile->get( 'filters.value_filter.filter_type' ),
			'condition_field' => $this->profile->get( 'filters.value_filter.condition_field', false ),
			'range_group_filter_preference' => $this->profile->get( 'filters.value_filter.filter_type.range_group_filter_preference' )
		];

		$valueFilter = $this->filterFactory->newValueFilter( $params );

		$filterList = $valueFilter->create(
			$urlArgs,
			$this->resultFetcher->getValueFilters()
		);

		$html = '';

		foreach ( $filterList as $property => $filters ) {

			$id = 'v-' . strtolower( str_replace( ' ', '-', $property ) );
			$args = $urlArgs->clone();
			$pv = $args->getArray( 'pv' );

			if ( $property === $urlArgs->find( 'clear.p' ) ) {
				continue;
			}

			$clear = $urlArgs->getArray( 'clear' );

			if (
				isset( $pv[$property] ) &&
				is_array( $pv[$property] ) &&
				array_filter( $pv[$property] ) !== [] &&
				$filters !== [] &&
				!isset( $clear[$property] ) ) {
				$clear = $this->createClearFilter( 'clear[' . $property . ']', count( $pv[$property] ) );
			} else {
				$clear = '';
			}

			// If some value filter is active then close the property filter by
			// default if the users hasn't set any other preference
			$cardState = $urlArgs->getArray( 'cstate' );
			$collapsed = isset( $cardState["card-$id"] ) && $cardState["card-$id"] === 'c';

			if ( $collapsed ) {
				$cssClass = 'mw-collapsed value-filter';
			} else {
				$cssClass = 'value-filter';
			}

			$this->templateEngine->compile(
				'filter-facet',
				[
					'id' => "card-$id",
					'label' => $property,
					'filterfacet' => $filters,
					'class' => $cssClass,
					'clear' => $clear
				]
			);

			$html .= $this->templateEngine->publish( 'filter-facet' );
		}

		return $html;
	}

	private function createClearFilter( $name, $count = 1 ) {
		$this->templateEngine->compile(
			'filter-items-clear-button',
			[
				'name' => $name,
				'value' => '',
				'title' => $this->msg( [ 'smw-facetedsearch-clear-filters', $count ] )
			]
		);

		return $this->templateEngine->publish( 'filter-items-clear-button' );
	}

}
