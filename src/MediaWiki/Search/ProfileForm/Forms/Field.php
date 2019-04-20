<?php

namespace SMW\MediaWiki\Search\ProfileForm\Forms;

use Html;
use SMW\Highlighter;
use SMW\Message;
use Title;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Field {

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function create( $type, $attributes = [] ) {

		$attributes['class'] = "smw-$type" . ( isset( $attributes['class'] ) ? ' ' . $attributes['class'] : '' );

		if ( isset( $attributes['tooltip'] ) ) {
			$attributes['tooltip'] = $this->tooltip( $attributes );
			$attributes['class']  .= " smw-$type-tooltip";
		}

		if ( $type === 'input' ) {
			return $this->input( $attributes );
		}

		if ( $type === 'select' ) {
			return $this->select( $attributes );
		}

		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function tooltip( $attributes = [] ) {

		$highlighter = Highlighter::factory( Highlighter::TYPE_NOTE );
		$msg = '';

		// Simple text, or is it message-key?
		if ( isset( $attributes['tooltip'] ) && Message::exists( $attributes['tooltip'] ) ) {
			$msg = Message::get( $attributes['tooltip'], Message::PARSE, Message::USER_LANGUAGE );
		} elseif ( isset( $attributes['tooltip'] ) ) {
			$msg = $attributes['tooltip'];
		}

		$highlighter->setContent(
			[
				'content' => $msg,
				'style' => 'margin-left:5px;vertical-align:-1px;'
			]
		);

		return $highlighter->getHtml();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function select( $attributes = [] ) {

		$list = [];
		$html = [];
		$selected = false;

		if ( isset( $attributes['list'] ) ) {
			$list = $attributes['list'];
			unset( $attributes['list'] );
		}

		if ( isset( $attributes['selected'] ) ) {
			$selected = $attributes['selected'];
			unset( $attributes['selected'] );
		}

		foreach ( $list as $key => $value ) {

			$opt = '';
			$val = $value;

			if ( is_array( $value ) ) {
				$val = $value[0];
				$opt =  ' ' . $value[1];
			}

			if ( $selected === $key ) {
				$opt = ' selected';
			}

			$html[] = "<option value='$key'$opt>$val</option>";
		}

		$style = '';
		$name = '';
		$label = '';
		$class = '';

		if ( isset( $attributes['class'] ) ) {
			$class = $attributes['class'];
			unset( $attributes['class'] );
		}

		if ( isset( $attributes['name'] ) ) {
			$name = $attributes['name'];
		}

		if ( isset( $attributes['style'] ) ) {
			$style .= $attributes['style'];
			unset( $attributes['style'] );
		}

		if ( isset( $attributes['display'] ) ) {
			$style = 'display:' . $attributes['display'] . ';';
			unset( $attributes['display'] );
		}

		if ( isset( $attributes['multifield'] ) ) {
			$name = $attributes['name'] . "[]";
			unset( $attributes['multifield'] );
		}

		if ( isset( $attributes['label'] ) ) {
			$label = "<label for='$name'>" . $attributes['label'] . "</label>";
			unset( $attributes['label'] );
		}

		return $label . Html::rawElement(
			'select',
			[
				'class' => $class,
				'name'  => $name,
			] + ( $style !== '' ? [ 'style' => $style ] : [] ) + $attributes,
			implode( '', $html )
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function input( $attributes = [] ) {

		$class = isset( $attributes['class'] ) ? $attributes['class'] : '';
		$type = 'text';
		$tooltip = '';
		$required = false;
		$placeholder = '';
		$value = '';
		$style = '';
		$name = '';

		if ( isset( $attributes['style'] ) ) {
			$style .= $attributes['style'];
			unset( $attributes['style'] );
		}

		if ( isset( $attributes['display'] ) ) {
			$style = 'display:' . $attributes['display'] . ';';
			unset( $attributes['display'] );
		}

		if ( isset( $attributes['name'] ) ) {
			$name = $attributes['name'];
			unset( $attributes['name'] );
		}

		if ( $name !== '' && isset( $attributes['multifield'] ) ) {
			$name .= "[]";
			unset( $attributes['multifield'] );
		}

		if ( isset( $attributes['required'] ) ) {
			$required = (bool) $attributes['required'];
			unset( $attributes['required'] );
		}

		if ( isset( $attributes['placeholder'] ) ) {
			$placeholder = $attributes['placeholder'];
		}

		if ( isset( $attributes['tooltip'] ) ) {
			$tooltip = $attributes['tooltip'];
			unset( $attributes['tooltip'] );
		}

		if ( isset( $attributes['type'] ) ) {
			$type = $attributes['type'];
		}

		if ( isset( $attributes['value'] ) ) {
			$value = $attributes['value'];
		}

		$attr = [
			'class' => $class,
			'name' => $name,
			'type' => $type,
			'value' => $value,
			'placeholder' => $placeholder,
			'data-required' => $required
		] + $attributes;

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-input-field',
			] + ( $style !== '' ? [ 'style' => $style ] : [] ),
			Html::rawElement(
				'input',
				$attr
			) . $tooltip
		);
	}

}
