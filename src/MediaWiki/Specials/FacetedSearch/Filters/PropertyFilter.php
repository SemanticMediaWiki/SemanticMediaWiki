<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Filters;

use MediaWiki\Html\TemplateParser;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class PropertyFilter {

	use MessageLocalizerTrait;

	/**
	 * @var TemplateParser
	 */
	private $templateParser;

	/**
	 * @var TreeBuilder
	 */
	private $treeBuilder;

	/**
	 * @var
	 */
	private $params;

	/**
	 * @var UrlArgs
	 */
	private $urlArgs;

	/**
	 * @since 3.2
	 *
	 * @param TemplateParser $templateParser
	 * @param TreeBuilder $treeBuilder
	 * @param array $params
	 */
	public function __construct( TemplateParser $templateParser, TreeBuilder $treeBuilder, array $params ) {
		$this->templateParser = $templateParser;
		$this->treeBuilder = $treeBuilder;
		$this->params = $params;
	}

	/**
	 * @since 3.2
	 *
	 * @param UrlArgs $urlArgs
	 * @param array $propertyFilters
	 *
	 * @return string
	 */
	public function create( UrlArgs $urlArgs, array $propertyFilters ): string {
		$this->urlArgs = $urlArgs;

		$properties = [];
		$filters = [];

		$list = [
			'unlinked' => [],
			'linked' => []
		];

		foreach ( $propertyFilters as $key => $count ) {
			$key = DIProperty::newFromUserLabel( $key )->getLabel();
			$filters[$key] = $count;
		}

		ksort( $filters );

		if ( $this->urlArgs->getArray( 'pv' ) !== [] ) {
			$props = array_keys( $this->urlArgs->getArray( 'pv' ) );

			foreach ( $props as $p ) {
				if ( !isset( $filters[$p] ) ) {
					$filters[$p] = 1;
				}
			}
		}

		foreach ( $filters as $key => $count ) {
			$properties[] = $this->matchFilter( $key, $count, $list );
		}

		$option = $this->templateParser->processTemplate(
			'items.option',
			[
				'input' => $this->createInputField( $propertyFilters ),
				'condition' => ''
			]
		);

		if ( $list['unlinked'] === [] && $list['linked'] === [] ) {
			$linked = $this->msg( 'smw_result_noresults' );
			$unlinked = '';
			$cssClass = 'no-result';
		} elseif ( $this->params['hierarchy_tree'] ) {
			$this->treeBuilder->setNodes( $list['unlinked'] + $list['linked'] );
			$this->treeBuilder->buildFrom( $properties, TreeBuilder::TYPE_PROPERTY );
			$linked = $this->treeBuilder->getTree();
			$cssClass = 'tree';
			$unlinked = '';
		} else {
			$unlinked = '<ul>' . implode( '', $list['unlinked'] ) . '</ul>';
			$linked = '<ul>' . implode( '', $list['linked'] ) . '</ul>';
			$cssClass = '';
		}

		return $this->templateParser->processTemplate(
			'items',
			[
				'option' => $option,
				'unlinked' => $unlinked,
				'linked' => $linked,
				'css-class' => $cssClass
			]
		);
	}

	private function matchFilter( $key, $count, &$list ) {
		$property = DIWikiPage::newFromText( $key, SMW_NS_PROPERTY );
		$propertyFilters = $this->urlArgs->getArray( 'pv' );

		$clear = $this->urlArgs->find( 'clear.p' );

		if ( isset( $propertyFilters[$key] ) && $clear !== $key ) {
			unset( $propertyFilters[$key] );

			$list['unlinked'][$key] = $this->templateParser->processTemplate(
				'item.unlink.button',
				[
					'label' => $key,
					'count' => $count,
					'name' => 'clear[p]',
					'value' => $key,
					'hidden-name' => "pv[$key][]",
					'hidden-value' => ''
				]
			);
		} else {
			$list['linked'][$key] = $this->templateParser->processTemplate(
				'item.linked.button',
				[
					'name' => "pv[$key][]",
					'value' => '',
					'label' => $key,
					'count' => $count
				]
			);
		}

		return $property;
	}

	private function createInputField( array $values ) {
		if ( count( $values ) <= $this->params['min_item'] ) {
			return '';
		}

		return $this->templateParser->processTemplate(
			'items.input',
			[
				'placeholder' => $this->msg( [ 'smw-facetedsearch-input-filter-placeholder' ] ),
			]
		);
	}

}
