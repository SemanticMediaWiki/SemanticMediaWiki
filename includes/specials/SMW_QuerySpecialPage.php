<?php

use ParamProcessor\ParamDefinition;
use SMW\ParameterInput;

/**
 * Base class for special pages with ask query interfaces.
 *
 * Currently contains code that was duplicated in Special:Ask and Special:QueryUI.
 * Probably there is more such code to put here.
 *
 * @since 1.8
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
abstract class SMWQuerySpecialPage extends SpecialPage {
	/**
	 * Display a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @since 1.8
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 *
	 * @return string
	 */
	protected function showFormatOptions( $format, array $paramValues ) {
		$definitions = SMWQueryProcessor::getFormatParameters( $format );

		$optionsHtml = array();

		/**
		 * @var ParamProcessor\ParamDefinition $definition
		 */
		foreach ( $definitions as $name => $definition ) {
			// Ignore the format parameter, as we got a special control in the GUI for it already.
			if ( $name == 'format' ) {
				continue;
			}

			// Maybe there is a better way but somehow I couldn't find one therefore
			// 'source' display will be omitted where no alternative source was found or
			// a source that was marked as default but had no other available options
			if ( ( $name == 'source' && count ( $definition->getAllowedValues() ) == 0 ) || (
				$name == 'source' && in_array( 'default', $definition->getAllowedValues() ) &&
				count ( $definition->getAllowedValues() ) < 2 ) ) {
				continue;
			}

			$currentValue = array_key_exists( $name, $paramValues ) ? $paramValues[$name] : false;

			$optionsHtml[] =
				Html::rawElement(
					'span',
					array(
						'class' => $this->isTooltipDisplay() == true ? 'smw-ask-info' : '',
						'word-wrap' => 'break-word',
						'data-info' => $this->msg( $definition->getMessage() )->text()
					), htmlspecialchars( $name ) .  ': ' .
					$this->showFormatOption( $definition, $currentValue )
				);
		}

		$i = 0;
		$n = 0;
		$rowHtml = '';
		$resultHtml = '';

		// Top info text for a collapsed option box
		if ( $this->isTooltipDisplay() == true ){
			$resultHtml .= Html::element('div', array(
				'style' => 'margin-bottom:10px;'
				), wfMessage( 'smw-ask-otheroptions-info')->text()
			);
		}

		// Table
		$resultHtml .= Html::openElement( 'table', array(
			'class' => 'smw-ask-otheroptions',
			'width' => '100%'
			)
		);
		$resultHtml .= Html::openElement( 'tbody' );

		while ( $option = array_shift( $optionsHtml ) ) {
			$i++;

			// Collect elements for a row
			$rowHtml .= Html::rawElement('td', array(), $option );

			// Create table row
			if ( $i % 3 == 0 ){
			$resultHtml .= Html::rawElement( 'tr', array(
				'style' => 'background: ' . ( $i % 6 == 0 ? 'white' : '#eee' )
				), $rowHtml
			);
			$rowHtml = '';
			$n++;
			}
		}

		// Ensure left over elements are collected as well
		$resultHtml .= Html::rawElement( 'tr', array(
			'style' => 'background: ' . ( $n % 2 == 0 ? '#eee' : 'white' )
			), $rowHtml
		);

		$resultHtml .= Html::closeElement( 'tbody' );
		$resultHtml .= Html::closeElement( 'table' );

		return $resultHtml;
	}

	/**
	 * Get the HTML for a single parameter input
	 *
	 * @since 1.8
	 *
	 * @param ParamDefinition $definition
	 * @param mixed $currentValue
	 *
	 * @return string
	 */
	protected function showFormatOption( ParamDefinition $definition, $currentValue ) {
		// Init
		$description = '';

		$input = new ParameterInput( $definition );
		$input->setInputName( 'p[' . $definition->getName() . ']' );
		//$input->setInputClass( 'smw-ask-input-' . str_replace( ' ', '-', $definition->getName() ) );

		if ( $currentValue !== false ) {
			$input->setCurrentValue( $currentValue );
		}

		// Parameter description text
		if ( !$this->isTooltipDisplay() ) {
			$description =  Html::rawElement( 'span', array(
				'class' => 'smw-ask-parameter-description'
				), '<br />' . $this->msg( $definition->getMessage() )->text()
			);
		}

		return Html::rawElement( 'td', array(
			'overflow' => 'hidden'
			), $input->getHtml() . $description
		);
	}

	/**
	 * Getting Special:Ask user tooltip preference
	 *
	 * @since 1.8
	 *
	 *
	 * @return boolean
	 */
	protected function isTooltipDisplay() {
		// @TODO global
		// In case of RequestContext, switch to
		// $this->getUser()->getOption( 'smw-prefs-ask-options-tooltip-display' );
		return $GLOBALS['wgUser']->getOption( 'smw-prefs-ask-options-tooltip-display' );
	}
}
