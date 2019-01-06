<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use SMWQueryProcessor as QueryProcessor;
use Title;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class FormatListWidget {

	/**
	 * @var array
	 */
	private static $resultFormats = [];

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
	public static function selectList( Title $title, array $params ) {

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

		$formatList = self::formatList(
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
			Html::hidden( 'eq', 'yes' ) . $formatList
		);

		return $result;
	}

	private static function formatList( $url, $selectedFormat, &$default, $defaultName, $defaultLocalizedName ) {

		$formatList = Html::rawElement(
			'option',
			[
				'value' => 'broadtable'
			] + ( $selectedFormat == 'broadtable' ? [ 'selected' ] : [] ),
			$defaultLocalizedName
		);

		$formats = [];

		foreach ( array_keys( self::$resultFormats ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != 'broadtable' && $format != 'count' && $format != 'debug' ) {
				$printer = QueryProcessor::getResultPrinter(
					$format,
					QueryProcessor::SPECIAL_PAGE
				);

				$formats[] = [
					'format' => $format,
					'name'   => htmlspecialchars( $printer->getName() ),
					'export' => $printer->isExportFormat()
				];
			}
		}

		usort( $formats, function( $x, $y ) {
			return strcasecmp( $x['name'] , $y['name'] );
		} );

		$default = $defaultName;

		foreach ( $formats as $format ) {

			$formatList .= Html::rawElement(
				'option',
				[
					'data-isexport' => $format['export'],
					'value' => $format['format']
				] + ( $selectedFormat == $format['format'] ? [ 'selected' ] : [] ),
				$format['name']
			);

			if ( $selectedFormat == $format['format'] ) {
				$default = $format['name'];
			}
		}

		$default = Html::rawElement(
			'a',
			[
				'href' => 'https://semantic-mediawiki.org/wiki/Help:' . $selectedFormat . ' format'
			],
			$default
		);

		return Html::rawElement(
			'select',
			[
				'id' => 'formatSelector', // Used in JS as selector
				'class' => 'smw-ask-button smw-ask-button-lgrey smw-ask-format-selector',
				'name' => 'p[format]',
				'data-url' => $url
			],
			$formatList
		);
	}

}
