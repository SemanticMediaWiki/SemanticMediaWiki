<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\Localizer\MessageLocalizerTrait;
use SMW\Utils\TemplateEngine;
use SMW\Utils\UrlArgs;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ExtraFieldBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 * @param TemplateEngine $templateEngine
	 */
	public function __construct( Profile $profile, TemplateEngine $templateEngine ) {
		$this->profile = $profile;
		$this->templateEngine = $templateEngine;
	}

	/**
	 * @since 3.2
	 *
	 * @param UrlArgs|null $urlArgs
	 *
	 * @return string
	 */
	public function buildHTML( ?UrlArgs $urlArgs = null ): string {
		if ( ( $fieldList = $this->profile->get( 'search.extra_fields.field_list', [] ) ) === [] ) {
			return '';
		}

		if ( $urlArgs->get( 'reset' ) === null ) {
			$values = $urlArgs !== null ? $urlArgs->getArray( 'fields' ) : [];
		} else {
			$values = [];
		}

		if ( $urlArgs->find( 'cstate.extra-fields' ) !== '' ) {
			$cssClass = $urlArgs->find( 'cstate.extra-fields' ) === 'c' ? 'mw-collapsible mw-collapsed' : 'mw-collapsible';
		} elseif ( $this->profile->get( 'search.extra_fields.default_collapsed', true ) ) {
			$cssClass = $values === [] ? 'mw-collapsible mw-collapsed' : 'mw-collapsible';
		} else {
			$cssClass = 'mw-collapsible';
		}

		$html = '';
		$i = 0;

		foreach ( $fieldList as $key => $definition ) {
			$class = "$key";
			$isAutocomplete = false;
			$property = $definition['property'];

			if ( isset( $definition['autocomplete'] ) && $definition['autocomplete'] ) {
				$class .= ' smw-propertyvalue-input autocomplete-arrow';
				$isAutocomplete = true;
			}

			$this->templateEngine->compile(
				'search-extra-field-input',
				[
					'label' => $definition['label'],
					'type' => $isAutocomplete ? 'text' : 'search',
					'name' => "fields[$key]",
					'value' => $values[$key] ?? '',
					'placeholder' => '...',
					'property' => $property,
					'class' => $class
				]
			);

			$i++;
			$html .= $this->templateEngine->publish( 'search-extra-field-input' );
		}

		$this->templateEngine->compile(
			'search-extra-fields',
			[
				'fields' => $html,
				'css-class' => $cssClass,
				'section-label' => 'Search fields',
				'theme' => $this->profile->get( 'theme' )
			]
		);

		return $this->templateEngine->publish( 'search-extra-fields' );
	}

}
