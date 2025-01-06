<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Schema\CompartmentIterator;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;
use SMWDataItem as DataItem;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class CheckboxRangeGroupValueFilter {

	use MessageLocalizerTrait;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var CompartmentIterator
	 */
	private $compartmentIterator;

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
	 * @param CompartmentIterator $compartmentIterator
	 * @param array $params
	 */
	public function __construct( TemplateEngine $templateEngine, CompartmentIterator $compartmentIterator, array $params ) {
		$this->templateEngine = $templateEngine;
		$this->compartmentIterator = $compartmentIterator;
		$this->params = $params;
	}

	/**
	 * @since 3.2
	 *
	 * @param UrlArgs $urlArgs
	 * @param string $property
	 * @param array $values
	 *
	 * @return string
	 */
	public function create( UrlArgs $urlArgs, string $property, array $values, array $raw ): string {
		if ( $values === [] ) {
			return '';
		}

		$this->urlArgs = $urlArgs;

		$list = [
			'unlinked' => [],
			'linked' => []
		];

		$ranges = $this->buildRangeGroups(
			$property,
			$values,
			$raw
		);

		$valueFilters = $this->getValueFilters( $property );
		$isClear = $this->urlArgs->find( "clear.$property", false );

		foreach ( $ranges as $range ) {
			$this->matchFilter( $property, $range, $valueFilters, $list, $isClear );
		}

		$this->templateEngine->compile(
			'filter-items-option',
			[
				'input' => $this->createInputField( $property, $ranges ),
				'condition' => $this->createConditionField( $property )
			]
		);

		if ( $list['linked'] === [] && $list['unlinked'] === [] ) {
			$list['linked'] = [ $this->msg( 'smw-facetedsearch-no-filters' ) ];
		}

		$this->templateEngine->compile(
			'filter-items',
			[
				'option' => $this->templateEngine->publish( 'filter-items-option' ),
				'unlinked' => implode( '', $list['unlinked'] ),
				'linked' => implode( '', $list['linked'] ),
				'css-class' => ''
			]
		);

		return $this->templateEngine->publish( 'filter-items' );
	}

	private function matchFilter( $property, $range, $valueFilters, &$list, $isClear ) {
		$key = $range['min'] . '|' . $range['max'];

		if ( $key === '' ) {
			return;
		}

		$attr = [
			'name' => "pv[$property][]",
			'value' => $key,
			'count' => $range['count'],
			'label' => $range['msg'],
			'checked' => ''
		];

		if ( isset( $valueFilters[$key] ) && $isClear === false ) {
			$attr['checked'] = 'checked';

			$this->templateEngine->compile(
				'filter-item-checkbox',
				$attr
			);

			$list['unlinked'][] = $this->templateEngine->publish( 'filter-item-checkbox' );
		} else {
			$this->templateEngine->compile(
				'filter-item-checkbox',
				$attr
			);

			$list['linked'][] = $this->templateEngine->publish( 'filter-item-checkbox' );
		}
	}

	private function getValueFilters( $property ) {
		$valueFilters = $this->urlArgs->getArray( 'pv' );
		$valueFilters = $valueFilters[$property] ?? [];

		return is_array( $valueFilters ) ? array_flip( $valueFilters ) : [];
	}

	private function buildRangeGroups( $property, $values, $raw ) {
		$ranges = [];

		$property = DIProperty::newFromUserLabel( $property );

		$diType = DataTypeRegistry::getInstance()->getDataItemId(
			$property->findPropertyTypeID()
		);

		foreach ( $this->compartmentIterator as $compartment ) {
			$data = $compartment->get( 'range_group' );

			foreach ( $data as $key => $value ) {
				$ranges[] = $this->range( $diType, $property, $key, $value );
			}
		}

		foreach ( $raw as $val => $r ) {

			if ( $diType === DataItem::TYPE_TIME ) {
				$r = $this->getJD( $property, $r );
			} else {
				$r = (int)$r;
			}

			foreach ( $ranges as $k => $range ) {

				if ( $r >= $range['comp_min'] && $r <= $range['comp_max'] ) {
					$ranges[$k]['count']++;
				} elseif ( $range['comp_max'] === 'INF' && $r >= $range['comp_min'] ) {
					$ranges[$k]['count']++;
				} elseif ( $range['comp_min'] === 'INF' && $r <= $range['comp_max'] ) {
					$ranges[$k]['count']++;
				}
			}
		}

		foreach ( $ranges as $range ) {
			$values[$range['msg']] = $range['count'];
		}

		return $ranges;
	}

	private function range( $diType, $property, $key, $value ) {
		[ $min, $max ] = explode( '...', $value );

		$msg = $this->msg( $key );

		if ( strpos( $msg, $key ) !== false ) {
			$msg = $key;
		}

		// Handle {{ ... }} and find replacements, for example:
		// "within last 50 years": "{{-50 years}}...{{CURRENTTIME}}"
		if ( strpos( $min, '{{' ) !== false && strpos( $min, '}}' ) !== false ) {
			preg_match_all( '/{{(.*?)}}/', $min, $matches );

			if ( isset( $matches[1][0] ) && $matches[1][0] === 'CURRENTTIME' ) {
				$min = date( 'Y-m-d' );
			} elseif ( isset( $matches[1][0] ) ) {
				$min = date( 'Y-m-d', strtotime( $matches[1][0] ) );
			}
		}

		if ( strpos( $max, '{{' ) !== false && strpos( $max, '}}' ) !== false ) {
			preg_match_all( '/{{(.*?)}}/', $max, $matches );

			if ( isset( $matches[1][0] ) && $matches[1][0] === 'CURRENTTIME' ) {
				$max = date( 'Y-m-d' );
			} elseif ( isset( $matches[1][0] ) ) {
				$max = date( 'Y-m-d', strtotime( $matches[1][0] ) );
			}
		}

		// Use the JD for comparison to avoid having to deal
		// with BC/JL conversions
		if ( $diType === DataItem::TYPE_TIME && $min !== 'INF' ) {
			$compMin = $this->getJD( $property, $min );
		} else {
			$compMin = $min;
		}

		if ( $diType === DataItem::TYPE_TIME && $max !== 'INF' ) {
			$compMax = $this->getJD( $property, $max );
		} else {
			$compMax = $max;
		}

		return [
			'min' => $min,
			'max' => $max,
			'comp_min' => $compMin,
			'comp_max' => $compMax,
			'msg' => $msg,
			'count' => 0
		];
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

	private function getJD( $property, $value ) {
		return DataValueFactory::getInstance()->newDataValueByProperty( $property, $value )->getDataItem()->getJD();
	}

}
