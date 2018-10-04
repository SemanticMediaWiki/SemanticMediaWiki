<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use ParamProcessor\ParamDefinition;
use SMW\Message;
use SMW\Utils\HtmlDivTable;
use SMWQueryProcessor as QueryProcessor;
use Title;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since   1.8
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class ParametersWidget {

	/**
	 * @var boolean
	 */
	private static $isTooltipDisplay = false;

	/**
	 * @var integer
	 */
	private static $defaultLimit = 50;

	/**
	 * @since 2.5
	 *
	 * @param boolean $isTooltipDisplay
	 */
	public static function setTooltipDisplay( $isTooltipDisplay ) {
		self::$isTooltipDisplay = (bool)$isTooltipDisplay;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $defaultLimit
	 */
	public static function setDefaultLimit( $defaultLimit ) {
		self::$defaultLimit = $defaultLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return string
	 */
	public static function fieldset( Title $title, array $parameters ) {

		$toggle = Html::rawElement(
			'span',
			[
				'style' => 'margin-left:5px;'
			],
			'&#160;[' . Html::rawElement(
				'span',
				[
					'class' => 'options-toggle-action',
				],
				Html::rawElement(
					'label',
					[
						'for' => 'options-toggle',
						'title' => Message::get( 'smw-section-expand', Message::TEXT, Message::USER_LANGUAGE )
					],
					'+'
				)
			) . ']&#160;'
		);

		$options = Html::rawElement(
			'div',
			[
				'id' => 'parameter-title',
				'class' => 'strike'
			],
			Html::rawElement(
				'span',
				[],
				Message::get( 'smw-ask-parameters', Message::TEXT, Message::USER_LANGUAGE ) . $toggle
			)
		) . Html::rawElement(
			'div',
			[],
			'<input type="checkbox" id="options-toggle"/>' . Html::rawElement(
				'div',
				[
					'id' => 'options-list',
					'class' => 'options-list'
				],
				Html::rawElement(
					'div',
					[
						'class' => 'options-parameter-list'
					],
					self::parameterList( $parameters )
				)
			)
		);

		return Html::rawElement(
			'fieldset',
			[],
			Html::element(
				'legend',
				[],
				Message::get( 'smw-ask-options', Message::TEXT, Message::USER_LANGUAGE )
			). FormatListWidget::selectList(
				$title,
				$parameters
			) . $options . SortWidget::sortSection( $parameters )
		);
	}

	/**
	 * Display a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @since 1.8
	 *
	 * @param string $format
	 * @param array $parameters The current values for the parameters (name => value)
	 *
	 * @return string
	 */
	public static function parameterList( array $values ) {

		$format = 'broadtable';

		if ( isset( $values['format'] ) ) {
			$format = $values['format'];
		}

		$optionList = self::optionList(
			QueryProcessor::getFormatParameters( $format ),
			$values
		);

		$i = 0;
		$n = 0;

		$rowHtml = '';
		$resultHtml = '';

		// Top info text for a collapsed option box
		if ( self::$isTooltipDisplay === true ){
			$resultHtml .= Html::element(
				'div',
				[
					'style' => 'margin-bottom:10px;'
				],
				Message::get( 'smw-ask-otheroptions-info', Message::TEXT, Message::USER_LANGUAGE )
			);
		}

		// Table
		$resultHtml = HtmlDivTable::open(
			[
				'class' => 'smw-ask-options-list',
				'width' => '100%'
			]
		);

		while ( $option = array_shift( $optionList ) ) {
			$i++;

			// Collect elements for a row
			$rowHtml .=  $option;

			// Create table row
			if ( $i % 3 == 0 ) {
				$resultHtml .= HtmlDivTable::row(
					$rowHtml,
					[
						'class' => $i % 6 == 0 ? 'smw-ask-options-row-even' : 'smw-ask-options-row-odd',
					]
				);
				$rowHtml = '';
				$n++;
			}
		}

		// Ensure left over elements are collected as well
		$resultHtml .= HtmlDivTable::row(
			$rowHtml,
			[
				'class' => $n % 2 == 0 ? 'smw-ask-options-row-odd' : 'smw-ask-options-row-even',
			]
		);

		$resultHtml .= HtmlDivTable::close();

		return $resultHtml;
	}

	private static function optionList( $definitions, $values ) {

		$html = [];

		/**
		 * @var \ParamProcessor\ParamDefinition $definition
		 */
		foreach ( $definitions as $name => $definition ) {

			// Ignore the format parameter, as we got a special control in the GUI for it already.
			if ( $name == 'format' ) {
				continue;
			}

			// Handle sort, order separate as the generated checkbox are suboptimal, and the single
			// field interferes with the GET request on multiple sort setters
			if ( in_array( $name, [ 'sort', 'order' ] ) ) {
				continue;
			}

			// Maybe there is a better way but somehow I couldn't find one therefore
			// 'source' display will be omitted where no alternative source was found or
			// a source that was marked as default but had no other available options
			$allowedValues = $definition->getAllowedValues();

			if ( $name == 'source' && (
					count( $allowedValues ) == 0 ||
					in_array( 'default', $allowedValues ) && count( $allowedValues ) < 2
				) ) {

				continue;
			}

			$currentValue = false;

			if ( array_key_exists( $name, $values ) ) {
				$currentValue = $values[$name];
			}

			// Set default values
			if ( $name === 'limit' && ( $currentValue === null || $currentValue === false ) ) {
				$currentValue = self::$defaultLimit;
			}

			if ( $name === 'offset' && ( $currentValue === null || $currentValue === false ) ) {
				$currentValue = 0;
			}

			$html[] = '<td>' . self::field( $definition, $name ) . '</td>' . self::input( $definition, $currentValue );
		}

		return $html;
	}

	private static function field( ParamDefinition $definition, $name ) {

		$info = '';
		$class = '';

		if ( self::$isTooltipDisplay === true ) {
			$class = 'smw-ask-info';
		}

		if ( $definition->getMessage() !== null  ) {
			$info = Message::get( $definition->getMessage(), Message::TEXT, Message::USER_LANGUAGE );
		}

		return HtmlDivTable::cell(
			Html::rawElement(
				'span',
				[
					'class'     =>  $class,
					'word-wrap' => 'break-word',
					'data-info' => $info
				],
				htmlspecialchars( $name ) . ': '
			),
			[
				'overflow' => 'hidden',
				'style' => 'border:none;'
			]
		);
	}

	private static function input( ParamDefinition $definition, $currentValue ) {

		$description = '';
		$info = '';

		$input = new ParameterInput( $definition );
		$input->setInputName( 'p[' . $definition->getName() . ']' );
		//$input->setInputClass( 'smw-ask-input-' . str_replace( ' ', '-', $definition->getName() ) );

		$opts = $definition->getOptions();
		$attributes = [];

		if ( isset( $opts['style'] ) ) {
			$attributes['style'] = $opts['style'];
		}

		if ( isset( $opts['size'] ) ) {
			$attributes['size'] = $opts['size'];
		}

		// [ 'data-props' => [
		//   'property' => Foo, 'value' => 'Bar', 'title-prefix' => 'false'
		// ] ]
		if ( isset( $opts['data-props'] ) && is_array( $opts['data-props'] ) ) {
			foreach ( $opts['data-props'] as $key => $value ) {
				if ( is_string( $key ) ) {
					$attributes["data-$key"] = $value;
				}
			}
		}

		if ( isset( $opts['class'] ) ) {
			$attributes['class'] = $opts['class'];
		}

		if ( $attributes !== [] ) {
			$input->setAttributes( $attributes );
		}

		if ( $currentValue !== false ) {
			$input->setCurrentValue( $currentValue );
		}

		// Parameters description text
		if ( !self::$isTooltipDisplay ) {

			if ( $definition->getMessage() !== null ) {
				$info = Message::get( $definition->getMessage(), Message::PARSE, Message::USER_LANGUAGE );
			}

			$description =  Html::rawElement(
				'span',
				[
					'class' => 'smw-ask-parameter-description'
				],
				'<br />' . $info
			);
		}

		return HtmlDivTable::cell(
			$input->getHtml() . $description,
			[
				'overflow' => 'hidden',
				'style' => 'width:33%;border:none;'
			]
		);
	}

}
