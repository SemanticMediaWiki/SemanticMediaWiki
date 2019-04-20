<?php

namespace SMW\MediaWiki\Search\ProfileForm\Forms;

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
class OpenForm {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var Field
	 */
	private $field;

	/**
	 * @var boolean
	 */
	private $isActiveForm = false;

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 */
	public function __construct( WebRequest $request ) {
		$this->request = $request;
		$this->field = new Field();
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
	 * @param boolean $isActiveForm
	 */
	public function isActiveForm( $isActiveForm ) {
		$this->isActiveForm = (bool)$isActiveForm;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $definition
	 */
	public function makeFields( $definition = [] ) {

		$this->parameters = [];

		$group = '';
		$properties = [];
		$values = [];
		$op = [];

		if ( $this->isActiveForm ) {
			$properties = $this->request->getArray( 'property', [] );
			$values = $this->request->getArray( 'pvalue', [] );
			$op = $this->request->getArray( 'op', [] );
		}

		$this->parameters = [
			'property' => [],
			'pvalue' => [],
			'op' => []
		];

		foreach ( $properties as $i => $property ) {

			if ( $property === '' ) {
				continue;
			}

			$this->parameters['property'][] = $property;
			$this->parameters['pvalue'][] = $values[$i];
			$this->parameters['op'][] = $op[$i];

			$group .= $this->makeFieldGroup( $property, $values[$i], $op[$i] );
		}

		// At least one empty group
		$group .= $this->makeFieldGroup( '', '', '' );

		return $group;
	}

	private function makeFieldGroup( $property, $value, $op ) {

		$display = $this->isActiveForm ? 'inline-block' : 'none';

		$attributes = [
			'multifield' => true,
			'display' => $display,
			'name' => 'property',
			'value' => $property,
			'data-autocomplete-indicator' => true,
			'placeholder' => 'Property ...',
			'class' => 'smw-property-input autocomplete-arrow'
		];

		$prop = $this->field->create( 'input', $attributes );

		$attributes = [
			'multifield' => true,
			'display' => $display,
			'name' => 'pvalue',
			'value' => $value,
			'data-autocomplete-indicator' => true,
			'data-property' => $property,
			'placeholder' => 'Value ...',
			'class' => 'smw-propertyvalue-input autocomplete-arrow'
		];

		if ( $value === '' ) {
			$attributes['class'] .= ' is-disabled';
		}

		$pvalue = $this->field->create( 'input', $attributes );
		$disabled = $property === '' && $value === '' ? 'disabled' : '';

		$list = [
			''    => '',
			'OR'  => 'OR',
			' '    => [ '————', 'disabled' ],
			'del' => [ 'del', $disabled ]
		];

		$attributes = [
			'list' => $list,
			'selected' => $op,
			'multifield' => true,
			'name' => 'op',
			'display' => $display,
			'class' => 'smw-select-field'
		];

		$select = $this->field->create( 'select', $attributes );

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-input-group'
			],
			$prop . $pvalue . $select
		);
	}

}
