<?php

namespace SMW\MediaWiki\Search\Form;

use RuntimeException;
use SMW\Highlighter;
use SMW\Message;
use SMW\DIProperty;
use Html;
use Title;
use WebRequest;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsBuilder {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var FormsFactory
	 */
	private $formsFactory;

	/**
	 * @var []
	 */
	private $formList = [];

	/**
	 * @var []
	 */
	private $nsList = [];

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 * @param FormsFactory $formsFactory
	 */
	public function __construct( WebRequest $request, FormsFactory $formsFactory ) {
		$this->request = $request;
		$this->formsFactory = $formsFactory;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $form
	 *
	 * @return string
	 */
	public static function toLowerCase( $key ) {
		return strtolower( str_replace( [ ' ' ], [ '' ], $key ) );
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getNsList() {

		$activeForm = $this->request->getVal( 'smw-form' );

		if ( $activeForm === null ) {
			return [];
		}

		$activeForm = self::toLowerCase( $activeForm );

		if ( isset( $this->nsList[$activeForm] )) {
			return $this->nsList[$activeForm];
		}

		return [];
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return string
	 */
	public function makeSelectList( Title $title ) {

		$html = [];

		foreach ( $this->formList as $k => $options ) {

			$name = $options['name'];
			$selected = $options['selected'] ? 'selected' : '';

			$html[] = "<option value='$k' $selected>$name</option>";
		}

		$link = Html::element(
			'a',
			[
				'href' => $title->getFullUrl()
			],
			'Form' //TODO: Translation key smw-form
		);

		$select = Html::rawElement(
			'label',
			[
				'for' => 'smw-form'
			],
			$link . ':&nbsp;'
		) . Html::rawElement(
			'select',
			[
				'id' => 'smw-form',
				'name' => 'smw-form'
			],
			implode( '', $html )
		);

		return Html::rawElement(
			'div',
			[
				'id' => 'smw-search-forms',
				'class' => 'smw-select is-disabled',
				'data-nslist' => json_encode( $this->nsList )
			],
			$select
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $json
	 *
	 * @return string
	 */
	public function buildFromJSON( $json ) {

		$data = json_decode( $json, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new RuntimeException( "JSON decode error: " . json_last_error_msg() );
		}

		if ( !isset( $data['forms'] ) ) {
			throw new RuntimeException( "Missing forms definition" );
		}

		$activeForm = $this->request->getVal( 'smw-form' );

		$divider = "<div class='divider' style='display:none;'></div>";

		if ( $activeForm !== null && $activeForm !== ''  ) {
			$divider = "<div class='divider'></div>";
		}

		$this->formList = [];

		$this->nsList = [];
		$this->parameters = [];

		if ( $activeForm === null || $activeForm === '' ) {
			$forms = [ '' => '' ] + $data['forms'];
		} else {
			$forms = $data['forms'];
		}

		$formDefinitions = [];
		$descriptions = isset( $data['descriptions'] ) ? $data['descriptions'] : [];
		$i = 0;

		$openForm = $this->formsFactory->newOpenForm( $this->request );
		$customForm = $this->formsFactory->newCustomForm( $this->request );

		foreach ( $forms as $name => $definition ) {

			// Short form, URL query conform
			$s = self::toLowerCase( $name );
			$this->formList[$s] = [ 'name' => $name, 'selected' => $activeForm === $s ];

			if ( !is_array( $definition ) ) {
				continue;
			}

			$isActiveForm = $s === $activeForm;
			$description = $this->findDescription( $descriptions, $name, $isActiveForm );

			if ( $s === 'open' ) {
				$openForm->isActiveForm( $isActiveForm );
				$fields = $openForm->makeFields();
				$this->parameters = array_merge( $this->parameters, $openForm->getParameters() );
			} else {
				$customForm->isActiveForm( $isActiveForm );
				$fields = $customForm->makeFields( $definition );
				$this->parameters = array_merge( $this->parameters, $customForm->getParameters() );
			}

			// Form definition!
			$formDefinitions[] = Html::rawElement(
				'div',
				[
					'id' => "smw-form-{$s}",
					'class' => 'smw-fields'
				],
				$description . $fields
			);

			$i++;
		}

		if ( isset( $data['namespaces'] ) ) {
			foreach ( $data['namespaces'] as $k => $values ) {
				$k = self::toLowerCase( $k );
				$this->nsList[$k] = [];

				foreach ( $values as $ns ) {
					if ( defined( $ns ) ) {
						$this->nsList[$k][] = constant( $ns );
					}
				}
			}
		}

		return $divider . Html::rawElement(
			'div',
			[
				'id' => 'smw-form-definitions',
				'class' => 'is-disabled'
			],
			implode( '', $formDefinitions )
		);
	}

	private function findDescription( $descriptions, $name, $isActiveForm ) {

		if ( !isset( $descriptions[$name] ) ) {
			return '';
		}

		$display = $isActiveForm ? 'inline-block' : 'none';

		// Simple text, or is it message-key?
		if ( Message::exists( $descriptions[$name] ) ) {
			$description = Message::get( $descriptions[$name], Message::PARSE, Message::USER_LANGUAGE );
		} else{
			$description = $descriptions[$name];
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-form-description',
				'style' => "display:$display;"
			],
			$description
		);
	}

}
