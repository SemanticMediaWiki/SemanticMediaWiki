<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use SMW\Utils\HtmlTable;
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

		$table = HtmlTable::open( [ 'style' => "width: 100%;" ] );

		$table .= HtmlTable::header(
			HtmlTable::cell(
				Message::get( 'smw_ask_queryhead', Message::TEXT, Message::USER_LANGUAGE ),
				[
					'class' => 'condition',
					'style' => 'width: 49.5%;'
				]
			) .	HtmlTable::cell(
				'',
				[]
			) .	HtmlTable::cell(
				Message::get( 'smw_ask_printhead', Message::TEXT, Message::USER_LANGUAGE ),
				[
					'class' => 'printout',
					'style' => 'width: 49.5%;'
				]
			)
		);

		$table .= HtmlTable::row(
			HtmlTable::cell(
				'<textarea id="ask-query-condition" class="smw-ask-query-condition" name="q" rows="6">' . htmlspecialchars( $queryString ) . '</textarea>',
				[]
			) . HtmlTable::cell(
				'',
				[
					'style' => 'width:10px; border:0px; padding: 0px;'
				]
			) . HtmlTable::cell(
				'<textarea id="smw-property-input" class="smw-ask-query-printout" name="po" rows="6">' . htmlspecialchars( $printoutString ) . '</textarea>',
				[]
			)
		);

		$table .= HtmlTable::close();

		return $table;
	}

}
