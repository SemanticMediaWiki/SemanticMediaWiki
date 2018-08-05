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
	public function getHiddenNsList() {
		return $this->hiddenNsList;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getPreselectNsList() {

		$activeForm = $this->request->getVal( 'smw-form' );

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
	 * @param Title $title
	 *
	 * @return string
	 */
	public function buildFormList( Title $title, $link = '' ) {

		$html = [];

		foreach ( $this->formList as $k => $options ) {

			$name = $options['name'];
			$selected = $options['selected'] ? 'selected' : '';

			$html[] = "<option value='$k' $selected>$name</option>";
		}

		$attr = [ 'style' => 'border-right:1px solid #ccc;margin-right:4px;' ];

		$link = ( $link !== '' ? $link . '&nbsp;' : '' ) . Html::element(
			'a',
			[
				'class' => 'smw-form-link-form',
				'href' => $title->getFullUrl(),
				'title' => 'Find forms by type'
			],
			'Form'
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
				'data-nslist' => json_encode( $this->preselectNsList )
			],
			$select
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

		$activeForm = $this->request->getVal( 'smw-form' );

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
			}
		}
	}

	private function hidden_namespaces( $hidden ) {
		foreach ( $hidden as $ns ) {
			if ( is_string( $ns ) && defined( $ns ) ) {
				$this->hiddenNsList[] = constant( $ns );
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
