<?php

namespace SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use Html;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Schema\CompartmentIterator;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class RangeValueFilter {

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
	 * @param array $raw
	 *
	 * @return string
	 */
	public function create( UrlArgs $urlArgs, string $property, array $values, array $raw ): string {
		if ( $values === [] ) {
			return $this->msg( 'smw-facetedsearch-no-filter-range' );
		}

		$this->urlArgs = $urlArgs;
		$defaults = $this->findDefaults();

		$ranges = $this->urlArgs->getArray( 'pv', [] );
		$ranges = $ranges[$property] ?? [];

		$rangeFilters = [];
		$clear = $this->urlArgs->getArray( 'clear' );

		foreach ( $ranges as $value ) {

			if ( strpos( $value, '|' ) === false ) {
				continue;
			}

			[ $min, $max ] = explode( '|', $value, 2 );

			$rangeFilters = [ 'min' => $min, 'max' => $max ];
		}

		$numbers = [];
		$postFix = '';

		foreach ( $values as $key => $value ) {

			if ( strpos( $key, ' ' ) !== false ) {
				[ $v, $postFix ] = explode( ' ', $key );
			}

			if ( isset( $raw[$key] ) ) {
				$v = $raw[$key];
			}

			$numbers[] = round( $v, $defaults['precision'] );
		}

		$min = min( $numbers ) - $defaults['uncertainty'];
		$max = max( $numbers ) + $defaults['uncertainty'];

		if (
			isset( $clear[$property] ) ||
			$rangeFilters === [] ||
			$rangeFilters['min'] === $rangeFilters['max'] ) {
			$from = $min;
			$to = $max;
		} else {
			$from = $rangeFilters['min'];
			$to = $rangeFilters['max'];
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-overlay-spinner mini flex',
				'style' => "position:relative;margin-top:10px;margin-bottom:20px;"
			]
		) . Html::rawElement(
			'input',
			[
				'class' => 'filter-items range-filter',
				'style' => 'display:none !important',
				'data-min' => $min,
				'data-max' => ( $max - $min ) <= 0 ? $min + $defaults['min_interval'] : $max,
				'data-from' => $from,
				// 'data-value-list' => json_encode( $numbers ),
				'data-to' => $to,
				'data-step' => $defaults['step_size'],
				'data-min-interval' => $defaults['min_interval'],
				'data-postfix' => $postFix !== '' ? "&nbsp;$postFix" : '',
				'data-property' => $property,
				'name' => "pv[$property][]",
				'value' => "$min|$max",
				'form' => 'search-input-form'
			]
		);
	}

	private function findDefaults() {
		$defaults = [
			'step_size' => 1,
			'min_interval' => 5,
			'precision' => 2,
			'uncertainty' => 0
		];

		if ( !$this->compartmentIterator->has( 'range_control' ) ) {
			return $defaults;
		}

		foreach ( $this->compartmentIterator as $compartment ) {
			$data = $compartment->get( 'range_control' );

			$defaults = [
				'step_size' => $data['step_size'],
				'min_interval' => $data['min_interval'],
				'precision' => $data['precision'],
				'uncertainty' => $data['uncertainty'] ?? 0
			];
		}

		return $defaults;
	}

}
