<?php

namespace SMW\MediaWiki\Specials\Ask;

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use SMW\Localizer\Message;
use SMW\Query\QueryProcessor;

/**
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class FormatListWidget {

	private static array $resultFormats = [];

	/**
	 * @since 3.0
	 */
	public static function setResultFormats( array $resultFormats ): void {
		self::$resultFormats = $resultFormats;
	}

	/**
	 * @since 3.0
	 */
	public static function selectList( Title $title, array $params ): string {
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
		$selectedFormat = $params['format'] ?? 'broadtable';

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

	private static function formatList(
		string $url,
		string $selectedFormat,
		string &$default,
		string $defaultName,
		string $defaultLocalizedName
	) {
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

		usort( $formats, static function ( array $x, array $y ): int {
			return strcasecmp( $x['name'], $y['name'] );
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
