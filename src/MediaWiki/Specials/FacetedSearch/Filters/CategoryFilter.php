<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Filters;

use SMW\DIWikiPage;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class CategoryFilter {

	use MessageLocalizerTrait;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var TreeBuilder
	 */
	private $treeBuilder;

	/**
	 * @var
	 */
	private $params;

	/**
	 * @since 3.2
	 *
	 * @param TemplateEngine $templateEngine
	 * @param TreeBuilder $treeBuilder
	 * @param array $params
	 */
	public function __construct( TemplateEngine $templateEngine, TreeBuilder $treeBuilder, array $params ) {
		$this->templateEngine = $templateEngine;
		$this->treeBuilder = $treeBuilder;
		$this->params = $params;
	}

	/**
	 * @since 3.2
	 *
	 * @param UrlArgs $urlArgs
	 * @param array $filters
	 *
	 * @return string
	 */
	public function create( UrlArgs $urlArgs, array $filters ): string {
		$categories = [];

		$list = [
			'unlinked' => [],
			'linked' => []
		];

		ksort( $filters );

		// Any leftovers from the selection?
		foreach ( $urlArgs->getArray( 'c' ) as $category ) {

			if ( $category === '' ) {
				continue;
			}

			$filters += [ str_replace( ' ', '_', $category ) => true ];
		}

		$categoryFilters = array_flip( $urlArgs->getArray( 'c' ) );
		$clear = $urlArgs->find( 'clear.c' );

		foreach ( $filters as $key => $count ) {
			$categories[] = $this->matchFilter( $categoryFilters, $key, $count, $list, $clear );
		}

		$this->templateEngine->compile(
			'filter-items-option',
			[
				'input' => $this->createInputField( $filters ),
				'condition' => ''
			]
		);

		if ( $list['unlinked'] === [] && $list['linked'] === [] ) {
			$linked = $this->msg( 'smw_result_noresults' );
			$unlinked = '';
			$cssClass = 'no-result';
		} elseif ( $this->params['hierarchy_tree'] ) {
			$this->treeBuilder->setNodes( $list['unlinked'] + $list['linked'] );
			$this->treeBuilder->buildFrom( $categories, TreeBuilder::TYPE_CATEGORY );
			$linked = $this->treeBuilder->getTree();
			$cssClass = 'tree';
			$unlinked = '';
		} else {
			$unlinked = '<ul>' . implode( '', $list['unlinked'] ) . '</ul>';
			$linked = '<ul>' . implode( '', $list['linked'] ) . '</ul>';
			$cssClass = '';
		}

		$this->templateEngine->compile(
			'filter-items',
			[
				'option' => $this->templateEngine->publish( 'filter-items-option' ),
				'unlinked' => $unlinked,
				'linked' => $linked,
				'css-class' => $cssClass
			]
		);

		return $this->templateEngine->publish( 'filter-items' );
	}

	private function matchFilter( $categoryFilters, $key, $count, &$list, $clear ) {
		$category = DIWikiPage::newFromText( $key, NS_CATEGORY );
		$key = str_replace( '_', ' ', $key );

		if ( isset( $categoryFilters[$key] ) && $clear !== $key ) {
			unset( $categoryFilters[$key] );

			$this->templateEngine->compile(
				'filter-item-unlink-button',
				[
					'label' => $key,
					'count' => $count,
					'name' => 'clear[c]',
					'value' => $key,
					'hidden-name' => 'c[]',
					'hidden-value' => $key
				]
			);

			$list['unlinked'][$key] = $this->templateEngine->publish( 'filter-item-unlink-button' );
		} else {
			$this->templateEngine->compile(
				'filter-item-linked-button',
				[
					'name' => "c[]",
					'value' => $key,
					'label' => $key,
					'count' => $count
				]
			);

			$list['linked'][$key] = $this->templateEngine->publish( 'filter-item-linked-button' );
		}

		return $category;
	}

	private function createInputField( array $values ) {
		if ( count( $values ) <= $this->params['min_item'] ) {
			return '';
		}

		$this->templateEngine->compile(
			'filter-items-input',
			[
				'placeholder' => $this->msg( [ 'smw-facetedsearch-input-filter-placeholder' ] ),
			]
		);

		return $this->templateEngine->publish( 'filter-items-input' );
	}

}
