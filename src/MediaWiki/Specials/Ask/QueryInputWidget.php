<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use SMW\Utils\HtmlDivTable;

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

		$table .= HtmlDivTable::row(
			HtmlDivTable::cell(
				"<fieldset><legend>" . Message::get( 'smw_ask_queryhead', Message::TEXT, Message::USER_LANGUAGE ) . "</legend>" .
				'<textarea id="ask-query-condition" class="smw-ask-query-condition" name="q" rows="6" placeholder="...">' .
				htmlspecialchars( $queryString ) . '</textarea></fieldset>',
				[ 'class' => 'smw-ask-condition slowfade' ]
			) . HtmlDivTable::cell(
				'',
				[
					'style' => 'width:10px; border:0px; padding: 0px;'
				]
			) . HtmlDivTable::cell(
				"<fieldset><legend>" . Message::get( 'smw_ask_printhead', Message::TEXT, Message::USER_LANGUAGE ) . "</legend>" .
				'<textarea id="smw-property-input" class="smw-ask-query-printout" name="po" rows="6" placeholder="...">' .
				htmlspecialchars( $printoutString ) . '</textarea></fieldset>',
				[ 'class' => 'smw-ask-printhead slowfade' ]
			)
		);

		$table .= HtmlDivTable::close();

		return $table;
	}

}
