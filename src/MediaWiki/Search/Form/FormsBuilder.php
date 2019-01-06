<?php

namespace SMW\MediaWiki\Search\Form;

use Html;
use RuntimeException;
use SMW\Message;
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
	 * @var OpenForm
	 */
	private $openForm;

	/**
	 * @var CustomForm
	 */
	private $customForm;

	/**
	 * @var string
	 */
	private $defaultForm = '';

	/**
	 * @var []
	 */
	private $formList = [];

	/**
	 * @var []
	 */
	private $preselectNsList = [];

	/**
	 * @var []
	 */
	private $hiddenNsList = [];

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @var []
	 */
	private $termPrefixes = [];

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
	public function getTermPrefixes() {
		return $this->termPrefixes;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getHiddenNsList() {
		return $this->hiddenNsList;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getPreselectNsList() {

		$activeForm = $this->request->getVal( 'smw-form', $this->defaultForm );

		if ( $activeForm === null ) {
			return [];
		}

		$activeForm = self::toLowerCase( $activeForm );

		if ( isset( $this->preselectNsList[$activeForm] )) {
			return $this->preselectNsList[$activeForm];
		}

		return [];
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function buildFormList() {

		$list = [];
		$name = '';
		$value = '';

		foreach ( $this->formList as $k => $options ) {

			if ( $k === '' ) {
				continue;
			}

			if ( $options['selected'] ) {
				$name = $options['name'];
				$value = $k;
			}

			$list[] = [ 'id' => $k, 'name' => $options['name'], 'desc' => $options['name'] ];
		}

		return Html::rawElement(
			'button',
			[
				'type' => 'button',
				'id' => 'smw-search-forms',
				'class' => 'smw-selectmenu-button is-disabled',
				'title' => Message::get( 'smw-search-profile-extended-section-form', Message::TEXT, Message::USER_LANGUAGE  ),
				'name' => 'smw-form',
				'value' => $value,
				'data-list' => json_encode( $list ),
				'data-nslist' => json_encode( $this->preselectNsList )
			],
			$name === '' ? 'Form' : $name
		) . Html::rawElement(
			'input',
			[
				'type' => 'hidden',
				'name' => 'smw-form',
				'value' => $value,
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function buildForm( array $data ) {

		if ( !isset( $data['forms'] ) ) {
			throw new RuntimeException( "Missing forms definition" );
		}

		if ( isset( $data['default_form'] ) ) {
			$this->defaultForm = self::toLowerCase( $data['default_form'] );
		}

		if ( isset( $data['term_parser']['prefix'] ) ) {
			$this->termPrefixes = $data['term_parser']['prefix'];
		}

		$activeForm = $this->request->getVal( 'smw-form', $this->defaultForm );

		$divider = "<div class='divider' style='display:none;'></div>";

		if ( $activeForm !== null && $activeForm !== ''  ) {
			$divider = "<div class='divider'></div>";
		}

		$this->formList = [];
		$this->preselectNsList = [];
		$this->parameters = [];

		if ( $activeForm === null || $activeForm === '' ) {
			$forms = [ '' => '' ] + $data['forms'];
		} else {
			$forms = $data['forms'];
		}

		$formDefinitions = [];

		if ( $this->openForm === null ) {
			$this->openForm = $this->formsFactory->newOpenForm( $this->request );
		}

		if ( $this->customForm === null ) {
			$this->customForm = $this->formsFactory->newCustomForm( $this->request );
		}

		ksort( $forms );

		foreach ( $forms as $name => $definition ) {
			$formDefinitions[] = $this->form_fields( $data, $activeForm, $name, $definition );
		}

		if ( isset( $data['namespaces']['preselect'] ) && is_array( $data['namespaces']['preselect'] ) ) {
			$this->preselect_namespaces( $data['namespaces']['preselect'] );
		}

		if ( isset( $data['namespaces']['hidden'] ) && is_array(  ) ) {
			$this->hidden_namespaces( $data['namespaces']['hidden'] );
		}

		if ( isset( $data['namespaces']['hide'] ) && is_array( $data['namespaces']['hide'] ) ) {
			$this->hidden_namespaces( $data['namespaces']['hide'] );
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

	private function form_fields( $data, $activeForm, $name, $definition ) {

		// Short form, URL query conform
		$s = self::toLowerCase( $name );
		$this->formList[$s] = [ 'name' => $name, 'selected' => $activeForm === $s ];

		if ( !is_array( $definition ) ) {
			return;
		}

		$description = '';
		$isActiveForm = $s === $activeForm;

		if ( isset( $data['descriptions'] ) ) {
			$description = $this->findDescription( $data['descriptions'], $name, $isActiveForm );
		}

		if ( $s === 'open' ) {
			$this->openForm->isActiveForm( $isActiveForm );
			$fields = $this->openForm->makeFields();
			$this->parameters = array_merge( $this->parameters, $this->openForm->getParameters() );
		} else {
			$this->customForm->isActiveForm( $isActiveForm );
			$fields = $this->customForm->makeFields( $definition );
			$this->parameters = array_merge( $this->parameters, $this->customForm->getParameters() );
		}

		return Html::rawElement(
			'div',
			[
				'id' => "smw-form-{$s}",
				'class' => 'smw-fields'
			],
			$description . $fields
		);
	}

	private function preselect_namespaces( $preselect ) {
		foreach ( $preselect as $k => $values ) {
			$k = self::toLowerCase( $k );
			$this->preselectNsList[$k] = [];

			foreach ( $values as $ns ) {
				if ( is_string( $ns ) && defined( $ns ) ) {
					$this->preselectNsList[$k][] = constant( $ns );
				}

				if ( is_numeric( $ns ) ) {
					$this->preselectNsList[$k][] = $ns;
				}
			}
		}
	}

	private function hidden_namespaces( $hidden ) {
		foreach ( $hidden as $ns ) {
			if ( is_string( $ns ) && defined( $ns ) ) {
				$this->hiddenNsList[] = constant( $ns );
			}

			if ( is_numeric( $ns ) ) {
				$this->hiddenNsList[] = $ns;
			}
		}
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
