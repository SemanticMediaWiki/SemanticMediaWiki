<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use SMW\Localizer;
use Html;
use Title;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class NavigationWidget {

	/**
	 * @var integer
	 */
	private static $maxInlineLimit = 500;

	/**
	 * @since 3.0
	 *
	 * @param string $maxInlineLimit
	 */
	public static function setMaxInlineLimit( $maxInlineLimit ) {
		self::$maxInlineLimit = $maxInlineLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title,
	 * @param array $visibleLinks
	 *
	 * @return string
	 */
	public static function topLinks( Title $title, $visibleLinks = [] ) {

		if ( $visibleLinks === [] ) {
			return '';
		}

		$lLinks = [];
		$rLinks = [];

		$lLinks['options'] = Html::rawElement(
			'a',
			[
				'href' => '#options'
			],
			Message::get( 'smw-ask-options', Message::TEXT, Message::USER_LANGUAGE )
		);

		$lLinks['search'] = Html::rawElement(
			'a',
			[
				'href' => '#search'
			],
			Message::get( 'smw-ask-search', Message::TEXT, Message::USER_LANGUAGE )
		);

		$lLinks['result'] = Html::rawElement(
			'a',
			[
				'href' => '#result'
			],
			Message::get( 'smw-ask-result', Message::TEXT, Message::USER_LANGUAGE )
		);

		$rLinks['empty'] = Html::rawElement(
			'a',
			[
				'href' => $title->getLocalURL(),
				'class' => 'float-right'
			],
			Message::get( 'smw-ask-empty', Message::TEXT, Message::USER_LANGUAGE )
		);

		$visibleLinks = array_flip( $visibleLinks );

		foreach ( $lLinks as $key => $value ) {
			if ( !isset( $visibleLinks[$key] ) ) {
				unset( $lLinks[$key] );
			}
		}

		foreach ( $rLinks as $key => $value ) {
			if ( !isset( $visibleLinks[$key] ) ) {
				unset( $rLinks[$key] );
			}
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-ask-toplinks'
			],
			implode( ' | ', $lLinks ) . '&#160;' .  implode( ' | ', $rLinks )
		) . Html::rawElement(
			'div',
			[
				'class' => 'clear-both'
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title,
	 * @param integer $limit
	 * @param integer $offset,
	 * @param integer $count,
	 * @param boolean $hasFurtherResults
	 * @param array $urlArgs
	 *
	 * @return string
	 */
	public static function navigation( Title $title, $limit, $offset, $count, $hasFurtherResults = false, array $urlArgs ) {

		$userLanguage = Localizer::getInstance()->getUserLanguage();
		$navigation = '';

		// @todo FIXME: i18n: Patchwork text.
		$navigation .=
			'<b>' .
				Message::get( 'smw_result_results' ) . ' ' . $userLanguage->formatNum( $offset + 1 ) .
			' &#150; ' .
				$userLanguage->formatNum( $offset + $count ) .
			'</b>&#160;&#160;&#160;&#160;';

		// Prepare navigation bar.
		if ( $offset > 0 ) {
			$href = $title->getLocalURL(
				array(
					'offset' => max( 0, $offset - $limit ),
					'limit' => $limit
				) + $urlArgs
			);

			$navigation .= '(' . Html::element(
				'a',
				array(
					'href' => $href,
					'rel' => 'nofollow'
				),
				Message::get( 'smw_result_prev' ) . ' ' . $limit
			) . ' | ';
		} else {
			$navigation .= '(' . Html::rawElement( 'span', [ 'class' => 'smw-ask-nav-prev' ], Message::get( 'smw_result_prev' ) . '&#160;' . $limit ) . ' | ';
		}

		if ( $hasFurtherResults ) {
			$href = $title->getLocalURL(
				array(
					'offset' => ( $offset + $limit ),
					'limit' => $limit
				) + $urlArgs
			);

			$navigation .= Html::element(
				'a',
				array(
					'href' => $href,
					'rel' => 'nofollow'
				),
				Message::get( 'smw_result_next' ) . ' ' . $limit
			) . ')';
		} else {
			$navigation .= Html::rawElement( 'span', [ 'class' => 'smw-ask-nav-prev' ], Message::get( 'smw_result_next' ) . '&#160;' . $limit ) . ')';
		}

		$first = true;

		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $l > self::$maxInlineLimit ) {
				break;
			}

			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;(';
				$first = false;
			} else {
				$navigation .= ' | ';
			}

			if ( $limit != $l ) {
				$href =  $title->getLocalURL(
					array(
						'offset' => $offset,
						'limit' => $l
					) + $urlArgs
				);

				$navigation .= Html::element(
					'a',
					array(
						'href' => $href,
						'rel' => 'nofollow'
					),
					$l
				);
			} else {
				$navigation .= '<b>' . $l . '</b>';
			}
		}

		$navigation .= ')';

		return Html::rawElement( 'span', [ 'class' => 'smw-ask-result-navigation' ], $navigation );
	}

}
