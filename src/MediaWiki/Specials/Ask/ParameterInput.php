<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use ParamProcessor\ParamDefinition;
use Xml;

/**
 * Simple class to get a HTML input for the parameter.
 * Usable for when creating a GUI from a parameter list.
 *
 * Based on 'addOptionInput' from Special:Ask in SMW 1.5.6.
 *
 * TODO: nicify HTML
 *
 * @since 1.9
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ParameterInput {

	/**
	 * The parameter to print an input for.
	 *
	 * @since 1.9
	 *
	 * @var ParamDefinition
	 */
	protected $param;

	/**
	 * The current value for the parameter. When provided,
	 * it'll be used as value for the input, otherwise the
	 * parameters default value will be used.
	 *
	 * @since 1.9
	 *
	 * @var mixed: string or false
	 */
	protected $currentValue;

	/**
	 * Name for the input.
	 *
	 * @since 1.9
	 *
	 * @var string
	 */
	protected $inputName;

	/**
	 * @var array
	 */
	private $attributes = [];

	/**
	 * Constructor.
	 *
	 * @since 1.9
	 *
	 * @param ParamDefinition $param
	 * @param mixed $currentValue
	 */
	public function __construct( ParamDefinition $param, $currentValue = false ) {
		$this->currentValue = $currentValue;
		$this->inputName = $param->getName();
		$this->param = $param;
	}

	/**
	 * Sets the current value.
	 *
	 * @since 1.9
	 *
	 * @param mixed $currentValue
	 */
	public function setCurrentValue( $currentValue ) {
		$this->currentValue = $currentValue;
	}

	/**
	 * Sets the name for the input; defaults to the name of the parameter.
	 *
	 * @since 1.9
	 *
	 * @param string $name
	 */
	public function setInputName( $name ) {
		$this->inputName = $name;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 */
	public function setAttributes( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Returns the HTML for the parameter input.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getHtml() {
		$allowedValues = $this->param->getAllowedValues();

		if ( $allowedValues === [] ) {
			switch ( $this->param->getType() ) {
				case 'char':
				case 'float':
				case 'integer':
				case 'number':
					return $this->getNumberInput();
				case 'boolean':
					return $this->getBooleanInput();
				case 'string':
				default:
					return $this->getStrInput();
			}
		}

		if ( $this->param->isList() ) {
			return $this->getCheckboxListInput( $allowedValues );
		}

		return $this->getSelectInput( $allowedValues );
	}

	/**
	 * Returns the value to initially display with the input.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getValueToUse() {
		$value = $this->currentValue === false ? $this->param->getDefault() : $this->currentValue;

		if ( $this->param->isList() && is_array( $value ) ) {
			$value = implode( $this->param->getDelimiter(), $value );
		}

		// #1473
		if ( $value === [] ) {
		   $value = '';
		}

		return $value;
	}

	/**
	 * Gets a short text input suitable for numbers.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getNumberInput() {

		$attributes = [
			'class' => 'parameter-number-input',
			'size' => 6,
			'style' => "width: 95%;"
		];

		if ( $this->attributes !==[] ) {
			$attributes = $this->attributes;
		}

		return Html::input(
			$this->inputName,
			$this->getValueToUse(),
			'text',
			$attributes
		);
	}

	/**
	 * Gets a text input for a string.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getStrInput() {

		$attributes = [
			'class' => 'parameter-string-input',
			'size' => 20,
			'style' => "width: 95%;"
		];

		if ( $this->attributes !==[] ) {
			$attributes = $this->attributes;
		}

		return Html::input(
			$this->inputName,
			$this->getValueToUse(),
			'text',
			$attributes
		);
	}

	/**
	 * Gets a checkbox.
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getBooleanInput() {

		$attributes = [
			'class' => 'parameter-boolean-input'
		];

		if ( $this->attributes !==[] ) {
			$attributes = $this->attributes;
		}

		return Xml::check(
			$this->inputName,
			$this->getValueToUse(),
			$attributes
		);
	}

	/**
	 * Gets a select menu for the provided values.
	 *
	 * @since 1.9
	 *
	 * @param array $valueList
	 *
	 * @return string
	 */
	protected function getSelectInput( array $valueList ) {
		$options = [];
		$options[] = '<option value=""></option>';

		$currentValues = (array)$this->getValueToUse();
		if ( is_null( $currentValues ) ) {
			$currentValues = [];
		}

		foreach ( $valueList as $value ) {
			$options[] =
				'<option value="' . htmlspecialchars( $value ) . '"' .
					( in_array( $value, $currentValues ) ? ' selected="selected"' : '' ) . '>' . htmlspecialchars( $value ) .
				'</option>';
		}

		return Html::rawElement(
			'select',
			[
				'name' => $this->inputName,
				'class'=> 'parameter-select-input'
			],
			implode( "\n", $options )
		);
	}

	/**
	 * Gets a list of input boxes for the provided values.
	 *
	 * @since 1.9
	 *
	 * @param array $valueList
	 *
	 * @return string
	 */
	protected function getCheckboxListInput( array $valueList ) {
		$boxes = [];
		$currentValues = [];

		$values = $this->getValueToUse();

		// List of comma separated values, see ParametersProcessor::getParameterList
		if ( strpos( $values, ',' ) !== false ) {
			$currentValues = array_flip(
				array_map( 'trim', explode( ',', $values ) )
			);
		} elseif ( $values !== '' ) {
			$currentValues[$values] = true;
		}

		foreach ( $valueList as $value ) {

			// Use a value not a simple "true"
			$attr = [
				'type' => 'checkbox',
				'name' => $this->inputName . '[]',
				'value' => $value
			];

			$boxes[] = Html::rawElement(
				'span',
				[
					'class' => 'parameter-checkbox-input',
					'style' => 'white-space: nowrap; padding-right: 5px;'
				],
				Html::rawElement(
					'input',
					$attr + ( isset( $currentValues[$value] ) ? [ 'checked' ] : [] )
				) . Html::element( 'tt', [], $value )
			);
		}

		return implode( "\n", $boxes );
	}

}
