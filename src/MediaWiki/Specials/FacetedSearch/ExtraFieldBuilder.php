<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use SMW\Localizer\MessageLocalizerTrait;
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
	 * @var TemplateParser
	 */
	private $templateParser;

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 * @param TemplateParser $templateParser
	 */
	public function __construct( Profile $profile, TemplateParser $templateParser ) {
		$this->profile = $profile;
		$this->templateParser = $templateParser;
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

			$html .= $this->templateParser->processTemplate(
				'search.extrafield.input',
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
		}

		return $this->templateParser->processTemplate(
			'search.extrafields',
			[
				'fields' => $html,
				'css-class' => $cssClass,
				'section-label' => 'Search fields',
				'theme' => $this->profile->get( 'theme' )
			]
		);
	}

}
