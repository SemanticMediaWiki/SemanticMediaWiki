<?php

/**
 * Base class for special pages with ask query interfaces.
 *
 * Currently contains code that was duplicated in Special:Ask and Special:QueryUI.
 * Probably there is more such code to put here.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.8
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
		$definitions = array_merge(
			SMWQueryProcessor::getParameters(),
			SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE )->getParameters()
		);

		$definitions = ParamDefinition::getCleanDefinitions( $definitions );

		$optionsHtml = array();

		foreach ( $definitions as $name => $definition ) {
			// Ignore the format parameter, as we got a special control in the GUI for it already.
			if ( $name == 'format' ) {
				continue;
			}

			// Maybe there is a better way but somehow I couldn't find one therefore
			// 'source' display will be omitted where no alternative source was found or
			// a source was marked as default but had no other available options
			if ( ( $name == 'source' && count ( $definition->getAllowedValues() ) == 0 ) || (
				$name == 'source' && in_array( 'default', $definition->getAllowedValues() ) &&
				count ( $definition->getAllowedValues() ) < 2 ) ) {
				continue;
			}

			$currentValue = array_key_exists( $name, $paramValues ) ? $paramValues[$name] : false;

			$optionsHtml[] =
				Html::rawElement(
					'div',
					array(
						'style' => 'width: 30%; padding: 5px; float: left;'
					),
					htmlspecialchars( $name ) . ': ' .
						$this->showFormatOption( $definition, $currentValue ) .
						'<br />' .
						Html::element( 'em', array(), $definition->getDescription() )
				);
		}

		for ( $i = 0, $n = count( $optionsHtml ); $i < $n; $i++ ) {
			if ( $i % 3 == 2 || $i == $n - 1 ) {
				$optionsHtml[$i] .= "<div style=\"clear: both\";></div>\n";
			}
		}

		$i = 0;
		$rowHtml = '';
		$resultHtml = '';

		while ( $option = array_shift( $optionsHtml ) ) {
			$rowHtml .= $option;
			$i++;

			$resultHtml .= Html::rawElement(
				'div',
				array(
					'style' => 'background: ' . ( $i % 6 == 0 ? 'white' : '#dddddd' ) . ';'
				),
				$rowHtml
			);

			$rowHtml = '';
		}

		return $resultHtml;
	}

	/**
	 * Get the HTML for a single parameter input.
	 *
	 * @since 1.8
	 *
	 * @param IParamDefinition $definition
	 * @param mixed $currentValue
	 *
	 * @return string
	 */
	protected function showFormatOption( IParamDefinition $definition, $currentValue ) {
		$input = new ParameterInput( $definition );
		$input->setInputName( 'p[' . $definition->getName() . ']' );

		if ( $currentValue !== false ) {
			$input->setCurrentValue( $currentValue );
		}

		return $input->getHtml();
	}

}