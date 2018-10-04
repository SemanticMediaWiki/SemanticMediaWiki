<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use SMW\Utils\HtmlModal;
use Title;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class HelpWidget {

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function html() {

		$format = 'broadtable' ;
		$text = Message::get( 'smw-ask-help', Message::PARSE, Message::USER_LANGUAGE );

		$text .= Html::rawElement(
			'div',
			[
				'class' => 'strike',
				'style' => 'padding: 5px 0 5px 0;'
			],
			Html::rawElement(
				'span',
				[
					'style' => 'font-size: 1.2em; margin-left:0px'
				],
				Message::get( 'smw-ask-format', Message::TEXT, Message::USER_LANGUAGE )
			) . Html::rawElement(
				'ul',
				[],
				Html::rawElement(
					'li',
					[
						'class' => 'smw-ask-format-help-link'
					],
					Message::get( [ 'smw-ask-format-help-link', $format ], Message::PARSE, Message::USER_LANGUAGE )
				)
			)
		);

		$text .= Html::rawElement(
			'div',
			[
				'class' => 'strike',
				'style' => 'padding: 5px 0 5px 0;'
			],
			Html::rawElement(
				'span',
				[
					'style' => 'font-size: 1.2em; margin-left:0px'
				],
				Message::get( 'smw-ask-input-assistance', Message::TEXT, Message::USER_LANGUAGE )
			)
		);

		$text .= Message::get( 'smw-ask-condition-input-assistance', Message::PARSE, Message::USER_LANGUAGE );

		$text .= Html::rawElement(
			'ul',
			[],
			Html::rawElement(
				'li',
				[],
				Message::get( 'smw-ask-condition-input-assistance-property', Message::TEXT, Message::USER_LANGUAGE )
			) .
			Html::rawElement(
				'li',
				[],
				Message::get( 'smw-ask-condition-input-assistance-category', Message::TEXT, Message::USER_LANGUAGE )
			) .
			Html::rawElement(
				'li',
				[],
				Message::get( 'smw-ask-condition-input-assistance-concept', Message::TEXT, Message::USER_LANGUAGE )
			)
		);

		$html = HtmlModal::modal(
			Message::get( 'smw-cheat-sheet', Message::TEXT, Message::USER_LANGUAGE ),
			$text,
			[
				'id' => 'ask-help',
				'class' => 'plainlinks',
				'style' => 'display:none;'
			]
		);

		return $html;
	}

}
