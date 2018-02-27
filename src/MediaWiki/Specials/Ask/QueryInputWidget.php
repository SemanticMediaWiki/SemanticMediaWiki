<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use SMW\Utils\HtmlDivTable;
use SMW\Highlighter;
use Html;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class QueryInputWidget {

	/**
	 * @since 3.0
	 *
	 * @param string $queryString
	 * @param string $printoutString
	 *
	 * @return string
	 */
	public static function table( $queryString , $printoutString ) {

		$table = HtmlDivTable::open( [ 'style' => "width: 100%;" ] );

		$table .= HtmlDivTable::header(
			HtmlDivTable::cell(
				Message::get( 'smw_ask_queryhead', Message::TEXT, Message::USER_LANGUAGE ),
				[
					'class' => 'condition',
					'style' => 'width: 49.5%;'
				]
			) .	HtmlDivTable::cell(
				'',
				[]
			) .	HtmlDivTable::cell(
				Message::get( 'smw_ask_printhead', Message::TEXT, Message::USER_LANGUAGE ),
				[
					'class' => 'printout',
					'style' => 'width: 49.5%;'
				]
			)
		);

		$table .= HtmlDivTable::row(
			HtmlDivTable::cell(
				'<textarea id="ask-query-condition" class="smw-ask-query-condition" name="q" rows="6">' . htmlspecialchars( $queryString ) . '</textarea>',
				[]
			) . HtmlDivTable::cell(
				'',
				[
					'style' => 'width:10px; border:0px; padding: 0px;'
				]
			) . HtmlDivTable::cell(
				'<textarea id="smw-property-input" class="smw-ask-query-printout" name="po" rows="6">' . htmlspecialchars( $printoutString ) . '</textarea>',
				[]
			)
		);

		$table .= HtmlDivTable::close();

		return $table;
	}

}
