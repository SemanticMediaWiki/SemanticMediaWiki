<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ListValueFilter {

	use MessageLocalizerTrait;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var UrlArgs
	 */
	private $urlArgs;

	/**
	 * @var
	 */
	private $params;

	/**
	 * @since 3.2
	 *
	 * @param TemplateEngine $templateEngine
	 * @param array $params
	 */
	public function __construct( TemplateEngine $templateEngine, array $params ) {
		$this->templateEngine = $templateEngine;
		$this->params = $params;
	}

	/**
	 * @since 3.2
	 *
	 * @param UrlArgs $urlArgs
	 * @param string $property
	 * @param array $values
	 * @param array $raw
	 *
	 * @return string
	 */
	public function create( UrlArgs $urlArgs, string $property, array $values, array $raw ): string {
		if ( $values === [] ) {
			return '';
		}

		$list = [
			'unlinked' => [],
			'linked' => []
		];

		$this->urlArgs = $urlArgs;
		arsort( $values );

		$valueFilters = $this->getValueFilters( $property );
		$clear = $this->urlArgs->getArray( 'clear' );

		$prop = DIProperty::newFromUserLabel( $property );

		$isRecordType = DataTypeRegistry::getInstance()->isRecordType(
			$prop->findPropertyTypeID()
		);

		foreach ( $values as $key => $count ) {

			if ( $key === '' ) {
				continue;
			}

			$label = $key;

			// For the record type, the query requires to use the "raw" value with
			// values being separated by `;` but should be displayed with a
			// formatted label. We need to check here whether it is a record
			// type or not because checking for `;` wouldn't be correct as other
			// values may use `;` as part of the value itself.
			if ( $isRecordType && isset( $raw[$key] ) ) {
				$key = $raw[$key];

				$dv = DataValueFactory::getInstance()->getInstance()->newDataValueByProperty(
					$prop,
					$key
				);

				if ( $dv->isValid() ) {
					$label = $dv->getShortWikiText();
				}
			} elseif ( isset( $raw[$key] ) ) {
				$key = $raw[$key];
			}

			$this->matchFilter( $property, $key, $label, $count, $valueFilters, $clear, $list );
		}

		$list = $this->sortValues( $list );

		if ( $list['linked'] === [] && $list['unlinked'] === [] ) {
			$unlinked = '';
			$linked = $this->msg( 'smw-facetedsearch-no-filters' );
			$cssClass = 'no-result';
			$option = '';
		} else {
			$unlinked = "<ul>" . implode( '', $list['unlinked'] ) . "</ul>";
			$linked = "<ul>" . implode( '', $list['linked'] ) . "</ul>";

			$this->templateEngine->compile(
				'filter-items-option',
				[
					'input' => $this->createInputField( $property, $values ),
					'condition' => $this->createConditionField( $property )
				]
			);

			$option = $this->templateEngine->publish( 'filter-items-option' );
			$cssClass = '';
		}

		$this->templateEngine->compile(
			'filter-items',
			[
				'option' => $option,
				'unlinked' => $unlinked,
				'linked' => $linked,
				'css-class' => $cssClass
			]
		);

		return $this->templateEngine->publish( 'filter-items' );
	}

	private function getValueFilters( $property ) {
		$valueFilters = $this->urlArgs->getArray( 'pv' );
		$valueFilters = $valueFilters[$property] ?? [];

		return is_array( $valueFilters ) ? array_flip( $valueFilters ) : [];
	}

	private function sortValues( $list ) {
		$linked = [];
		$unlinked = [];

		// List is sorted by descending count, ascending key
		foreach ( $list as $c => $l ) {
			foreach ( $l as $k => $v ) {
				if ( $k === 'linked' && $v !== [] ) {
					foreach ( $v as $key => $value ) {
						$linked[] = $value;
					}
				}
				if ( $k === 'unlinked' && $v !== [] ) {
					foreach ( $v as $key => $value ) {
						$unlinked[] = $value;
					}
				}
			}
		}

		return [
			'unlinked' => $unlinked,
			'linked' => $linked
		];
	}

	private function matchFilter( $property, $key, $label, $count, $valueFilters, $clear, &$list ) {
		if ( !isset( $list[$count] ) ) {
			$list[$count] = [ 'linked' => [], 'unlinked' => [] ];
		}

		if ( isset( $clear[$property] ) ) {
			$isClear = false;
		} else {
			$isClear = !isset( $clear['v'] ) || $clear['v'] !== $key;
		}

		if ( isset( $valueFilters[$key] ) && $isClear ) {

			if ( isset( $list[$count]['unlinked'][$key] ) ) {
				return;
			}

			$this->templateEngine->compile(
				'filter-item-unlink-button',
				[
					'label' => $label,
					'count' => $count,
					'name' => 'clear[v]',
					'value' => $key,
					'hidden-name' => "pv[$property][]",
					'hidden-value' => $key
				]
			);

			$list[$count]['unlinked'][$key] = $this->templateEngine->publish( 'filter-item-unlink-button' );
		} else {
			$this->templateEngine->compile(
				'filter-item-linked-button',
				[
					'name' => "pv[$property][]",
					'value' => $key,
					'label' => $label,
					'count' => $count
				]
			);

			$list[$count]['linked'][$key] = $this->templateEngine->publish( 'filter-item-linked-button' );
		}

		// Sort by key ascending
		asort( $list[$count]['unlinked'] );
		asort( $list[$count]['linked'] );
	}

	private function createConditionField( $property ) {
		if ( $this->params['condition_field'] === false ) {
			return '';
		}

		$condition = $this->urlArgs->find( "vc.$property", 'or' );

		$this->templateEngine->compile(
			'filter-items-condition',
			[
				'property' => $property,
				'or-selected' => $condition === 'or' ? 'selected' : '',
				'and-selected' => $condition === 'and' ? 'selected' : '',
				'not-selected' => $condition === 'not' ? 'selected' : ''
			]
		);

		return $this->templateEngine->publish( 'filter-items-condition' );
	}

	private function createInputField( $property, array $values ) {
		if ( count( $values ) <= $this->params['min_item'] ) {
			return '';
		}

		$this->templateEngine->compile(
			'filter-items-input',
			[
				'placeholder' => $this->msg( [ 'smw-facetedsearch-input-filter-placeholder', $property ] ),
			]
		);

		return $this->templateEngine->publish( 'filter-items-input' );
	}

}
