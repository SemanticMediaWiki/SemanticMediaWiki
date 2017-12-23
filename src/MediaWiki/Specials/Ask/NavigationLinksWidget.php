<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use SMW\Localizer;
use SMWInfolink as Infolink;
use Html;
use Title;
use SMW\Utils\HtmlModal;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class NavigationLinksWidget {

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

		$rLinks['help'] = HtmlModal::link(
			Message::get( 'smw-cheat-sheet', Message::TEXT, Message::USER_LANGUAGE ),
			[
				'class'   => 'float-right',
				'data-id' => 'ask-help'
			]
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

		$lsep = Html::rawElement(
			'span',
			[
				'style' => 'color:#aaa;'
			],
			'&#160;|&#160;'
		);

		$rsep = Html::rawElement(
			'span',
			[
				'style' => 'color:#aaa;',
				'class' => 'float-right'
			],
			'&#160;|&#160;'
		);

		$html = Html::rawElement(
			'div',
			[
				'class' => 'smw-ask-toplinks'
			],
			implode( "$lsep", $lLinks ) . '&#160;' .  implode( "$rsep", $rLinks )
		) . Html::rawElement(
			'div',
			[
				'class' => 'clear-both'
			]
		);

		return $html;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title,
	 * @param UrlArgs $urlArgs
	 * @param integer $count
	 * @param boolean $hasFurtherResults
	 *
	 * @return string
	 */
	public static function navigationLinks( Title $title, UrlArgs $urlArgs, $count, $hasFurtherResults = false ) {

		if ( $count == 0 ) {
			return '';
		}

		$userLanguage = Localizer::getInstance()->getUserLanguage();
		$navigation = '';
		$urlArgs = clone $urlArgs;

		$limit = $urlArgs->get( 'limit' );
		$offset = $urlArgs->get( 'offset' );

		// @todo FIXME: i18n: Patchwork text.
		$navigation .=
			'<b>' .
				Message::get( 'smw_result_results', Message::TEXT, Message::USER_LANGUAGE ) . ' ' . $userLanguage->formatNum( $offset + 1 ) .
			' &#150; ' .
				$userLanguage->formatNum( $offset + $count ) .
			'</b>&#160;&#160;&#160;&#160;';

		$prev = Message::get(
			'smw_result_prev',
			Message::TEXT,
			Message::USER_LANGUAGE
		);

		$next = Message::get(
			'smw_result_next',
			Message::TEXT,
			Message::USER_LANGUAGE
		);

		// Prepare navigation bar.
		if ( $offset > 0 ) {

			$urlArgs->set( 'offset', max( 0, $offset - $limit ) );
			$urlArgs->set( 'limit', $limit );

			$navigation .= '(' . Html::element(
				'a',
				array(
					'href' => $title->getLocalURL( $urlArgs ),
					'rel' => 'nofollow'
				),
				$prev . ' ' . $limit
			) . ' | ';
		} else {
			$navigation .= '(' . Html::rawElement( 'span', [ 'class' => 'smw-ask-nav-prev' ], $prev . '&#160;' . $limit ) . ' | ';
		}

		if ( $hasFurtherResults ) {

			$urlArgs->set( 'offset', $offset + $limit );
			$urlArgs->set( 'limit', $limit );

			$navigation .= Html::element(
				'a',
				array(
					'href' => $title->getLocalURL( $urlArgs ),
					'rel' => 'nofollow'
				),
				$next . ' ' . $limit
			) . ')';
		} else {
			$navigation .= Html::rawElement( 'span', [ 'class' => 'smw-ask-nav-prev' ], $next . '&#160;' . $limit ) . ')';
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

				$urlArgs->set( 'offset', $offset );
				$urlArgs->set( 'limit', $l );

				$navigation .= Html::element(
					'a',
					array(
						'href' => $title->getLocalURL( $urlArgs ),
						'rel' => 'nofollow'
					),
					$l
				);
			} else {
				$navigation .= '<b>' . $l . '</b>';
			}
		}

		$navigation .= ')';

		return Html::rawElement(
			'span',
			[
				'class' => 'smw-ask-result-navigation'
			],
			$navigation
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $navigation
	 * @param string $infoText
	 * @param Infolink|null $infoLink
	 *
	 * @return string
	 */
	public static function wrap( $navigation = '', $infoText = '', Infolink $infoLink = null ) {

		if ( $navigation === '' ) {
			return '';
		}

		$downloadLink = DownloadLinksWidget::downloadLinks(
			$infoLink
		);

		$nav = Html::rawElement(
			'div',
			[
				'class' => 'smw-ask-cond-info'
			],
			$infoText
		) . Html::rawElement(
			'div',
			[
				'class' => 'smw-horizontalrule'
			],
			''
		) . Html::rawElement(
			'div',
			[
				'class' => 'smw-ask-actions-nav'
			],
			$navigation . '&#160;&#160;&#160;' . $downloadLink
		);

		return Html::rawElement(
			'div',
			[
				'id' => 'ask-navinfo'
			],
			$nav
		);
	}

}
