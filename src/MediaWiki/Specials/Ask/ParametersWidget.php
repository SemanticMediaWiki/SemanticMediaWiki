<?php

namespace SMW\MediaWiki\Specials\Ask;

use ParamProcessor\ParamDefinition;
use SMW\ParameterInput;
use SMW\Message;
use SMWQueryProcessor as QueryProcessor;
use Html;

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
	 * @param string $format
	 * @param array $parameters
	 *
	 * @return string
	 */
	public static function options( $format, array $parameters ) {

		$fieldset = Html::rawElement(
			'fieldset',
			[
				'class' => 'smw-ask-options'
			],
			Html::element(
				'legend',
				[],
				Message::get( 'smw-ask-options', Message::TEXT, Message::USER_LANGUAGE )
			) . Html::rawElement(
				'div',
				[
					'id' => 'options-list'
				],
				self::parameterList( $format, $parameters )
			) . SortWidget::sortSection( $parameters )
		);

		return Html::rawElement(
			'div',
			[
				'id' => 'options'
			],
			$fieldset
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
	public static function parameterList( $format, array $values ) {

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
		$resultHtml .= Html::openElement(
			'table',
			[
				'class' => 'smw-ask-options-list',
				'width' => '100%'
			]
		);

		$resultHtml .= Html::openElement( 'tbody' );

		while ( $option = array_shift( $optionList ) ) {
			$i++;

			// Collect elements for a row
			$rowHtml .=  $option;

			// Create table row
			if ( $i % 3 == 0 ){
			$resultHtml .= Html::rawElement(
				'tr',
				[
					'class' => $i % 6 == 0 ? 'smw-ask-options-row-even' : 'smw-ask-options-row-odd',
				],
				$rowHtml
			);
			$rowHtml = '';
			$n++;
			}
		}

		// Ensure left over elements are collected as well
		$resultHtml .= Html::rawElement(
			'tr',
			[
				'class' => $n % 2 == 0 ? 'smw-ask-options-row-odd' : 'smw-ask-options-row-even',
			],
			$rowHtml
		);

		$resultHtml .= Html::closeElement( 'tbody' );
		$resultHtml .= Html::closeElement( 'table' );

		return $resultHtml;
	}

	private static function optionList( $definitions, $values ) {

		$html = [];

		/**
		 * @var ParamProcessor\ParamDefinition $definition
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

		return Html::rawElement(
			'span',
			[
				'class'     =>  $class,
				'word-wrap' => 'break-word',
				'data-info' => $info
			],
			htmlspecialchars( $name ) . ': '
		);
	}

	private static function input( ParamDefinition $definition, $currentValue ) {

		$description = '';
		$info = '';

		$input = new ParameterInput( $definition );
		$input->setInputName( 'p[' . $definition->getName() . ']' );
		//$input->setInputClass( 'smw-ask-input-' . str_replace( ' ', '-', $definition->getName() ) );

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

		return Html::rawElement(
			'td',
			[
				'overflow' => 'hidden'
			],
			$input->getHtml() . $description
		);
	}

}
