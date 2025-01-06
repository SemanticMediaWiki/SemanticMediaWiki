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
class CheckboxValueFilter {

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

		$this->urlArgs = $urlArgs;

		$list = [
			'unlinked' => [],
			'linked' => []
		];

		arsort( $values );

		$valueFilters = $this->getValueFilters( $property );
		$isClear = $this->urlArgs->find( "clear.$property", false );

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

			$this->matchFilter( $property, $key, $label, $count, $valueFilters, $list, $isClear );
		}

		if ( $list['linked'] === [] && $list['unlinked'] === [] ) {
			$list['linked'] = [ $this->msg( 'smw-facetedsearch-no-filters' ) ];
			$cssClass = 'no-result';
			$option = '';
		} else {

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
				'unlinked' => implode( '', $list['unlinked'] ),
				'linked' => implode( '', $list['linked'] ),
				'css-class' => $cssClass
			]
		);

		return $this->templateEngine->publish( 'filter-items' );
	}

	private function matchFilter( $property, $key, $label, $count, $valueFilters, &$list, $isClear ) {
		// Make sure characters like `"` are encoded otherwise those will be removed
		// from the value representation
		$key = htmlspecialchars( $key );

		$attr = [
			'name' => "pv[$property][]",
			'value' => $key,
			'count' => $count,
			'label' => $label,
			'checked' => ''
		];

		if ( isset( $valueFilters[$key] ) && $isClear === false ) {
			$attr['checked'] = 'checked';

			if ( isset( $list['unlinked'][$key] ) ) {
				return;
			}

			$this->templateEngine->compile(
				'filter-item-checkbox',
				$attr
			);

			$list['unlinked'][$key] = $this->templateEngine->publish( 'filter-item-checkbox' );
		} else {
			$this->templateEngine->compile(
				'filter-item-checkbox',
				$attr
			);

			$list['linked'][$key] = $this->templateEngine->publish( 'filter-item-checkbox' );
		}
	}

	private function getValueFilters( $property ) {
		$valueFilters = $this->urlArgs->getArray( 'pv' );
		$valueFilters = $valueFilters[$property] ?? [];

		return is_array( $valueFilters ) ? array_flip( $valueFilters ) : [];
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
