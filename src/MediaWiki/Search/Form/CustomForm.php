<?php

namespace SMW\MediaWiki\Search\Form;

use Html;
use SMW\DIProperty;
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
class CustomForm {

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
	 * @var []
	 */
	private $fieldCounter = [];

	/**
	 * @var []
	 */
	private $html5TypeMap = [
		'_txt' => 'text',
		'_uri' => 'url',
		'_dat' => 'date',
		'_tel' => 'tel',
		'_ema' => 'email',
		'_num' => 'number'
	];

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
	public function makeFields( $definition ) {

		$fields = [];
		$this->parameters = [];
		$nameList = [];

		foreach ( $definition as $property ) {
			$options = [];

			// Simple list, or does a property have some options?
			if ( is_array( $property ) ) {
				foreach ( $property as $p => $options ) {
					$property = $p;
				}
			}

			// Transforms (Foo bar -> foobar), better URL query conformity
			$name = FormsBuilder::toLowerCase( $property );
			$value = '';

			// Field with the same name should only appear once in a form
			if ( isset( $nameList[$name] ) ) {
				continue;
			}

			// Each form definition may contain properties that are also defined
			// in other forms therefore count its member position so that only
			// values for the active form are fetched. The counter is used as
			// positioning for the value array index.
			if ( !isset( $this->fieldCounter[$name] ) ) {
				$this->fieldCounter[$name] = 0;
			} else {
				$this->fieldCounter[$name]++;
			}

			// Find request related value for the active form
			if ( $this->isActiveForm ) {
				$vals = $this->request->getArray( $name );

				$i = $this->fieldCounter[$name];
				$value = isset( $vals[$i] ) ? $vals[$i] : $vals[0];
				$this->parameters[$name] = $value;
			}

			$nameList[$name] = true;
			$fields[] = $this->makeField( $name, $property, $value, $options );
		}

		return implode( '', $fields );
	}

	private function makeField( $name, $property, $value, $options ) {

		$display = $this->isActiveForm ? 'inline-block' : 'none';
		$options = !is_array( $options ) ? [] : $options;

		if ( !isset( $options['placeholder'] ) ) {
			$options['placeholder'] = "$property ...";
		}

		if ( !isset( $options['class'] ) ) {
			$options['class'] = "";
		}

		if ( isset( $options['autocomplete'] ) && $options['autocomplete'] ) {
			$options['class'] .= " smw-propertyvalue-input autocomplete-arrow";
		}

		if ( isset( $options['type'] ) ) {
			$type = $options['type'];
		} else {
			$typeID = DIProperty::newFromUserLabel( $property )->findPropertyTypeID();
			$type = 'text';

			if ( isset( $this->html5TypeMap[$typeID] ) ) {
				$type = $this->html5TypeMap[$typeID];
			}
		}

		// Numeric names are not useful and may have been caused by an invalid
		// JSON array/object definition
		if ( is_numeric( $name ) ) {
			$options[] = 'disabled';
		}

		$attributes = [
			'name' => $name,
			'value' => $value,
			'type'  => $type,
			'display' => $display,
			'placeholder' => $options['placeholder'],
			'data-property' => $property,
			'title' => $property,
			'multifield' => true,
		] + $options;

		return $this->field->create( 'input', $attributes );
	}

}
