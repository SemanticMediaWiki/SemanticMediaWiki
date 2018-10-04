<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class SortWidget {

	/**
	 * @var boolean
	 */
	private static $sortingSupport = false;

	/**
	 * @var boolean
	 */
	private static $randSortingSupport = false;

	/**
	 * @since 3.0
	 *
	 * @param boolean $sortingSupport
	 */
	public static function setSortingSupport( $sortingSupport ) {
		self::$sortingSupport = (bool)$sortingSupport;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $randSortingSupport
	 */
	public static function setRandSortingSupport( $randSortingSupport ) {
		self::$randSortingSupport = (bool)$randSortingSupport;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public static function sortSection( array $params ) {

		if ( self::$sortingSupport === false ) {
			return '';
		}

		if ( !array_key_exists( 'sort', $params ) || !array_key_exists( 'order', $params ) ) {
			$orders = [];
			$sorts = [];
		} else {
			$sorts = explode( ',', $params['sort'] );
			$orders = explode( ',', $params['order'] );
			reset( $sorts );
		}

		return Html::rawElement(
			'div',
			[
				'id' => 'options-sort',
				'class' => 'smw-ask-options-sort'
			], Html::rawElement(
				'div',
				[
					'id' => 'sorting-title',
					'class' => 'strike'
				],
				Html::rawElement(
					'span',
					[],
					Message::get( 'smw-ask-options-sort', Message::TEXT, Message::USER_LANGUAGE )
				)
			) . Html::rawElement(
				'div',
				[
					'id' => 'sorting-input', 'class' => ''
				],
				self::sortingOptions( $sorts, $orders )
			)
		);
	}

	private static function sortingOptions( array $sorts, array $orders ) {

		$result = '';

		foreach ( $orders as $i => $order ) {

			if ( in_array( $order, [ 'ASC', 'asc', 'ascending' ] )) {
				$order = 'asc';
			}

			if ( in_array( $order, [ 'DESC', 'desc', 'descending' ] )) {
				$order = 'desc';
			}

			if ( in_array( $order, [ 'RAND', 'rand', 'random' ] )) {
				$order = 'rand';
			}

			if ( !isset( $sorts[$i] ) ) {
				$sorts[$i] = '';
			}

			$html = Html::rawElement(
					'input',
					[
						'type' => 'text',
						'name' => "sort_num[]",
						'size' => '35',
						'class' => 'smw-property-input autocomplete-arrow',
						'value' => htmlspecialchars( $sorts[$i] )
					]
			);

			$html .= '<select name="order_num[]"><option ';

			if ( $order == 'asc' ) {
				$html .= 'selected="selected" ';
			}

			$html .=  'value="asc">' . Message::get( 'smw_ask_ascorder', Message::TEXT, Message::USER_LANGUAGE ) . '</option><option ';

			if ( $order == 'desc' ) {
				$html .= 'selected="selected" ';
			}

			$html .=  'value="desc">' . Message::get( 'smw_ask_descorder', Message::TEXT, Message::USER_LANGUAGE ) . "</option>";

			if ( self::$randSortingSupport ) {
				$html .= '<option ';

				if ( $order == 'rand' ) {
					$html .= 'selected="selected" ';
				}

				$html .= 'value="rand">' . Message::get( 'smw-ask-order-rand', Message::TEXT, Message::USER_LANGUAGE ) . '</option>';
			}

			$html .= '</select>';
			$html .= '<span class="smw-ask-sort-delete"><a class="smw-ask-sort-delete-action" data-target="sort_div_' . $i . '" >' . Message::get( 'delete', Message::TEXT, Message::USER_LANGUAGE ) . '</a></span>';

			$result .= Html::rawElement( 'div', [ 'id' => "sort_div_$i", 'class' => "smw-ask-sort-input" ], $html );
		}

		$result .=  '<div id="sorting_starter" style="display: none"><input type="text" name="sort_num[]" size="35" class="smw-property-input autocomplete-arrow" />';
		$result .= '<select name="order_num[]">' . "\n";
		$result .= '	<option value="asc">' . Message::get( 'smw_ask_ascorder', Message::TEXT, Message::USER_LANGUAGE ) . "</option>\n";
		$result .= '	<option value="desc">' . Message::get( 'smw_ask_descorder', Message::TEXT, Message::USER_LANGUAGE ) . "</option>\n";

		if ( self::$randSortingSupport ) {
			$result .= '	<option value="rand">' . Message::get( 'smw-ask-order-rand', Message::TEXT, Message::USER_LANGUAGE ) . "</option>\n";
		}

		$result .= "</select>";
		$result .= "</div>";
		$result .= '<div id="sorting_main"></div>' . "\n";

		return $result . Html::rawElement(
			'span',
			[
				'class' => 'smw-ask-sort-add'
			],
			Html::rawElement(
				'a',
				[
					'class' => 'smw-ask-sort-add-action'
				],
				Message::get( 'smw-ask-sort-add-action', Message::TEXT, Message::USER_LANGUAGE )
			)
		);
	}

}
