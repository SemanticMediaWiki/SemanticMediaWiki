<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use Html;
use Title;
use SMWQueryProcessor as QueryProcessor;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class FormatSelectionWidget {

	/**
	 * @var array
	 */
	private static $resultFormats = array();

	/**
	 * @since 3.0
	 *
	 * @param array $resultFormats
	 */
	public static function setResultFormats( array $resultFormats ) {
		self::$resultFormats =  $resultFormats;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params
	 *
	 * @return string
	 */
	public static function selection( Title $title, array $params ) {

		$result = '';

		// Default
		$printer = QueryProcessor::getResultPrinter(
			'broadtable',
			QueryProcessor::SPECIAL_PAGE
		);

		$url = $title->getLocalURL( 'showformatoptions=this.value' );

		foreach ( $params as $param => $value ) {
			if ( $param !== 'format' ) {
				$url .= '&params[' . rawurlencode( $param ) . ']=' . rawurlencode( $value );
			}
		}

		$defaultLocalizedName = htmlspecialchars( $printer->getName() ) . ' (' . Message::get( 'smw_ask_defaultformat', Message::TEXT, Message::USER_LANGUAGE ) . ')';
		$defaultName = $printer->getName();

		$default = '';
		$selectedFormat = isset( $params['format'] ) ? $params['format'] : 'broadtable';

		$options = self::optionsField(
			$url,
			$selectedFormat,
			$default,
			$defaultName,
			$defaultLocalizedName
		);

		$result = Html::rawElement(
			'span',
			[
				'class' => "smw-ask-format-list"
			],
			Html::hidden( 'eq', 'yes' ) . $options
		);

		$result .= Html::rawElement(
			'span',
			[
				'id' => "formatHelp",
				'class' => "smw-ask-format-selection-help"
			],
			Message::get( [ 'smw-ask-format-selection-help', $default ], Message::TEXT, Message::USER_LANGUAGE )
		);

		return Html::rawElement(
			'fieldset',
			[
				'id' => "format",
				'class' => "smw-ask-format",
				'style' => "margin-top:0px;"
			],
			 Html::element( 'legend', [], Message::get( 'smw-ask-format', Message::TEXT, Message::USER_LANGUAGE ) ) . $result
		);
	}

	private static function optionsField( $url, $selectedFormat, &$default, $defaultName, $defaultLocalizedName ) {

		$result = Html::openElement(
				'select',
				array(
					'class' => 'smw-ask-button smw-ask-button-lgrey smw-ask-format-selector',
					'id' => 'formatSelector',
					'name' => 'p[format]',
					'data-url' => $url,
					'onchange' => "$( '#options-list' ).addClass( 'is-disabled');"
				)
			) . "\n" .
			'	<option value="broadtable"' . ( $selectedFormat == 'broadtable' ? ' selected="selected"' : '' ) . '>' . $defaultLocalizedName . '</option>' . "\n";

		$formats = array();

		foreach ( array_keys( self::$resultFormats ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != 'broadtable' && $format != 'count' && $format != 'debug' ) {
				$printer = QueryProcessor::getResultPrinter( $format, QueryProcessor::SPECIAL_PAGE );
				$formats[$format] = htmlspecialchars( $printer->getName() );
			}
		}

		natcasesort( $formats );
		$default = $defaultName;

		foreach ( $formats as $format => $name ) {
			$result .= '	<option value="' . $format . '"' . ( $selectedFormat == $format ? ' selected="selected"' : '' ) . '>' . $name . "</option>\n";

			if ( $selectedFormat == $format ) {
				$default = $name;
			}
		}

		$default = Html::rawElement(
			'a', [
				'href' => 'https://semantic-mediawiki.org/wiki/Help:' . $selectedFormat . ' format'
			], $default
		);

		$result .= "</select>";

		return $result;
	}

}
